<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestablecerPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = 'http://localhost:4200/cambiar-password?token=' . $this->token . '&email=' . urlencode($email);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña - ERP ITGAM')
            ->greeting('¡Hola, ' . $notifiable->nombre_completo . '!')
            ->line('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta en el ERP ITGAM.')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace de recuperación expirará en 60 minutos.')
            ->line('Si no realizaste esta solicitud, puedes ignorar este mensaje de forma segura.')
            ->salutation('Saludos cordiales, Equipo ERP ITGAM');
    }
}
