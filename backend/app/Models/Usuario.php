<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importante para las relaciones
use App\Notifications\RestablecerPasswordNotification;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    
    public $incrementing = true; 
    protected $keyType = 'int';  

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

    /**
     * Indica a Laravel que use password_hash en lugar de password para Auth
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES AGREGADAS (Para que funcione el ServicioSocialController)
    |--------------------------------------------------------------------------
    */

    /**
 * Relación con los reportes usando el número de control como enlace
 */
public function servicioReportes(): HasMany
{
    // Usamos 'numero_control' como la llave local en lugar de 'id_usuario'
    return $this->hasMany(ServicioReporte::class, 'usuario_id', 'numero_control');
}

public function servicioDocumentos(): HasMany
{
    return $this->hasMany(ServicioDocumento::class, 'usuario_id', 'numero_control');
}

    /**
     * Relación original con Carrera
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id', 'id_carrera');
    }

    /**
     * Obtiene el correo electrónico para la recuperación de contraseña.
     */
    public function getEmailForPasswordReset()
    {
        return $this->correo;
    }

    /**
     * Envía la notificación al canal de correo correspondiente (campo 'correo' en BD).
     */
    public function routeNotificationForMail($notification)
    {
        return $this->correo;
    }

    /**
     * Envía la notificación de restablecimiento de contraseña.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new RestablecerPasswordNotification($token));
    }

    /**
     * Ignora el remember_token ya que la tabla usuarios no tiene esta columna.
     */
    public function setRememberToken($value)
    {
        // No-op
    }
}