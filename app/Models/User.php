<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, RegistraAuditoria;

    protected string $moduloAuditoria = 'usuarios';

    /** El inicio de sesión actualiza estos campos; no se registran en auditoría. */
    protected array $auditoriaIgnorar = ['ultimo_acceso', 'ip_ultimo_acceso', 'remember_token'];

    const ESTADOS = [
        'activo'     => 'Activo',
        'inactivo'   => 'Inactivo',
        'suspendido' => 'Suspendido',
    ];

    /** Nombre legible de cada rol del sistema. */
    const ROLES = [
        'super-admin'  => 'Superadministrador',
        'admin'        => 'Administrador',
        'contador'     => 'Contador',
        'asistente'    => 'Asistente',
        'visualizador' => 'Visualizador',
    ];

    protected $fillable = [
        'name', 'apellidos', 'email', 'dni', 'telefono',
        'cargo', 'area', 'estado', 'avatar', 'password',
        'ultimo_acceso', 'ip_ultimo_acceso',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_acceso'     => 'datetime',
        'password'          => 'hashed',
    ];

    public static function nombreRol(string $rol): string
    {
        return self::ROLES[$rol] ?? ucfirst(str_replace('-', ' ', $rol));
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->name} {$this->apellidos}");
    }

    public function getInicialesAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->name, 0, 1) . mb_substr((string) $this->apellidos, 0, 1));
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'activo'     => 'success',
            'suspendido' => 'danger',
            default      => 'secondary',
        };
    }

    /** Nombre legible del rol principal del usuario. */
    public function getRolPrincipalAttribute(): string
    {
        $rol = $this->getRoleNames()->first();

        return $rol ? self::nombreRol($rol) : 'Sin rol';
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function vehiculosAsignados()
    {
        return $this->hasMany(Vehiculo::class, 'responsable_id');
    }

    public function notificacionesSistema()
    {
        return $this->hasMany(NotificacionSistema::class);
    }

    public function notificacionesNoLeidas()
    {
        return $this->notificacionesSistema()->where('leida', false);
    }
}
