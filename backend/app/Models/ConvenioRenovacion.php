<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConvenioRenovacion extends Model
{
    protected $table = 'convenio_renovaciones';

    protected $fillable = [
        'empresa_id',
        'fecha_firma_anterior',
        'fecha_termino_anterior',
        'vigencia_anterior',
        'nueva_fecha_firma',
        'nueva_fecha_termino',
        'nueva_vigencia',
        'comentarios'
    ];

    protected $casts = [
        'fecha_firma_anterior'   => 'date:Y-m-d',
        'fecha_termino_anterior' => 'date:Y-m-d',
        'nueva_fecha_firma'      => 'date:Y-m-d',
        'nueva_fecha_termino'    => 'date:Y-m-d',
    ];

    /**
     * Relación inversa con la Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id_empresa');
    }
}
