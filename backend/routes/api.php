<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InglesDashboardController as InglesController;
use App\Models\Carrera;

// Rutas de Autenticación y generales
Route::post('/login', [AuthController::class, 'login']);
Route::post('/activar-cuenta', [AuthController::class, 'activarCuenta']);
Route::post('/cambiar-password', [AuthController::class, 'cambiarPassword']);

Route::get('/carreras', fn() => response()->json(['carreras' => Carrera::all()]));

// Rutas del Módulo de Inglés
Route::prefix('ingles')->group(function () {
    // Generales del Dashboard y Estado
    Route::get('/dashboard', [InglesController::class, 'getDashboard']);
    Route::get('/niveles', [InglesController::class, 'getNiveles']);
    
    /**
     * Esta es la ruta que alimenta el BehaviorSubject en Angular.
     * Al estar dentro del prefix 'ingles', la URL final es: /api/ingles/alumno-estado/{id}
     */
    Route::get('/alumno-estado/{usuarioId}', [InglesController::class, 'getMiEstadoActual']);

    // Inscripción y búsqueda
    Route::post('/inscribir', [InglesController::class, 'inscribir']);
    Route::get('/buscar-alumno/{numero_control}', [InglesController::class, 'buscarAlumno']);
    
    // Gestión de Pagos
    Route::post('/actualizar-pago/{id}', [InglesController::class, 'actualizarPago']);
    
    // Gestión de Alumnos y Calificaciones
    // Nota: getAlumnosPorCurso ahora leerá el grupo desde ?grupo=X en la URL
    Route::get('/curso/{nivel_id}/alumnos', [InglesController::class, 'getAlumnosPorCurso']);
    Route::post('/calificaciones', [InglesController::class, 'guardarCalificaciones']);
    
    // Generación de Documentos (PDFs)
    Route::get('/constancia/{id_inscripcion}', [InglesController::class, 'generarConstancia']);
    Route::get('/reporte-nivel/{nivel_id}', [InglesController::class, 'generarReporteNivel']);
    Route::get('/boleta/{id}', [InglesController::class, 'generarBoleta']);
    
    // Limpieza de datos
    Route::delete('/curso/{nivel_id}/vaciar', [InglesController::class, 'vaciarCurso']);
});

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Si necesitas que la boleta sea privada, usa esta. Si no, usa la de arriba.
    Route::get('/ingles/boleta-privada/{alumno_id}', [InglesController::class, 'generarBoleta']);
    Route::post('/logout', [AuthController::class, 'logout']);
});