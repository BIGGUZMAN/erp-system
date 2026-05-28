<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';
    protected $primaryKey = 'id_empresa';

    protected $fillable = [
        'anio',
        'empresa',
        'tipo_empresa',
        'rfc',
        'direccion',
        'tipo_convenio',
        'fecha_firma',
        'vigencia',
        'fecha_termino',
        'convenio_fisico',
        'representante',
        'cargo',
        'contacto',
        'telefono',
        'correo',
        'igem',
        'itics',
        'ilog',
        'ind',
        'idam',
        'ife',
        'proyectos',
        'comentarios'
    ];

    protected $casts = [
        'fecha_firma'   => 'date:Y-m-d',
        'fecha_termino' => 'date:Y-m-d',
        'igem'          => 'boolean',
        'itics'         => 'boolean',
        'ilog'          => 'boolean',
        'ind'           => 'boolean',
        'idam'          => 'boolean',
        'ife'           => 'boolean',
    ];

    // Estatus calculado dinámicamente en tiempo real
    protected $appends = ['estatus'];

    /**
     * Calcula dinámicamente si el convenio está Vigente, Próximo a vencer o Vencido.
     */
    public function getEstatusAttribute(): string
    {
        if (!$this->fecha_termino) {
            return 'Vigente';
        }

        $hoy = now()->startOfDay();
        $termino = \Carbon\Carbon::parse($this->fecha_termino)->startOfDay();

        if ($termino->isPast()) {
            return 'Vencido';
        }

        // Próximo a vencer si faltan 90 días o menos
        $diasRestantes = $hoy->diffInDays($termino, false);
        if ($diasRestantes <= 90) {
            return 'Próximo a vencer';
        }

        return 'Vigente';
    }

    /**
     * Relación con el historial de renovaciones
     */
    public function renovaciones()
    {
        return $this->hasMany(ConvenioRenovacion::class, 'empresa_id', 'id_empresa')
                    ->orderBy('created_at', 'desc');
    }
}
