<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelIngles extends Model
{
    use HasFactory;

    protected $table = 'niveles_ingles';
    protected $fillable = ['numero', 'nombre', 'clasificacion'];

    public function inscripciones()
    {
        return $this->hasMany(InscripcionIngles::class, 'nivel_id');
    }
}
