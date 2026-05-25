<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioReporte extends Model
{
    use HasFactory;

    protected $table = 'servicio_reportes';

    protected $fillable = [
        'usuario_id',
        'numero_reporte',
        'fecha_inicio_periodo',
        'fecha_limite',
        'estado',
        'ruta_archivo',
        'comentarios_admin'
    ];

    /**
     * Relación con el Usuario.
     * usuario_id almacena el numero_control del alumno.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'numero_control');
    }
}