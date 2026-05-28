<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\ConvenioRenovacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    /**
     * Listado de empresas/convenios con filtros de búsqueda y estatus.
     */
    public function index(Request $request)
    {
        $query = Empresa::query();

        // 1. Filtro por Búsqueda de texto (Empresa, RFC, Representante, Contacto)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('empresa', 'like', "%{$search}%")
                  ->orWhere('rfc', 'like', "%{$search}%")
                  ->orWhere('representante', 'like', "%{$search}%")
                  ->orWhere('contacto', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por Tipo de Convenio
        if ($request->filled('tipo_convenio')) {
            $query->where('tipo_convenio', $request->tipo_convenio);
        }

        // 3. Filtro por Estatus (Vigente, Próximo a vencer, Vencido)
        // Debido a que 'estatus' es dinámico, implementamos las condiciones equivalentes en SQL
        if ($request->filled('estatus')) {
            $estatus = $request->estatus;
            $hoy = now()->startOfDay();
            $limiteProximo = now()->addDays(90)->endOfDay();

            if ($estatus === 'Vencido') {
                $query->where('fecha_termino', '<', $hoy);
            } elseif ($estatus === 'Próximo a vencer') {
                $query->whereBetween('fecha_termino', [$hoy, $limiteProximo]);
            } elseif ($estatus === 'Vigente') {
                $query->where(function ($q) use ($limiteProximo) {
                    $q->where('fecha_termino', '>', $limiteProximo)
                      ->orWhereNull('fecha_termino');
                });
            }
        }

        $empresas = $query->orderBy('empresa', 'asc')->get();

        return response()->json($empresas);
    }

    /**
     * Registro de una nueva empresa / convenio.
     */
    public function store(Request $request)
    {
        $request->validate([
            'anio'            => 'required|integer',
            'empresa'         => 'required|string|unique:empresas,empresa',
            'tipo_empresa'    => 'required|string',
            'rfc'             => 'nullable|string|unique:empresas,rfc',
            'tipo_convenio'   => 'required|string',
            'fecha_firma'     => 'nullable|date',
            'vigencia'        => 'required|integer|min:0',
            'fecha_termino'   => 'nullable|date',
            'convenio_fisico' => 'nullable|string',
            'representante'   => 'nullable|string',
            'cargo'           => 'nullable|string',
            'contacto'        => 'nullable|string',
            'telefono'        => 'nullable|string',
            'correo'          => 'nullable|string',
            
            // Booleans para carreras
            'igem'            => 'nullable|boolean',
            'itics'           => 'nullable|boolean',
            'ilog'            => 'nullable|boolean',
            'ind'             => 'nullable|boolean',
            'idam'            => 'nullable|boolean',
            'ife'             => 'nullable|boolean',
            
            'proyectos'       => 'nullable|string',
            'comentarios'     => 'nullable|string',
        ], [
            'empresa.unique' => 'Ya existe una empresa registrada con ese nombre.',
            'rfc.unique'     => 'Ya existe un convenio registrado con ese RFC.',
        ]);

        $data = $request->all();

        // Autocomputar la fecha de término si se firma y tiene vigencia
        if ($request->filled('fecha_firma') && $request->filled('vigencia') && !$request->filled('fecha_termino')) {
            $firma = Carbon::parse($request->fecha_firma);
            $data['fecha_termino'] = $firma->addYears($request->vigencia)->format('Y-m-d');
        }

        $empresa = Empresa::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Empresa registrada correctamente con número de convenio #' . $empresa->id_empresa . '.',
            'data'    => $empresa
        ], 210); // status 201 Created o 200
    }

    /**
     * Detalles de una empresa con su historial de renovaciones.
     */
    public function show($id)
    {
        $empresa = Empresa::with('renovaciones')->findOrFail($id);
        return response()->json($empresa);
    }

    /**
     * Actualización de datos de una empresa existente.
     */
    public function update(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $request->validate([
            'anio'            => 'required|integer',
            'empresa'         => 'required|string|unique:empresas,empresa,' . $id . ',id_empresa',
            'tipo_empresa'    => 'required|string',
            'rfc'             => 'nullable|string|unique:empresas,rfc,' . $id . ',id_empresa',
            'tipo_convenio'   => 'required|string',
            'fecha_firma'     => 'nullable|date',
            'vigencia'        => 'required|integer|min:0',
            'fecha_termino'   => 'nullable|date',
            'convenio_fisico' => 'nullable|string',
            'representante'   => 'nullable|string',
            'cargo'           => 'nullable|string',
            'contacto'        => 'nullable|string',
            'telefono'        => 'nullable|string',
            'correo'          => 'nullable|string',
            
            // Booleans para carreras
            'igem'            => 'nullable|boolean',
            'itics'           => 'nullable|boolean',
            'ilog'            => 'nullable|boolean',
            'ind'             => 'nullable|boolean',
            'idam'            => 'nullable|boolean',
            'ife'             => 'nullable|boolean',
            
            'proyectos'       => 'nullable|string',
            'comentarios'     => 'nullable|string',
        ], [
            'empresa.unique' => 'Ya existe una empresa registrada con ese nombre.',
            'rfc.unique'     => 'Ya existe un convenio registrado con ese RFC.',
        ]);

        $data = $request->all();

        // Autocomputar la fecha de término si cambian firma o vigencia y no se definió término explícito
        if ($request->filled('fecha_firma') && $request->filled('vigencia')) {
            $firma = Carbon::parse($request->fecha_firma);
            $data['fecha_termino'] = $firma->addYears($request->vigencia)->format('Y-m-d');
        }

        $empresa->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Datos de la empresa actualizados correctamente.',
            'data'    => $empresa
        ]);
    }

    /**
     * Renovación de la vigencia del convenio de una empresa.
     */
    public function renovar(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $request->validate([
            'nueva_fecha_firma'   => 'required|date',
            'nueva_vigencia'      => 'required|integer|min:1',
            'nueva_fecha_termino' => 'nullable|date',
            'comentarios'         => 'nullable|string'
        ]);

        // Calcular la nueva fecha de término si no se provee
        $nuevaFirma = Carbon::parse($request->nueva_fecha_firma);
        $nuevaFechaTermino = $request->nueva_fecha_termino 
            ? Carbon::parse($request->nueva_fecha_termino) 
            : $nuevaFirma->copy()->addYears($request->nueva_vigencia);

        DB::beginTransaction();

        try {
            // 1. Guardar registro histórico en convenio_renovaciones
            ConvenioRenovacion::create([
                'empresa_id'             => $empresa->id_empresa,
                'fecha_firma_anterior'   => $empresa->fecha_firma,
                'fecha_termino_anterior' => $empresa->fecha_termino,
                'vigencia_anterior'      => $empresa->vigencia,
                'nueva_fecha_firma'      => $request->nueva_fecha_firma,
                'nueva_fecha_termino'    => $nuevaFechaTermino->format('Y-m-d'),
                'nueva_vigencia'         => $request->nueva_vigencia,
                'comentarios'            => $request->comentarios
            ]);

            // 2. Actualizar las fechas oficiales y vigencia en la tabla empresas
            $empresa->update([
                'fecha_firma'   => $request->nueva_fecha_firma,
                'fecha_termino' => $nuevaFechaTermino->format('Y-m-d'),
                'vigencia'      => $request->nueva_vigencia,
                'comentarios'   => $request->comentarios ? ($empresa->comentarios . "\n\n[RENOVACIÓN " . now()->format('d/m/Y') . "]: " . $request->comentarios) : $empresa->comentarios
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Convenio renovado correctamente con fecha de término hasta ' . $nuevaFechaTermino->format('d/m/Y') . '.',
                'data'    => $empresa
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'server_error',
                'message' => 'No se pudo procesar la renovación de vigencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminación de un convenio y sus registros históricos asociados.
     */
    public function destroy($id)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Convenio con la empresa eliminado correctamente.'
        ]);
    }
}
