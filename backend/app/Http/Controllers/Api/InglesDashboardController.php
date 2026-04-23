<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NivelIngles;
use App\Models\InscripcionIngles;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;

class InglesDashboardController extends Controller
{
    public function getDashboard()
    {
        $niveles = NivelIngles::withCount('inscripciones')->get();

        $totalEnrollment = $niveles->sum('inscripciones_count');
        $gruposActivos = $niveles->where('inscripciones_count', '>', 0)->count();
        $tasaAprobacion = InscripcionIngles::where('estado_academico', 'Aprobado')->count() /
            max(1, InscripcionIngles::count()) * 100;

        return response()->json([
            'niveles' => $niveles,
            'totalEnrollment' => $totalEnrollment,
            'gruposActivos' => $gruposActivos,
            'tasaAprobacion' => round($tasaAprobacion, 2)
        ]);
    }

    public function getNiveles()
    {
        return response()->json([
            'niveles' => NivelIngles::all()
        ]);
    }

    // Busca al alumno y devuelve sus datos
    public function buscarAlumno($numero_control)
    {
        $usuario = Usuario::where('numero_control', $numero_control)->first();

        if ($usuario) {
            return response()->json([
                'encontrado' => true,
                'nombre_completo' => $usuario->nombre_completo,
                'carrera_id' => $usuario->carrera_id
            ]);
        }

        return response()->json(['encontrado' => false], 404);
    }

    public function inscribir(Request $request)
    {
        $request->validate([
            'numero_control' => 'required',
            'nombre_completo' => 'required',
            'carrera_id' => 'required',
            'nivel_id' => 'required',
            'ciclo_escolar' => 'required',
            'modalidad' => 'required',
            'grupo' => 'required', // Se agregó validación de grupo
            'comprobante' => 'nullable|file|max:5120'
        ]);

        $usuario = Usuario::firstOrCreate(
            ['numero_control' => $request->numero_control],
            [
                'nombre_completo' => $request->nombre_completo,
                'carrera_id' => $request->carrera_id,
                'correo' => strtolower(str_replace(' ', '.', $request->numero_control)) . '@itg.com'
            ]
        );

        $ruta = null;
        $isPendiente = filter_var($request->pago_pendiente, FILTER_VALIDATE_BOOLEAN);
        $estado_pago = $isPendiente ? 'Pendiente' : 'Pendiente'; 

        if ($request->hasFile('comprobante')) {
            $ruta = $request->file('comprobante')->store('comprobantes', 'public');
            $estado_pago = 'Pagado';
        }

        $duracion = null;
        if ($request->fecha_inicio && $request->fecha_fin) {
            $duracion = $request->fecha_inicio . ' a ' . $request->fecha_fin;
        }

        $inscripcion = InscripcionIngles::create([
            'usuario_id' => $usuario->id_usuario,
            'nivel_id' => $request->nivel_id,
            'ciclo_escolar' => $request->ciclo_escolar,
            'modalidad' => $request->modalidad,
            'grupo' => $request->grupo, // Se guarda el grupo (A, B, etc.)
            'duracion' => $duracion,
            'estado_pago' => $estado_pago,
            'ruta_comprobante' => $ruta
        ]);

        return response()->json([
            'message' => 'Inscripción realizada con éxito',
            'inscripcion' => $inscripcion
        ]);
    }

    // Se modificó para recibir el parámetro grupo
    public function getAlumnosPorCurso(Request $request, $nivel_id)
    {
        $grupo = $request->query('grupo', 'A'); // Por defecto busca el grupo A

        $alumnos = InscripcionIngles::with('usuario.carrera')
            ->where('nivel_id', $nivel_id)
            ->where('grupo', $grupo)
            ->get();

        $stats = [
            'promedio' => round($alumnos->avg('calificacion_final'), 2),
            'aprobados' => $alumnos->where('estado_academico', 'Aprobado')->count(),
            'reprobados' => $alumnos->where('estado_academico', 'Reprobado')->count(),
            'porcentaje' => round(($alumnos->where('estado_academico', 'Aprobado')->count() / max(1, $alumnos->count())) * 100, 2)
        ];

        return response()->json(['alumnos' => $alumnos, 'stats' => $stats]);
    }

