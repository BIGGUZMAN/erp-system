<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use App\Models\Carrera;

class AuthController extends Controller
{
    /**
     * Determina el tipo de dashboard basándose en el número de control y correo
     */
    private function determinarTipo($numeroControl, $correo)
    {
        // 1. Si el correo empieza con 'Cle', es Admin de Inglés
        if (str_starts_with(strtolower($correo), 'cle')) {
            return 'admin_ingles';
        }

        // 2. REGLA ALUMNO PRECISA: Una letra opcional al inicio seguida de 8 o 9 números
        // Esto cubre C221130028 y 221130029
        if (preg_match('/^[a-zA-Z]?\d{8,9}$/', $numeroControl)) {
            return 'alumno';
        }

        // 3. De lo contrario, es Admin General
        return 'admin';
    }

    /**
     * Activar cuenta (Solo una vez por usuario)
     */
    public function activarCuenta(Request $request)
    {
        $request->validate([
            'numero_control' => 'required',
            'nombre_completo' => 'required|string|max:255',
            'correo' => 'required|email',
            'password' => 'required|min:6',
            'carrera_id' => 'nullable|integer'
        ]);

        $numeroControl = $request->numero_control;
        $correo = $request->correo;

        // Buscamos al usuario que insertaste previamente vía SQL
        $usuario = Usuario::where('numero_control', $numeroControl)
            ->where('correo', $correo)
            ->first();

        if (!$usuario) {
            return response()->json(['message' => 'Los datos no coinciden con nuestros registros.'], 404);
        }

        // Revisamos si la cuenta ya tiene contraseña asignada
        if (!is_null($usuario->password_hash)) {
            return response()->json(['message' => 'Esta cuenta ya ha sido activada anteriormente.'], 422);
        }

        if (!str_ends_with($correo, '@gamadero.tecnm.mx')) {
            return response()->json(['message' => 'Usa tu correo institucional (@gamadero.tecnm.mx).'], 422);
        }

        $tipo = $this->determinarTipo($numeroControl, $correo);
        
        $usuario->password_hash = Hash::make($request->password);
        $usuario->nombre_completo = $request->nombre_completo;
        $usuario->rol = $tipo;
        $usuario->is_active = 1; 

        if ($tipo === 'alumno') {
            if ($request->filled('carrera_id')) {
                // Ajustado a 'id_carrera' que es el nombre real en tu DB
                $carrera = Carrera::where('id_carrera', $request->carrera_id)->first();
                if ($carrera) {
                    $usuario->carrera_id = $carrera->id_carrera;
                    $carrera->increment('total_alumnos');
                }
            } else {
                // Si falta la carrera, devolvemos las opciones
                return response()->json([
                    'message' => 'Selecciona tu carrera para continuar.',
                    'carreras_disponibles' => Carrera::all()
                ], 200);
            }
        }

        $usuario->save();

        return response()->json([
            'message' => 'Cuenta activada con éxito. Ya puedes iniciar sesión.',
            'tipo' => $tipo
        ], 200);
    }

    /**
     * Inicio de Sesión
     */
    public function login(Request $request)
    {
        $request->validate([
            'numero_control' => 'required',
            'password' => 'required'
        ]);

        // Cargamos el usuario junto con su carrera usando la relación definida en el modelo
        $usuario = Usuario::with('carrera')->where('numero_control', $request->numero_control)->first();

        if (!$usuario) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        if (is_null($usuario->password_hash)) {
            return response()->json(['message' => 'Debes activar tu cuenta primero.'], 403);
        }

        if (!Hash::check($request->password, $usuario->password_hash)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 401);
        }

        $tipo = $this->determinarTipo($usuario->numero_control, $usuario->correo);

        return response()->json([
            'message' => 'Login exitoso',
            'tipo' => $tipo, 
            'usuario' => $usuario // Ahora 'usuario' incluye el objeto 'carrera' con su nombre
        ]);
    }

    /**
     * Obtener lista de carreras
     */
    public function obtenerCarreras()
    {
        return response()->json([
            'carreras' => Carrera::orderBy('id_carrera', 'asc')->get()
        ], 200);
    }

    /**
     * Cambiar Password (Estando logueado / Conociendo la actual)
     */
    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'numero_control' => 'required',
            'password_actual' => 'required',
            'password_nuevo' => 'required|min:6'
        ]);

        $usuario = Usuario::where('numero_control', $request->numero_control)->first();

        if (!$usuario || !Hash::check($request->password_actual, $usuario->password_hash)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta'], 401);
        }

        $usuario->password_hash = Hash::make($request->password_nuevo);
        $usuario->save();

        return response()->json(['message' => 'Contraseña actualizada con éxito']);
    }

    /**
     * Recuperar Password
     */
    public function recuperarPassword(Request $request)
    {
        $request->validate([
            'numero_control' => 'required',
            'correo' => 'required|email',
            'password_nuevo' => 'required|min:6'
        ]);

        $usuario = Usuario::where('numero_control', $request->numero_control)
                          ->where('correo', $request->correo)
                          ->first();

        if (!$usuario) {
            return response()->json(['message' => 'Los datos proporcionados no coinciden con nuestros registros.'], 404);
        }

        if (!str_ends_with($request->correo, '@gamadero.tecnm.mx')) {
            return response()->json(['message' => 'Debe usar su correo institucional.'], 422);
        }

        $usuario->password_hash = Hash::make($request->password_nuevo);
        $usuario->save();

        return response()->json(['message' => 'Tu contraseña ha sido restablecida con éxito.']);
    }
}