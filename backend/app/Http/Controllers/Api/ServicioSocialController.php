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
     * Los documentos NO se validan por admin. Al subir el 6to, se activan los reportes.
     */
    public function subirDocumentoInicial(Request $request)
    {
        \Log::info('Subir documento:', [
            'usuario_id' => $request->usuario_id,
            'tipo_documento' => $request->tipo_documento,
            'docs_requeridos' => $this->docsRequeridos
        ]);

        $esExterno = substr($request->usuario_id, 0, 1) === 'E';
        $docsPermitidos = $esExterno ? ['Carta de presentacion'] : $this->docsRequeridos;

        // Custom validation to handle accents, spaces, and case differences
        $normalizarTexto = function ($texto) {
            $utf8 = mb_convert_encoding($texto, 'UTF-8', 'auto');
            $sinAcentos = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
                ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
                $utf8
            );
            return trim(strtolower($sinAcentos));
        };

        $tipoRecibidoNormalizado = $normalizarTexto($request->tipo_documento ?? '');
        $documentoExacto = null;

        foreach ($docsPermitidos as $doc) {
            if ($normalizarTexto($doc) === $tipoRecibidoNormalizado) {
                $documentoExacto = $doc;
                break;
            }
        }

        if (!$documentoExacto) {
            \Log::error('Validation failed for tipo_documento:', [
                'recibido' => $request->tipo_documento,
                'recibido_normalizado' => $tipoRecibidoNormalizado,
                'permitidos' => $docsPermitidos
            ]);
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => [
                    'tipo_documento' => ['El tipo de documento recibido no coincide con los permitidos.']
                ],
                'recibido' => $request->tipo_documento,
                'permitidos' => $docsPermitidos
            ], 422);
        }

        // Overwrite the request parameter with the exact database string
        $request->merge(['tipo_documento' => $documentoExacto]);

        try {
            $request->validate([
                'usuario_id' => 'required',
                'archivo'    => 'required|file|mimes:pdf|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }

        $ruta = $request->file('archivo')->store('servicio_social/documentos', 'public');

        ServicioDocumento::updateOrCreate(
            ['usuario_id' => $request->usuario_id, 'tipo_documento' => $request->tipo_documento],
            [
                'ruta_archivo' => $ruta,
                'estado'       => 'Cargado'  // Solo "Cargado", no requiere aprobación
            ]
        );

        // Contar cuántos de los docs requeridos ya tienen archivo subido
        $conteo = ServicioDocumento::where('usuario_id', $request->usuario_id)
            ->whereIn('tipo_documento', $docsPermitidos)
            ->whereNotNull('ruta_archivo')
            ->count();

        $meta = $esExterno ? 1 : 6;

        // Al completar todos los documentos, activar el ciclo de reportes
        if ($conteo >= $meta) {
            $this->activarCicloReportes($request->usuario_id);
        }

        return response()->json([
            'message' => 'Documento subido correctamente.',
            'conteo'  => $conteo,
            'total'   => $meta,
            'ciclo_activado' => ($conteo >= $meta)
        ]);
    }

    /**
     * 2. ACTIVAR CICLO DE REPORTES (privado)
     * Crea los 3 reportes bimestrales. Solo R1 queda ACTIVO; R2 y R3 quedan INACTIVO.
     */
    private function activarCicloReportes($usuarioId)
    {
        if (ServicioReporte::where('usuario_id', $usuarioId)->exists()) {
            return; // Ya fueron creados, no duplicar
        }

        $fechaInicio = Carbon::now();

        for ($i = 1; $i <= 3; $i++) {
            ServicioReporte::create([
                'usuario_id'           => $usuarioId,
                'numero_reporte'       => $i,
                'fecha_inicio_periodo' => $fechaInicio->copy()->addMonths(($i - 1) * 2),
                'fecha_limite'         => $fechaInicio->copy()->addMonths($i * 2),
                'estado'               => ($i === 1) ? 'ACTIVO' : 'INACTIVO',
            ]);
        }
    }

    /**
     * 3. SUBIR REPORTE BIMESTRAL o ENSAYO FINAL
     */
    public function subirReporteBimestral(Request $request)
    {
        // --- Caso: Es el Ensayo Final ---
        if ($request->tipo_entidad === 'ensayo') {
            $request->validate([
                'usuario_id' => 'required',
                'archivo'    => 'required|file|mimes:pdf|max:5120',
            ]);

            // Verificar que los 3 reportes estén aprobados antes de permitir ensayo
            $reportesAprobados = ServicioReporte::where('usuario_id', $request->usuario_id)
                ->where('estado', 'APROBADO')
                ->count();

            if ($reportesAprobados < 3) {
                return response()->json([
                    'error' => 'Debes tener los 3 reportes aprobados para subir el Ensayo Final.'
                ], 403);
            }

            $rutaEnsayo = $request->file('archivo')->store('servicio_social/ensayos', 'public');
            ServicioDocumento::updateOrCreate(
                ['usuario_id' => $request->usuario_id, 'tipo_documento' => 'Ensayo Final'],
                ['ruta_archivo' => $rutaEnsayo, 'estado' => 'EN_REVISION']
            );
            return response()->json(['message' => 'Ensayo final subido correctamente. Pendiente de revisión.']);
        }

        // --- Caso: Es un Reporte Bimestral ---
        $request->validate([
            'reporte_id' => 'required|exists:servicio_reportes,id',
            'archivo'    => 'required|file|mimes:pdf|max:5120',
        ]);

        $reporte = ServicioReporte::findOrFail($request->reporte_id);

        if (!in_array($reporte->estado, ['ACTIVO', 'RECHAZADO'])) {
            return response()->json(['error' => 'Este reporte no está disponible para entrega.'], 403);
        }

        $rutaReporte = $request->file('archivo')->store('servicio_social/reportes', 'public');

        $reporte->update([
            'ruta_archivo' => $rutaReporte,
            'estado'       => 'EN_REVISION'
        ]);

        return response()->json(['message' => 'Reporte entregado satisfactoriamente. Pendiente de revisión.']);
    }

    /**
     * 4. OBTENER ESTADO DEL ALUMNO
     * Retorna: documentos iniciales, reportes, ensayo final y constancia.
     */
    public function getEstadoServicio($usuarioId)
    {
        // Auto-bloquear reportes vencidos que aún están ACTIVOS
        ServicioReporte::where('usuario_id', $usuarioId)
            ->where('estado', 'ACTIVO')
            ->where('fecha_limite', '<', now())
            ->update(['estado' => 'BLOQUEADO_VENCIDO']);

        $docsSubidos = ServicioDocumento::where('usuario_id', $usuarioId)
            ->whereIn('tipo_documento', $this->docsRequeridos)
            ->get();

        $reportes = ServicioReporte::where('usuario_id', $usuarioId)
            ->orderBy('numero_reporte', 'asc')
            ->get();

        $ensayo = ServicioDocumento::where('usuario_id', $usuarioId)
            ->where('tipo_documento', 'Ensayo Final')
            ->first();

        $constancia = ServicioDocumento::where('usuario_id', $usuarioId)
            ->where('tipo_documento', 'Constancia de Liberacion')
            ->first();

        return response()->json([
            'documentacion' => $docsSubidos,
            'reportes'      => $reportes,
            'ensayo'        => $ensayo,
            'constancia'    => $constancia,
            'puedeSubirEnsayo' => ($reportes->where('estado', 'APROBADO')->count() === 3)
        ]);
    }

    /**
     * 5. DESBLOQUEAR REPORTE (ADMIN)
     * El admin asigna una nueva fecha límite para que el alumno pueda subir.
     */
    public function desbloquearReporteAdmin(Request $request)
    {
        $request->validate([
            'reporte_id' => 'required|exists:servicio_reportes,id',
            'nueva_fecha' => 'required|date|after:now'
        ]);

        $reporte = ServicioReporte::find($request->reporte_id);
        $reporte->update([
            'estado'           => 'ACTIVO',
            'fecha_limite'     => $request->nueva_fecha,
            'comentarios_admin' => 'Plazo extendido por administración el ' . now()->format('d/m/Y') . '.'
        ]);

        return response()->json(['message' => 'Reporte desbloqueado correctamente. Nueva fecha asignada.']);
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

        // Ensure storage symlink exists
        $publicStorage = public_path('storage');
        if (!is_link($publicStorage) && !file_exists($publicStorage)) {
            // Create symlink if missing
            \Artisan::call('storage:link');
        }

        if ($ruta && Storage::disk('public')->exists($ruta)) {
            $file = Storage::disk('public')->get($ruta);
            $type = Storage::disk('public')->mimeType($ruta);
            $disposition = 'inline; filename="documento.' . pathinfo($ruta, PATHINFO_EXTENSION) . '"';

            return response($file, 200)
                ->header('Content-Type', $type)
                ->header('Content-Disposition', $disposition)
                ->header('Access-Control-Allow-Origin', 'http://localhost:4200')
                ->header('X-Frame-Options', 'ALLOWALL');
        }

        return response()->json(['error' => 'Archivo no encontrado.'], 404);
    }

    /**
     * 7. VALIDAR Y TERMINAR (mantener por compatibilidad)
     */
    public function validarYTerminar(Request $request)
    {
        $request->validate(['usuario_id' => 'required']);
        $alumno = Usuario::where('id_usuario', $request->usuario_id)->first();
        if (!$alumno) return response()->json(['error' => 'Usuario no encontrado'], 404);
        return response()->json(['message' => 'Proceso validado correctamente.']);
    }

    /**
     * 8. GET ALUMNOS ADMIN (panel de pendientes/bloqueados)
     * Devuelve una lista unificada de items para el panel del admin.
     */
    public function getAlumnosAdmin()
    {
        $items = [];

        // --- Reportes EN_REVISION (para validar) ---
        $reportesRevision = ServicioReporte::where('estado', 'EN_REVISION')
            ->with(['usuario'])
            ->get();

        foreach ($reportesRevision as $rep) {
            if (!$rep->usuario) continue;
            $items[] = [
                'id_entidad'      => $rep->id,
                'tipo_entidad'    => 'reporte',
                'estado'          => 'Entregado',
                'nombre_completo' => $rep->usuario->nombre_completo,
                'numero_control'  => $rep->usuario->numero_control,
                'numero_reporte'  => $rep->numero_reporte,
                'nombreMostrar'   => "REPORTE BIMESTRAL #{$rep->numero_reporte}",
                'url_archivo'     => $rep->ruta_archivo
                    ? url("storage/{$rep->ruta_archivo}")
                    : null,
            ];
        }

        // --- Reportes BLOQUEADO_VENCIDO (para desbloquear) ---
        $reportesBloqueados = ServicioReporte::where('estado', 'BLOQUEADO_VENCIDO')
            ->with(['usuario'])
            ->get();

        foreach ($reportesBloqueados as $rep) {
            if (!$rep->usuario) continue;
            $items[] = [
                'id_entidad'      => $rep->id,
                'tipo_entidad'    => 'reporte',
                'estado'          => 'Bloqueado',
                'nombre_completo' => $rep->usuario->nombre_completo,
                'numero_control'  => $rep->usuario->numero_control,
                'numero_reporte'  => $rep->numero_reporte,
                'nombreMostrar'   => "REPORTE BIMESTRAL #{$rep->numero_reporte}",
                'fecha_limite'    => $rep->fecha_limite,
                'url_archivo'     => null,
            ];
        }

        // --- Ensayos Finales EN_REVISION (para validar) ---
        $ensayosRevision = ServicioDocumento::where('tipo_documento', 'Ensayo Final')
            ->where('estado', 'EN_REVISION')
            ->with(['usuario'])
            ->get();

        foreach ($ensayosRevision as $doc) {
            $usuario = $doc->usuario;
            if (!$usuario) continue;
            $items[] = [
                'id_entidad'      => $doc->id,
                'tipo_entidad'    => 'ensayo',
                'estado'          => 'Entregado',
                'nombre_completo' => $usuario->nombre_completo,
                'numero_control'  => $usuario->numero_control,
                'nombreMostrar'   => 'ENSAYO FINAL',
                'url_archivo'     => $doc->ruta_archivo
                    ? url("storage/{$doc->ruta_archivo}")
                    : null,
            ];
        }

        // --- Ensayos Finales APROBADOS sin constancia generada (para enviar carta) ---
        $usuariosConConstancia = ServicioDocumento::where('tipo_documento', 'Constancia de Liberacion')
            ->pluck('usuario_id')
            ->toArray();

        $ensayosAprobados = ServicioDocumento::where('tipo_documento', 'Ensayo Final')
            ->where('estado', 'APROBADO')
            ->with(['usuario'])
            ->get();

        foreach ($ensayosAprobados as $doc) {
            // Verificar que no tenga constancia ya generada using pre-fetched array
            if (in_array($doc->usuario_id, $usuariosConConstancia)) {
                continue;
            }

            $usuario = $doc->usuario;
            if (!$usuario) continue;

            $items[] = [
                'id_entidad'      => $doc->id,
                'tipo_entidad'    => 'carta_pendiente',
                'estado'          => 'Pendiente',
                'nombre_completo' => $usuario->nombre_completo,
                'numero_control'  => $doc->usuario_id,
                'usuario_id'      => $doc->usuario_id,
                'nombreMostrar'   => 'CARTA DE TÉRMINO PENDIENTE',
                'url_archivo'     => null,
            ];
        }

        return response()->json($items);
    }

    /**
     * 9. VALIDAR REPORTE ADMIN
     * Aprueba o rechaza un reporte. Si aprueba R1/R2, activa el siguiente.
     */
    public function validarReporteAdmin(Request $request)
    {
        $request->validate([
            'reporte_id'    => 'required',
            'accion'        => 'required|in:APROBADO,RECHAZADO',
            'observaciones' => 'nullable|string'
        ]);

        $reporte = ServicioReporte::find($request->reporte_id);
        if (!$reporte) return response()->json(['error' => 'Reporte no encontrado'], 404);

        $reporte->update([
            'estado'           => $request->accion,
            'comentarios_admin' => $request->observaciones
        ]);

        // Si se aprueba y hay un siguiente reporte, activarlo
        if ($request->accion === 'APROBADO' && $reporte->numero_reporte < 3) {
            ServicioReporte::where('usuario_id', $reporte->usuario_id)
                ->where('numero_reporte', $reporte->numero_reporte + 1)
                ->where('estado', 'INACTIVO')
                ->update([
                    'estado'               => 'ACTIVO',
                    'fecha_inicio_periodo' => now(),
                    'fecha_limite'         => now()->addMonths(2),
                ]);
        }

        return response()->json(['message' => 'Validación procesada correctamente.']);
    }

    /**
     * 10. VALIDAR DOCUMENTO / ENSAYO FINAL (Admin)
     * Para el ensayo final: si se aprueba, aparecerá en panel de "Cartas Pendientes".
     */
    public function validarDocumento(Request $request)
    {
        $request->validate([
            'documento_id'  => 'required|exists:servicio_documentos,id',
            'accion'        => 'required|in:APROBADO,RECHAZADO',
            'observaciones' => 'nullable|string'
        ]);

        $doc = ServicioDocumento::find($request->documento_id);
        if (!$doc) return response()->json(['error' => 'Documento no encontrado'], 404);

        $doc->update([
            'estado'           => $request->accion,
            'comentarios_admin' => $request->observaciones
        ]);

        $mensaje = 'Documento validado correctamente.';
        if ($doc->tipo_documento === 'Ensayo Final' && $request->accion === 'APROBADO') {
            $mensaje = 'Ensayo Final aprobado. Ahora puedes enviar la Carta de Término al alumno desde el panel de cartas pendientes.';
        }

        return response()->json(['message' => $mensaje]);
    }

    /**
     * ACTUALIZAR LOGOS INSTITUCIONALES (Admin)
     * Permite subir nuevas imágenes de encabezado y pie de página
     * para la carta de término de servicio social.
     */
    public function actualizarLogos(Request $request)
    {
        $request->validate([
            'header_banner' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'footer_banner' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        if (!$request->hasFile('header_banner') && !$request->hasFile('footer_banner')) {
            return response()->json(['error' => 'Debes subir al menos una imagen (encabezado o pie de página).'], 422);
        }

        $logosPath = public_path('images/logos');
        $actualizados = [];

        // Asegurar que el directorio existe
        if (!is_dir($logosPath)) {
            mkdir($logosPath, 0755, true);
        }

        if ($request->hasFile('header_banner')) {
            $file = $request->file('header_banner');

            // Crear respaldo del anterior si existe
            $existingPath = $logosPath . '/header_banner.png';
            if (file_exists($existingPath)) {
                $backupName = 'header_banner_backup_' . date('Ymd_His') . '.png';
                copy($existingPath, $logosPath . '/' . $backupName);
            }

            // Guardar la nueva imagen como header_banner.png
            $file->move($logosPath, 'header_banner.png');
            $actualizados[] = 'Encabezado (header_banner.png)';
            \Log::info('LOGOS: Encabezado institucional actualizado por administrador.');
        }

        if ($request->hasFile('footer_banner')) {
            $file = $request->file('footer_banner');

            // Crear respaldo del anterior si existe
            $existingPath = $logosPath . '/footer_banner.png';
            if (file_exists($existingPath)) {
                $backupName = 'footer_banner_backup_' . date('Ymd_His') . '.png';
                copy($existingPath, $logosPath . '/' . $backupName);
            }

            // Guardar la nueva imagen como footer_banner.png
            $file->move($logosPath, 'footer_banner.png');
            $actualizados[] = 'Pie de página (footer_banner.png)';
            \Log::info('LOGOS: Pie de página institucional actualizado por administrador.');
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Logos actualizados correctamente: ' . implode(', ', $actualizados) . '.',
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * 11. ENVIAR CARTA DE TÉRMINO DE SERVICIO SOCIAL (Admin)
     * Se llama desde el dashboard del admin cuando hace clic en "Enviar Carta".
     * Genera el HTML oficial de la constancia y lo guarda para el alumno.
     */
    public function enviarCartaTermino(Request $request)
    {
        $request->validate([
            'usuario_id'        => 'required',
            'nombre_dependencia' => 'nullable|string',
            'horas'             => 'nullable|integer',
            'folio_num'         => 'nullable|string',
        ]);

        $usuarioId = $request->usuario_id;

        // Buscar al usuario por número de control
        $alumno = Usuario::where('numero_control', $usuarioId)->first();
        if (!$alumno) {
            $alumno = Usuario::where('id_usuario', $usuarioId)->first();
        }
        if (!$alumno) return response()->json(['error' => 'Usuario no encontrado'], 404);

        // Verificar que el ensayo esté aprobado
        $ensayo = ServicioDocumento::where('usuario_id', $usuarioId)
            ->where('tipo_documento', 'Ensayo Final')
            ->where('estado', 'APROBADO')
            ->first();

        if (!$ensayo) {
            return response()->json(['error' => 'El Ensayo Final no ha sido aprobado aún.'], 403);
        }

        // Obtener datos del periodo del servicio social
        $primerReporte = ServicioReporte::where('usuario_id', $usuarioId)->where('numero_reporte', 1)->first();
        $tercerReporte = ServicioReporte::where('usuario_id', $usuarioId)->where('numero_reporte', 3)->first();

        $fechaInicioObj   = $primerReporte ? Carbon::parse($primerReporte->fecha_inicio_periodo) : Carbon::now()->subMonths(6);
        $fechaFinObj      = $tercerReporte  ? Carbon::parse($tercerReporte->fecha_limite)         : Carbon::now();
        $fechaExpedicion  = Carbon::now()->subDays(5);
        $fechaCarta       = $fechaFinObj->copy()->addDays(5);

        // Datos de la carta
        $nombreAlumno   = strtoupper($alumno->nombre_completo);
        $numeroControl  = $alumno->numero_control;
        $dependencia    = $request->nombre_dependencia ?? '________________________________________';
        $horas          = $request->horas ?? 500;

        // Obtener nombre de carrera desde la relación
        $carreraObj = $alumno->carrera;
        $carrera    = $carreraObj ? strtoupper($carreraObj->nombre) : 'INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIONES';

        // Formateo de fechas en español
        $periodoInicio = strtoupper($fechaInicioObj->day . ' DE ' . $this->getMesEspanol($fechaInicioObj->format('m')) . ' DEL ' . $fechaInicioObj->year);
        $periodoFin    = strtoupper($fechaFinObj->day . ' DE ' . $this->getMesEspanol($fechaFinObj->format('m')) . ' DEL ' . $fechaFinObj->year);
        $periodo       = "{$periodoInicio} AL {$periodoFin}";

        $diaExp  = $fechaExpedicion->day;
        $mesExp  = strtoupper($this->getMesEspanol($fechaExpedicion->format('m')));
        $anioExp = $fechaExpedicion->year;

        // Fecha para "Se extiende" (+5 días según normativa)
        $diaCartaExp  = $fechaCarta->day;
        $mesCartaExp  = strtoupper($this->getMesEspanol($fechaCarta->format('m')));
        $anioCartaExp = $fechaCarta->year;

        // ── CARGAR IMÁGENES REALES COMO BASE64 (no depender de URLs) ──
        $headerPath = public_path('images/logos/header_banner.png');
        $footerPath = public_path('images/logos/footer_banner.png');

        // Validar que las imágenes existan físicamente
        if (!file_exists($headerPath)) {
            \Log::error("CARTA DE TÉRMINO: No se encontró header_banner.png en: {$headerPath}");
            return response()->json(['error' => 'No se encontró la imagen del encabezado institucional (header_banner.png). Verifica que exista en public/images/logos/'], 500);
        }
        if (!file_exists($footerPath)) {
            \Log::error("CARTA DE TÉRMINO: No se encontró footer_banner.png en: {$footerPath}");
            return response()->json(['error' => 'No se encontró la imagen del pie de página institucional (footer_banner.png). Verifica que exista en public/images/logos/'], 500);
        }

        // Codificar imágenes en base64 para incrustarlas directamente en el HTML
        $headerBase64 = base64_encode(file_get_contents($headerPath));
        $footerBase64 = base64_encode(file_get_contents($footerPath));
        $headerDataUri = "data:image/png;base64,{$headerBase64}";
        $footerDataUri = "data:image/png;base64,{$footerBase64}";

        // Número de folio (ej: DGTV/ITGAM/0840/2026)
        $folioNum = $request->folio_num;
        if (empty($folioNum)) {
            $folioNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } else {
            $folioNum = str_pad($folioNum, 4, '0', STR_PAD_LEFT);
        }
        $folio = 'DGTV/ITGAM/' . $folioNum . '/' . $anioExp;

        // ── HTML OFICIAL DE LA CARTA DE TÉRMINO ──
        $htmlContent = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                @page {
                    size: letter;
                    margin: 0;
                }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                html, body {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Arial', sans-serif;
                    font-size: 10.5pt;
                    color: #000;
                    background: #fff;
                    position: relative;
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                }

                /* ── HEADER BANNER ── */
                .header-banner {
                    width: 100%;
                    padding: 0;
                    margin: 0;
                }
                .header-banner img {
                    width: 100%;
                    height: auto;
                    display: block;
                }

                /* ── CONTENIDO PRINCIPAL ── */
                .contenido {
                    flex: 1;
                    padding: 10px 50px 10px 60px;
                    line-height: 1.5;
                }

                /* ── DATOS DEL DOCUMENTO ── */
                .datos-doc {
                    text-align: right;
                    margin-top: 15px;
                    margin-bottom: 5px;
                    font-size: 9.5pt;
                }
                .ciudad-fecha {
                    font-weight: bold;
                }
                .folio {
                    font-weight: bold;
                    margin-top: 1px;
                }

                /* ── ASUNTO ── */
                .asunto-bloque {
                    text-align: right;
                    font-size: 9.5pt;
                    margin-top: 12px;
                    margin-bottom: 20px;
                    font-weight: bold;
                    line-height: 1.25;
                }

                /* ── CUERPO ── */
                .a-quien {
                    font-weight: bold;
                    font-size: 10.5pt;
                    margin: 20px 0 10px 0;
                }
                .intro {
                    margin-bottom: 10px;
                }
                .cuerpo {
                    text-align: justify;
                    margin-bottom: 10px;
                    line-height: 1.5;
                }
                .legal {
                    text-align: justify;
                    margin-bottom: 10px;
                    line-height: 1.5;
                }
                .cierre {
                    text-align: justify;
                    margin-bottom: 20px;
                    line-height: 1.5;
                }

                /* ── ATENTAMENTE Y FIRMAS ── */
                .atentamente {
                    text-align: center;
                    font-size: 11pt;
                    font-weight: bold;
                    letter-spacing: 3px;
                    margin: 20px 0 40px;
                }
                .firmas {
                    display: flex;
                    justify-content: space-between;
                    text-align: center;
                    margin-top: 30px;
                    margin-bottom: 15px;
                }
                .firma-bloque {
                    width: 45%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }
                .firma-linea {
                    border-top: 1px solid #000;
                    width: 80%;
                    margin-bottom: 6px;
                }
                .firma-nombre {
                    font-weight: bold;
                    font-size: 9.5pt;
                    line-height: 1.25;
                }
                .firma-cargo {
                    font-size: 8pt;
                    color: #000;
                    margin-top: 3px;
                    line-height: 1.3;
                }

                /* ── C.C.P. ── */
                .ccp-block {
                    margin-top: 25px;
                    font-size: 7.5pt;
                    color: #000;
                    line-height: 1.3;
                    text-align: left;
                }

                /* ── FOOTER BANNER ── */
                .footer-banner {
                    width: 100%;
                    padding: 0;
                    margin-top: auto;
                }
                .footer-banner img {
                    width: 100%;
                    height: auto;
                    display: block;
                }
            </style>
        </head>
        <body>

            <!-- BANNER SUPERIOR (imagen real completa) -->
            <div class='header-banner'>
                <img src='{$headerDataUri}' alt='Encabezado Institucional'>
            </div>

            <!-- CONTENIDO DE LA CARTA -->
            <div class='contenido'>

                <!-- DATOS DEL DOCUMENTO -->
                <div class='datos-doc'>
                    <div class='ciudad-fecha'>CIUDAD DE MÉXICO, {$diaExp}/{$mesExp}/{$anioExp}</div>
                    <div class='folio'>{$folio}</div>
                </div>

                <!-- ASUNTO -->
                <div class='asunto-bloque'>
                    <div>ASUNTO:</div>
                    <div>CONSTANCIA DE</div>
                    <div>TERMINACIÓN DE</div>
                    <div>SERVICIO SOCIAL</div>
                </div>

                <!-- CUERPO -->
                <div class='a-quien'>A QUIEN CORRESPONDA:</div>

                <div class='intro'>Por medio de la presente se hace constar que:</div>

                <div class='cuerpo'>
                    Según documentos que obran en los archivos de esta Institución, a el C.
                    <strong>{$nombreAlumno}</strong>, con número de control
                    <strong>{$numeroControl}</strong> de la carrera de
                    <strong>{$carrera}</strong> realizó su Servicio Social en la siguiente
                    dependencia; <strong>{$dependencia}</strong>
                    desarrollando las siguientes
                    actividades: <strong>ACTIVIDADES ADMINISTRATIVAS Y DE APOYO AL ÁREA ASIGNADA</strong>,
                    cubriendo un total de <strong>{$horas} horas</strong>, durante el periodo comprendido
                    <strong>{$periodo}</strong> con un nivel de desempeño <strong>EXCELENTE</strong>.
                </div>

                <div class='legal'>
                    Este servicio social fue realizado de acuerdo a lo establecido en la
                    <strong>Ley Reglamentaria del Artículo 5o. Constitucional</strong> relativo al
                    ejercicio de las <strong>Profesiones y los Reglamentos</strong> que rigen a la
                    normativa emitida por el <strong>Tecnológico Nacional de México</strong>.
                </div>

                <div class='cierre'>
                    Se extiende la presente para los fines legales que al interesado convengan, en la
                    ciudad de <strong>México</strong>, a los <strong>{$diaCartaExp}</strong> días del mes de
                    <strong>{$mesCartaExp}</strong> del año <strong>{$anioCartaExp}</strong>.
                </div>

                <div class='atentamente'>A t e n t a m e n t e</div>

                <!-- FIRMAS -->
                <div class='firmas'>
                    <div class='firma-bloque'>
                        <div class='firma-linea'></div>
                        <div class='firma-nombre'>LIC. ROCÍO ESPINAL DÍAZ</div>
                        <div class='firma-cargo'>JEFA DEL DEPARTAMENTO DE<br>GESTIÓN TECNOLÓGICA Y VINCULACIÓN</div>
                    </div>
                    <div class='firma-bloque'>
                        <div class='firma-linea'></div>
                        <div class='firma-nombre'>DR. ARTURO ERNESTO MARES GARDEA</div>
                        <div class='firma-cargo'>DIRECTOR DEL INSTITUTO TECNOLÓGICO<br>DE GUSTAVO A. MADERO</div>
                    </div>
                </div>

                <!-- C.C.P. -->
                <div class='ccp-block'>
                    C.c.p. -Servicios Escolares.-Expediente del (la) estudiante<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-Archivo.
                </div>

            </div>

            <!-- BANNER INFERIOR (imagen real completa) -->
            <div class='footer-banner'>
                <img src='{$footerDataUri}' alt='Pie de Página Institucional'>
            </div>

        </body>
        </html>
        ";

        // Guardar la carta en BD
        ServicioDocumento::updateOrCreate(
            [
                'usuario_id'     => $usuarioId,
                'tipo_documento' => 'Constancia de Liberacion'
            ],
            [
                'ruta_archivo'     => $htmlContent,
                'estado'           => 'APROBADO',
                'comentarios_admin' => 'Carta de Término de Servicio Social generada y enviada el ' . now()->format('d/m/Y') . '.'
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => "Carta de término enviada correctamente al alumno {$alumno->nombre_completo}."
        ]);
    }

    /**
     * 12. OBTENER ALUMNOS COMPLETADOS (Admin) - para historial
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
            ->with(['servicioDocumentos', 'carrera'])
            ->get();

        return response()->json($alumnosCompletados->map(function ($alumno) {
            $carreraObj = $alumno->carrera;
            return [
                'id_usuario'     => $alumno->id_usuario,
                'nombre_completo' => $alumno->nombre_completo,
                'carrera'        => $carreraObj ? $carreraObj->nombre : 'INGENIERÍA EN TECNOLOGÍAS DE LA INFORMACIÓN Y COMUNICACIONES',
            ];
        }));
    }

    /**
     * Helper: Nombre del mes en español
     */
    private function getMesEspanol($mesNum): string
    {
        $meses = [
            '01' => 'ENERO',    '02' => 'FEBRERO',   '03' => 'MARZO',
            '04' => 'ABRIL',    '05' => 'MAYO',       '06' => 'JUNIO',
            '07' => 'JULIO',    '08' => 'AGOSTO',     '09' => 'SEPTIEMBRE',
            '10' => 'OCTUBRE',  '11' => 'NOVIEMBRE',  '12' => 'DICIEMBRE'
        ];
        return $meses[$mesNum] ?? 'DICIEMBRE';
    }
}