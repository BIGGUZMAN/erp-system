<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioLiberacion extends Model
{
    use HasFactory;

    protected $table = 'servicio_liberaciones';

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'usuario_id',
        'ruta_carta_liberacion',
        'fecha_emision'
    ];

    /**
     * Casteo de atributos para manejo de fechas con Carbon.
     */
    protected $casts = [
        'fecha_emision' => 'date',
    ];

    /**
     * Relación con el Usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }
}