    public function guardarCalificaciones(Request $request)
    {
        try {
            foreach ($request->calificaciones as $calif) {
                $inscripcion = InscripcionIngles::find($calif['id']);
                if ($inscripcion) {
                    $nota = $calif['calificacion_final'];
                    $inscripcion->calificacion_final = $nota;
                    $inscripcion->estado_academico = $nota >= 70 ? 'Aprobado' : 'Reprobado';
                    $inscripcion->save();
                }
            }
            return response()->json(['message' => 'Calificaciones guardadas']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Se modificó para borrar solo el grupo específico
    public function vaciarCurso(Request $request, $nivel_id)
    {
        $modalidad = $request->query('modalidad');
        $grupo = $request->query('grupo', 'A');

        if (!$modalidad) {
            return response()->json(['message' => 'Modalidad no especificada'], 400);
        }

        InscripcionIngles::where('nivel_id', $nivel_id)
            ->where('modalidad', $modalidad)
            ->where('grupo', $grupo)
            ->delete();

        return response()->json(['message' => "Ciclo vaciado para modalidad $modalidad Grupo $grupo"]);
    }

    // Se modificó para filtrar el reporte por grupo
    public function generarReporteNivel(Request $request, $nivel_id)
    {
        $modalidad = $request->query('modalidad');
        $grupo = $request->query('grupo', 'A');
        
        $nivel = NivelIngles::findOrFail($nivel_id);
        $alumnos = InscripcionIngles::with(['usuario.carrera'])
                    ->where('nivel_id', $nivel_id)
                    ->where('modalidad', $modalidad)
                    ->where('grupo', $grupo)
                    ->get();

        $stats = [
            'total' => $alumnos->count(),
            'aprobados' => $alumnos->where('estado_academico', 'Aprobado')->count(),
            'promedio' => round($alumnos->avg('calificacion_final'), 2),
            'modalidad' => $modalidad,
            'grupo' => $grupo,
            'fecha' => date('d/m/Y')
        ];

        $pdf = Pdf::loadView('pdf.reporte_analisis', compact('alumnos', 'nivel', 'stats'));
        return $pdf->download("Analisis_{$nivel->nombre}_Grup{$grupo}.pdf");
    }

    public function generarBoleta($id)
{
    // Buscamos la inscripción
    $inscripcion = InscripcionIngles::with(['usuario.carrera', 'nivel'])->findOrFail($id);
    
    // Validación de seguridad: El usuario autenticado debe ser el dueño de la boleta
    // if (auth()->user()->id_usuario !== $inscripcion->usuario_id) {
    //     return response()->json(['message' => 'No autorizado'], 403);
    // }

    if ($inscripcion->estado_academico !== 'Aprobado') {
        return response()->json(['message' => 'Boleta no disponible hasta aprobar el curso.'], 403);
    }

    $pdf = Pdf::loadView('pdf.boleta_alumno', compact('inscripcion'));
    return $pdf->download("Boleta_{$inscripcion->usuario->numero_control}.pdf");
}

    public function actualizarPago(Request $request, $id)
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $inscripcion = InscripcionIngles::findOrFail($id);

        if ($request->hasFile('comprobante')) {
            $ruta = $request->file('comprobante')->store('comprobantes', 'public');
            
            $inscripcion->update([
                'ruta_comprobante' => $ruta,
                'estado_pago' => 'Pagado'
            ]);

            return response()->json([
                'message' => 'Comprobante subido y pago actualizado',
                'inscripcion' => $inscripcion
            ]);
        }

        return response()->json(['message' => 'Error al recibir el archivo'], 400);
    }

    public function getMiEstadoActual($usuarioId)
{
    $historial = InscripcionIngles::with('nivel')
        ->where('usuario_id', $usuarioId)
        ->orderBy('nivel_id', 'asc')
        ->get();

    $aprobados = $historial->where('estado_academico', 'Aprobado')->count();
    $totalNiveles = 10; 

    // Calculamos el nivel siguiente asegurando que no pase de 10
    $nivelSiguiente = $aprobados + 1;
    if ($nivelSiguiente > $totalNiveles) {
        $nivelSiguiente = $totalNiveles;
    }

    return response()->json([
        'historial' => $historial,
        'porcentaje_total' => round(($aprobados / $totalNiveles) * 100, 2),
        'conteo_aprobados' => $aprobados,
        'nivel_siguiente' => $nivelSiguiente
    ]);
}
}