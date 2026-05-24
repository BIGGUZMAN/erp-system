<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServicioDocumento;
use App\Models\ServicioReporte;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ServicioSocialController extends Controller
{
    private $baseUrl = 'http://localhost:8000';

    private $docsRequeridos = [
        'Kardex',
        'Carta de presentacion',
        'Carta de aceptacion',
        'Solicitud de servicio social',
        'Carta compromiso de servicio social',
        'Asignacion de actividades del servicio social'
    ];

    /**
     * 1. SUBIR DOCUMENTOS INICIALES
     */
    public function subirDocumentoInicial(Request $request)
    {
        $esExterno = substr($request->usuario_id, 0, 1) === 'E';
        $docsPermitidos = $esExterno ? ['Carta de presentacion'] : $this->docsRequeridos;

        $request->validate([
            'usuario_id' => 'required',
            'tipo_documento' => 'required|string|in:' . implode(',', $docsPermitidos),
            'archivo' => 'required|file|mimes:pdf|max:5120',
        ]);

        $ruta = $request->file('archivo')->store('servicio_social/documentos', 'public');

        ServicioDocumento::updateOrCreate(
            ['usuario_id' => $request->usuario_id, 'tipo_documento' => $request->tipo_documento],
            [
                'ruta_archivo' => $ruta,
                'estado' => 'Cargado'
            ]
        );

        $conteo = ServicioDocumento::where('usuario_id', $request->usuario_id)
            ->whereIn('tipo_documento', $docsPermitidos)
            ->where('estado', 'Aprobado')
            ->count();

        $meta = $esExterno ? 1 : 6;

        if ($conteo === $meta) {
            $this->activarCicloReportes($request->usuario_id);
        }

        return response()->json(['message' => 'Documento subido correctamente.', 'conteo' => $conteo]);
    }

    /**
     * 2. ACTIVAR CICLO DE REPORTES
     */
    private function activarCicloReportes($usuarioId)
    {
        if (ServicioReporte::where('usuario_id', $usuarioId)->exists()) {
            return;
        }

        $fechaInicio = Carbon::now();

        for ($i = 1; $i <= 3; $i++) {
            ServicioReporte::create([
                'usuario_id' => $usuarioId,
                'numero_reporte' => $i,
                'fecha_inicio_periodo' => $fechaInicio->copy()->addMonths(($i - 1) * 2),
                'fecha_limite' => $fechaInicio->copy()->addMonths($i * 2),
                'estado' => ($i === 1) ? 'ACTIVO' : 'INACTIVO',
            ]);
        }
    }

    /**
     * 3. SUBIR REPORTE BIMESTRAL / ENSAYO
     */
    public function subirReporteBimestral(Request $request)
    {
        if ($request->tipo_entidad === 'ensayo') {
            $rutaEnsayo = $request->file('archivo')->store('servicio_social/ensayos', 'public');
            ServicioDocumento::updateOrCreate(
                ['usuario_id' => $request->usuario_id, 'tipo_documento' => 'Ensayo Final'],
                ['ruta_archivo' => $rutaEnsayo, 'estado' => 'EN_REVISION']
            );
            return response()->json(['message' => 'Ensayo final subido correctamente.']);
        }

        $request->validate([
            'reporte_id' => 'required|exists:servicio_reportes,id',
            'archivo' => 'required|file|mimes:pdf|max:5120',
        ]);

        $reporte = ServicioReporte::findOrFail($request->reporte_id);

        if ($reporte->estado !== 'ACTIVO') {
            return response()->json(['error' => 'Este reporte no se encuentra activo para entrega.'], 403);
        }

        $rutaReporte = $request->file('archivo')->store('servicio_social/reportes', 'public');

        $reporte->update([
            'ruta_archivo' => $rutaReporte,
            'estado' => 'EN_REVISION'
        ]);

        return response()->json(['message' => 'Reporte entregado satisfactoriamente.']);
    }

    /**
     * 4. OBTENER ESTADO
     */
    public function getEstadoServicio($usuarioId)
    {
        ServicioReporte::where('usuario_id', $usuarioId)
            ->where('estado', 'ACTIVO')
            ->where('fecha_limite', '<', now())
            ->update(['estado' => 'BLOQUEADO_VENCIDO']);

        $docsSubidos = ServicioDocumento::where('usuario_id', $usuarioId)->get();
        $reportes = ServicioReporte::where('usuario_id', $usuarioId)->orderBy('numero_reporte', 'asc')->get();

        return response()->json([
            'documentacion' => $docsSubidos,
            'reportes' => $reportes,
            'puedeSubirEnsayo' => ($reportes->where('estado', 'APROBADO')->count() === 3)
        ]);
    }

    /**
     * 5. DESBLOQUEAR REPORTE (ADMIN)
     */
    public function desbloquearReporteAdmin(Request $request)
    {
        $request->validate([
            'reporte_id' => 'required|exists:servicio_reportes,id',
            'nueva_fecha' => 'required|date|after:now'
        ]);

        $reporte = ServicioReporte::find($request->reporte_id);
        $reporte->update([
            'estado' => 'ACTIVO',
            'fecha_limite' => $request->nueva_fecha,
            'comentarios_admin' => 'Plazo extendido por administración.'
        ]);

        return response()->json(['message' => 'Reporte desbloqueado correctamente.']);
    }

    /**
     * 6. VER ARCHIVO
     */
    public function verArchivo(Request $request)
    {
        $ruta = $request->query('ruta') ?? $request->query('path');

        if ($ruta && (str_contains($ruta, '<div') || str_contains($ruta, '<html'))) {
            return response($ruta, 200)
                ->header('Content-Type', 'text/html')
                ->header('Access-Control-Allow-Origin', 'http://localhost:4200')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('X-Frame-Options', 'ALLOWALL');
        }

        if ($ruta && Storage::disk('public')->exists($ruta)) {
            $file = Storage::disk('public')->get($ruta);
            $type = Storage::disk('public')->mimeType($ruta);

            return response($file, 200)
                ->header('Content-Type', $type)
                ->header('Content-Disposition', 'inline; filename="documento.pdf"')
                ->header('Access-Control-Allow-Origin', 'http://localhost:4200')
                ->header('X-Frame-Options', 'ALLOWALL');
        }

        return response()->json(['error' => 'Archivo no encontrado.'], 404);
    }

    /**
     * 7. VALIDAR Y TERMINAR
     */
    public function validarYTerminar(Request $request)
    {
        $request->validate(['usuario_id' => 'required']);
        $alumno = Usuario::where('id_usuario', $request->usuario_id)->first();
        if (!$alumno) return response()->json(['error' => 'Usuario no encontrado'], 404);
        return response()->json(['message' => 'Proceso validado correctamente.']);
    }

    /**
     * 8. GET ALUMNOS ADMIN
     */
    public function getAlumnosAdmin()
    {
        $pendientes = Usuario::whereHas('servicioReportes', function ($query) {
                $query->whereIn('estado', ['EN_REVISION', 'BLOQUEADO_VENCIDO']);
            })
            ->orWhereHas('servicioDocumentos', function ($query) {
                $query->whereIn('estado', ['Cargado', 'EN_REVISION', 'Rechazado']);
            })
            ->with(['servicioReportes', 'servicioDocumentos'])
            ->get();

        return response()->json($pendientes);
    }

    /**
     * 9. VALIDAR REPORTE ADMIN
     */
    public function validarReporteAdmin(Request $request)
    {
        $request->validate([
            'reporte_id' => 'required',
            'accion' => 'required|in:APROBADO,RECHAZADO',
            'observaciones' => 'nullable|string'
        ]);

        $reporte = ServicioReporte::find($request->reporte_id);
        if (!$reporte) return response()->json(['error' => 'Reporte no encontrado'], 404);

        $reporte->update([
            'estado' => $request->accion,
            'comentarios_admin' => $request->observaciones 
        ]);

        if ($request->accion === 'APROBADO' && $reporte->numero_reporte < 3) {
            ServicioReporte::where('usuario_id', $reporte->usuario_id)
                ->where('numero_reporte', $reporte->numero_reporte + 1)
                ->where('estado', 'INACTIVO')
                ->update(['estado' => 'ACTIVO']);
        }

        return response()->json(['message' => 'Validación procesada correctamente.']);
    }

    /**
     * 10. VALIDAR DOCUMENTO / ENSAYO
     */
    public function validarDocumento(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:servicio_documentos,id',
            'accion' => 'required|in:APROBADO,RECHAZADO',
            'observaciones' => 'nullable|string'
        ]);

        $doc = ServicioDocumento::find($request->documento_id);
        if (!$doc) return response()->json(['error' => 'Documento no encontrado'], 404);

        $doc->update([
            'estado' => $request->accion,
            'comentarios_admin' => $request->observaciones
        ]);

        return response()->json(['message' => 'Documento validado correctamente.']);
    }

    /**
     * 11. OBTENER ALUMNOS COMPLETADOS
     */
    public function getAlumnosCompletadosAdmin()
    {
        $alumnosCompletados = Usuario::whereHas('servicioReportes', function ($query) {
                $query->where('estado', 'APROBADO');
            }, '=', 3)
            ->whereHas('servicioDocumentos', function ($query) {
                $query->where('tipo_documento', 'Ensayo Final')
                      ->where('estado', 'APROBADO');
            })
            ->with(['servicioDocumentos'])
            ->get();

        return response()->json($alumnosCompletados->map(function ($alumno) {
            return [
                'id_usuario' => $alumno->id_usuario,
                'nombre_completo' => $alumno->nombre_completo,
                'carrera' => $alumno->carrera ?? 'INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIONES',
            ];
        }));
    }

    /**
     * 12. GENERAR CONSTANCIA
     */
    public function liberarAlumnoConstancia(Request $request)
    {
        $request->validate([
            'usuario_id'         => 'required|exists:usuarios,id_usuario',
            'nombre_dependencia' => 'nullable|string',
            'actividades'        => 'nullable|string',
            'horas'              => 'required|integer',
            'periodo_inicio_fin' => 'nullable|string'
        ]);

        $alumno = Usuario::where('id_usuario', $request->usuario_id)->first();

        $nombreAlumno = $alumno->nombre_completo;
        $numeroControl = $alumno->id_usuario;
        $carrera = $alumno->carrera ?? 'INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIONES';
        
        $primerReporte = ServicioReporte::where('usuario_id', $request->usuario_id)->where('numero_reporte', 1)->first();
        $tercerReporte = ServicioReporte::where('usuario_id', $request->usuario_id)->where('numero_reporte', 3)->first();

        $fechaInicioObj = $primerReporte ? Carbon::parse($primerReporte->fecha_inicio_periodo) : Carbon::now()->subMonths(6);
        $fechaFinObj = $tercerReporte ? Carbon::parse($tercerReporte->fecha_limite) : Carbon::now();

        $fechaExpedicionObj = $fechaFinObj->copy()->addDays(5);

        $periodoTextoReal = $fechaInicioObj->format('d') . " DE " . strtoupper($this->getMesEspanol($fechaInicioObj->format('m'))) . " DEL " . $fechaInicioObj->format('Y') . " AL " . $fechaFinObj->format('d') . " DE " . strtoupper($this->getMesEspanol($fechaFinObj->format('m'))) . " DEL " . $fechaFinObj->format('Y');

        $diaExpedicion = $fechaExpedicionObj->format('d');
        $mesExpedicionText = strtoupper($this->getMesEspanol($fechaExpedicionObj->format('m')));
        $anioExpedicion = $fechaExpedicionObj->format('Y');

        $fechaSuperiorDerecha = $diaExpedicion . "/" . $mesExpedicionText . "/" . $anioExpedicion;

        $actividadesEstandar = "ACTIVIDADES ADMINISTRATIVAS Y DE APOYO AL ÁREA ASIGNADA";

        $htmlContent = "
        <div style='font-family: \"Arial\", sans-serif; padding: 25px; line-height: 1.5; color: #000; font-size: 11pt;'>
            <div style='text-align: right; margin-bottom: 45px; font-size: 11pt; font-family: \"Arial\", sans-serif; font-weight: bold;'>
                CIUDAD DE MÉXICO, {$fechaSuperiorDerecha}
            </div>
            <div style='text-align: right; margin-bottom: 35px; font-size: 11pt; font-family: \"Arial\", sans-serif;'>
                <strong>Instituto Tecnológico de Gustavo A. Madero</strong><br>
                Departamento de Gestión Tecnológica y Vinculación<br>
                <strong>ASUNTO: CONSTANCIA DE TERMINACIÓN DE SERVICIO SOCIAL</strong>
            </div>
            <div style='margin-top: 25px; margin-bottom: 25px; font-weight: bold;'>
                A QUIEN CORRESPONDA:
            </div>
            <p style='text-align: justify; margin-bottom: 20px; font-family: \"Arial\", sans-serif;'>
                Por medio de la presente se hace constar que:
            </p>
            <p style='text-align: justify; margin-bottom: 20px; font-family: \"Arial\", sans-serif; line-height: 1.6;'>
                Según documentos que obran en los archivos de esta Institución, a el C. <strong>" . strtoupper($nombreAlumno) . "</strong>, 
                con número de control <strong>{$numeroControl}</strong> de la carrera de <strong>" . strtoupper($carrera) . "</strong> 
                realizó su Servicio Social en la siguiente dependencia: 
                <span style='font-weight: bold; color: #002855;'>
                    " . ($request->nombre_dependencia ?: '________________________________________') . "
                </span>
                desarrollando las siguientes actividades: <strong>{$actividadesEstandar}</strong>, 
                cubriendo un total de <strong>{$request->horas} horas</strong>, durante el periodo comprendido <strong>{$periodoTextoReal}</strong> 
                con un nivel de desempeño EXCELENTE.
            </p>
            <p style='text-align: justify; margin-bottom: 25px; font-family: \"Arial\", sans-serif;'>
                Este servicio social fue realizado de acuerdo a lo establecido en la <strong>Ley Reglamentaria del Artículo 5o.
                Constitucional</strong> relativo al ejercicio de las <strong>Profesiones y los Reglamentos</strong> que rigen a la normativa emitida por el <strong>Tecnológico Nacional de México</strong>.
            </p>
            <p style='text-align: justify; margin-bottom: 45px; font-family: \"Arial\", sans-serif;'>
                Se extiende la presente para los fines legales que al interesado convengan, en la ciudad de <strong>México</strong>, a los <strong>{$diaExpedicion}</strong> días del mes de <strong>{$mesExpedicionText}</strong> del año <strong>{$anioExpedicion}</strong>.
            </p>
            <div style='text-align: center; margin-top: 40px; margin-bottom: 45px; font-weight: bold;'>
                A t e n t a m e n t e
            </div>
            <table style='width: 100%; border-collapse: collapse; margin-top: 30px; text-align: center; font-family: \"Arial\", sans-serif; font-size: 9.5pt; font-weight: bold;'>
                <tr>
                    <td style='width: 45%; vertical-align: top;'>
                        <div style='border-top: 1px solid #000; width: 85%; margin: 0 auto 8px auto;'></div>
                        LIC. ROCÍO ESPINAL DÍAZ<br>
                        <span style='font-weight: normal; font-size: 8.5pt; color: #333; display: block; margin-top: 3px; line-height: 1.3;'>
                            JEFA DEL DEPARTAMENTO DE<br>GESTIÓN TECNOLÓGICA Y VINCULACIÓN
                        </span>
                    </td>
                    <td style='width: 10%;'></td>
                    <td style='width: 45%; vertical-align: top;'>
                        <div style='border-top: 1px solid #000; width: 85%; margin: 0 auto 8px auto;'></div>
                        DR. ARTURO ERNESTO MARES GARDEA<br>
                        <span style='font-weight: normal; font-size: 8.5pt; color: #333; display: block; margin-top: 3px; line-height: 1.3;'>
                            DIRECTOR DEL INSTITUTO TECNOLÓGICO<br>DE GUSTAVO A. MADERO
                        </span>
                    </td>
                </tr>
            </table>
            <div style='margin-top: 60px; font-size: 8pt; line-height: 1.3; color: #444; text-align: left; font-family: \"Arial\", sans-serif;'>
                C.c.p. -Servicios Escolares.-Expediente del (la) estudiante<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-Archivo.
            </div>
        </div>
        ";

        ServicioDocumento::updateOrCreate(
            [
                'usuario_id' => $request->usuario_id,
                'tipo_documento' => 'Constancia de Liberacion'
            ],
            [
                'ruta_archivo' => $htmlContent, 
                'estado' => 'APROBADO',
                'comentarios_admin' => 'Servicio Social Concluido Satisfactoriamente.'
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'La constancia ha sido inyectada con éxito al dashboard del alumno.'
        ]);
    }

    private function getMesEspanol($mesNum) {
        $meses = [
            '01' => 'ENERO', '02' => 'FEBRERO', '03' => 'MARZO', '04' => 'ABRIL', '05' => 'MAYO', 
            '06' => 'JUNIO', '07' => 'JULIO', '08' => 'AGOSTO', '09' => 'SEPTIEMBRE', 
            '10' => 'OCTUBRE', '11' => 'NOVIEMBRE', '12' => 'DICIEMBRE'
        ];
        return $meses[$mesNum] ?? 'DICIEMBRE';
    }
}