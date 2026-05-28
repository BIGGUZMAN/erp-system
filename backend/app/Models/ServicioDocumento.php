<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioDocumento extends Model
{
    use HasFactory;

    // Nombre de la tabla (opcional si sigue la convención, pero mejor asegurar)
    protected $table = 'servicio_documentos';

    /**
     * Los atributos que se pueden asignar masivamente.
     * Esto permite usar ServicioDocumento::create([...]) y $doc->update([...])
     */
    protected $fillable = [
        'usuario_id',
        'tipo_documento',
        'ruta_archivo',
        'estado',
        'comentarios_admin' // <--- MODIFICACIÓN: Permite guardar las observaciones del admin
    ];

    /**
     * Atributos adicionales que se inyectarán automáticamente en el JSON final.
     * Esto soluciona de raíz el valor 'null' en la consola de Angular.
     */
    protected $appends = [
        'observaciones' // <--- MODIFICACIÓN: Declara el puente dinámico para el frontend
    ];

    /**
     * Accesor para simular la propiedad 'observaciones' que busca Angular.
     * Toma el valor real de 'comentarios_admin' y lo mapea al vuelo.
     */
    public function getObservacionesAttribute()
    {
        return $this->comentarios_admin; // <--- MODIFICACIÓN: Retorna el comentario real
    }

    /**
     * Relación inversa con el Usuario.
     * Un documento pertenece a un solo alumno.
     */
    public function usuario()
    {
        // Especificamos 'usuario_id' como la FK y 'numero_control' como la PK en la tabla usuarios
        return $this->belongsTo(Usuario::class, 'usuario_id', 'numero_control');
    }
}