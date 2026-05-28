<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Solicitar recuperación de contraseña (envía correo con link de token).
     */
    public function solicitarRecuperacion(Request $request)
    {
        $request->validate([
            'correo' => 'required|email|exists:usuarios,correo',
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El formato del correo es inválido.',
            'correo.exists' => 'No encontramos ningún usuario registrado con este correo.',
        ]);

        // Verificamos que el correo termine en @gamadero.tecnm.mx
        if (!str_ends_with($request->correo, '@gamadero.tecnm.mx')) {
            return response()->json([
                'message' => 'Solo se permite recuperar contraseñas para correos institucionales (@gamadero.tecnm.mx).'
            ], 422);
        }

        try {
            // Enviamos el enlace de restablecimiento.
            // Usamos la columna 'correo' como credencial para localizar al usuario.
            $status = Password::broker()->sendResetLink([
                'correo' => $request->correo
            ]);

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Hemos enviado un enlace de recuperación a tu correo electrónico.'
                ], 200);
            }

            // Si el broker retorna otro estado (como throttled o invalid user), lo exponemos
            return response()->json([
                'message' => __($status)
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Error en recuperación de contraseña: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return response()->json([
                'message' => 'Error al enviar el correo (SMTP/API): ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restablecer la contraseña usando el token.
     */
    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'correo' => 'required|email|exists:usuarios,correo',
            'password' => 'required|min:6|confirmed',
        ], [
            'token.required' => 'El token de recuperación es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El formato del correo es inválido.',
            'correo.exists' => 'El correo no coincide con nuestros registros.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        // Intentamos restablecer la contraseña a través del Password Broker.
        $status = Password::broker()->reset(
            [
                'correo' => $request->correo,
                'token' => $request->token,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation ?? $request->password
            ],
            function (Usuario $user, string $password) {
                // Actualizamos la contraseña usando password_hash en la tabla usuarios.
                $user->password_hash = Hash::make($password);
                // Si la cuenta no estaba activa, la activamos ahora
                $user->is_active = 1;
                // Si no tiene rol asignado, determinamos su rol
                if (empty($user->rol) || $user->rol === 'user') {
                    if (str_starts_with(strtolower($user->correo), 'cle')) {
                        $user->rol = 'admin_ingles';
                    } elseif (preg_match('/^[a-zA-Z]?\d{8,9}$/', $user->numero_control)) {
                        $user->rol = 'alumno';
                    } else {
                        $user->rol = 'admin';
                    }
                }
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Tu contraseña ha sido restablecida con éxito.'
            ], 200);
        }

        // Si falló por expiración de token o token inválido
        $message = 'El enlace de recuperación no es válido o ha expirado. Por favor, solicita uno nuevo.';
        if ($status === Password::INVALID_USER) {
            $message = 'No encontramos ningún usuario con ese correo electrónico.';
        }

        return response()->json([
            'message' => $message
        ], 400);
    }
}
