<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
   
public $incrementing = true; // Agrega esta
protected $keyType = 'int';  // Agrega esta

    protected $fillable = [
        'numero_control',
        'nombre_completo',
        'correo',
        'password_hash',
        'rol',
        'is_active',
        'carrera_id' 
    ];

    protected $hidden = [
        'password_hash',
    ];


    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id', 'id_carrera');
    }
}