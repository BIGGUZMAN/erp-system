<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InglesDashboardController as InglesController;
use App\Http\Controllers\Api\ServicioSocialController;
use App\Http\Controllers\Api\DashboardStatsController; 
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Models\Carrera;

/*
|--------------------------------------------------------------------------
| API Routes - ERP ITGAM
|--------------------------------------------------------------------------
*/

// Rutas Públicas y de Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/activar-cuenta', [AuthController::class, 'activarCuenta']);
Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);
Route::post('/recuperar-password/solicitar', [PasswordResetController::class, 'solicitarRecuperacion']);
Route::post('/recuperar-password/restablecer', [PasswordResetController::class, 'restablecerPassword']);
Route::get('/carreras', fn() => response()->json(['carreras' => Carrera::all()]));

/*
|--------------------------------------------------------------------------
| DASHBOARD GLOBAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/stats', [DashboardStatsController::class, 'getGlobalStats']);

/*
|--------------------------------------------------------------------------
| MÓDULO DE SERVICIO SOCIAL
|--------------------------------------------------------------------------
*/
Route::prefix('servicio-social')->group(function () {
    
    // Visualización de archivos (pública para poder cargar en iframe)
    Route::get('ver-archivo', [ServicioSocialController::class, 'verArchivo']);

    // Rutas para el Alumno
    Route::prefix('alumno')->group(function () {
        Route::get('/estado/{usuarioId}',        [ServicioSocialController::class, 'getEstadoServicio']);
        Route::post('/subir-documento',           [ServicioSocialController::class, 'subirDocumentoInicial']);
        Route::post('/subir-reporte',             [ServicioSocialController::class, 'subirReporteBimestral']);
    });

    // Rutas para el Administrador
    Route::prefix('admin')->group(function () {
        Route::get('/pendientes',                 [ServicioSocialController::class, 'getAlumnosAdmin']);
        Route::get('/alumnos-completados',        [ServicioSocialController::class, 'getAlumnosCompletadosAdmin']);
        Route::post('/validar-reporte',           [ServicioSocialController::class, 'validarReporteAdmin']);
        Route::post('/validar-documento',         [ServicioSocialController::class, 'validarDocumento']);
        Route::post('/validar-terminar',          [ServicioSocialController::class, 'validarYTerminar']);
        Route::post('/registrar-alumno',          [AuthController::class, 'registrarAlumnoAdmin']);
        Route::post('/desbloquear-reporte',       [ServicioSocialController::class, 'desbloquearReporteAdmin']);
        Route::post('/enviar-carta',              [ServicioSocialController::class, 'enviarCartaTermino']);
        Route::post('/actualizar-logos',          [ServicioSocialController::class, 'actualizarLogos']);

        // Módulo de empresas y convenios
        Route::prefix('empresas')->group(function () {
            Route::get('/',             [EmpresaController::class, 'index']);
            Route::post('/',            [EmpresaController::class, 'store']);
            Route::get('/{id}',         [EmpresaController::class, 'show']);
            Route::put('/{id}',         [EmpresaController::class, 'update']);
            Route::delete('/{id}',      [EmpresaController::class, 'destroy']);
            Route::post('/{id}/renovar',[EmpresaController::class, 'renovar']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| MÓDULO DE INGLÉS
|--------------------------------------------------------------------------
*/
Route::prefix('ingles')->group(function () {
    Route::get('/dashboard',                      [InglesController::class, 'getDashboard']);
    Route::get('/niveles',                        [InglesController::class, 'getNiveles']);
    Route::get('/alumno-estado/{usuarioId}',      [InglesController::class, 'getMiEstadoActual']);
    Route::post('/inscribir',                     [InglesController::class, 'inscribir']);
    Route::get('/buscar-alumno/{numero_control}', [InglesController::class, 'buscarAlumno']);
    Route::get('/buscar-historial/{numero_control}', [InglesController::class, 'buscarHistorial']);
    Route::post('/actualizar-pago/{id}',          [InglesController::class, 'actualizarPago']);
    Route::get('/curso/{nivel_id}/alumnos',       [InglesController::class, 'getAlumnosPorCurso']);
    Route::post('/calificaciones',                [InglesController::class, 'guardarCalificaciones']);
    Route::get('/constancia/{id_inscripcion}',    [InglesController::class, 'generarConstancia']);
    Route::get('/reporte-nivel/{nivel_id}',       [InglesController::class, 'generarReporteNivel']);
    Route::get('/boleta/{id}',                    [InglesController::class, 'generarBoleta']);
    Route::delete('/curso/{nivel_id}/vaciar',     [InglesController::class, 'vaciarCurso']);
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/ingles/boleta-privada/{alumno_id}', [InglesController::class, 'generarBoleta']);
});