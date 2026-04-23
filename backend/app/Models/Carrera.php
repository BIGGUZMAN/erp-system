<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';          // nombre de la tabla
    protected $primaryKey = 'id_carrera';   // clave primaria personalizada
    public $incrementing = true;            // es autoincremental
    protected $keyType = 'int';             // tipo de la clave

    protected $fillable = ['nombre', 'total_alumnos'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'carrera_id');
    }
}
