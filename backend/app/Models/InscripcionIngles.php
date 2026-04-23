<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionIngles extends Model
{
    use HasFactory;

    protected $table = 'inscripciones_ingles';

    protected $fillable = [
        'usuario_id', 
        'nivel_id', 
        'ciclo_escolar', 
        'modalidad',
        'grupo', // <--- Campo nuevo agregado
        'duracion', 
        'estado_pago', 
        'calificacion_final',
        'estado_academico', 
        'ruta_comprobante'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    public function nivel()
    {
        return $this->belongsTo(NivelIngles::class, 'nivel_id');
    }
}