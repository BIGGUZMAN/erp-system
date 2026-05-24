<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InscripcionIngles;
use App\Models\ServicioReporte;
use App\Models\Alumno;
use Illuminate\Http\Request;

class DashboardStatsController extends Controller
{
    public function getGlobalStats()
    {
        try {
            // 1. Alumnos en Inglés (Tabla: inscripciones_ingles)
            $totalIngles = InscripcionIngles::count();

            // 2. Servicio Social Activo (Tabla: servicio_reportes)
            // Contamos usuarios únicos que han entregado reportes
            $activeSocialService = ServicioReporte::distinct()->count('usuario_id');

            // 3. Residencias (Tabla: alumnos)
            // IMPORTANTE: Como 'estatus' no existe, lo pongo en 0 para evitar el error 500.
            // Si después descubres que la columna se llama 'estado', cámbialo aquí.
            $activeResidencies = 0; 

            return response()->json([
                'success' => true,
                'data' => [
                    'totalIngles' => $totalIngles,
                    'activeSocialService' => $activeSocialService,
                    'activeResidencies' => $activeResidencies
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}