<?php

namespace App\Mail;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoTransport extends AbstractTransport
{
    protected $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (!$email instanceof Email) {
            return;
        }

        $sender = $email->getFrom()[0] ?? null;
        $senderEmail = $sender ? $sender->getAddress() : config('mail.from.address');
        $senderName = $sender ? $sender->getName() : config('mail.from.name');

        $to = [];
        foreach ($email->getTo() as $recipient) {
            $to[] = [
                'email' => $recipient->getAddress(),
                'name' => $recipient->getName() ?: null,
            ];
        }

        $payload = [
            'sender' => [
                'name' => $senderName ?: 'ERP System',
                'email' => $senderEmail,
            ],
            'to' => $to,
            'subject' => $email->getSubject(),
        ];

        $html = $email->getHtmlBody();
        if ($html !== null && $html !== '') {
            $payload['htmlContent'] = $html;
        }
        
        $text = $email->getTextBody();
        if ($text !== null && $text !== '') {
            $payload['textContent'] = $text;
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $this->apiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        Log::debug('Brevo API send response payload', [
            'status' => $response->status(),
            'body' => $response->json() ?: $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('Brevo SMTP API send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to send email via Brevo API: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
