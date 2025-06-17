<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'dni',
        'nombre_usuario',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'password',
        'foto_path',
        'estado',
        'remember_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}";
    }
    public function getRoleColorAttribute()
    {
        $colors = [
            'admin' => 'danger',
            'director' => 'warning',
            'docente' => 'success',
            'auxiliar' => 'info',
            'estudiante' => 'primary',
            'apoderado' => 'secondary',
        ];

        return $colors[$this->role] ?? 'light';
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('nombre', $roleName)->exists();
    }

    public function hasAnyRole(array $roles)
    {
        return $this->roles()->whereIn('nombre', $roles)->exists();
    }

    public function assignRole($roleName)
    {
        $role = Role::firstOrCreate(['nombre' => $roleName]);
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function getRoleAttribute()
    {
        return session('current_role') ?? $this->roles->first()->nombre ?? null;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->roles)) {
                throw new \Exception('El usuario debe tener al menos un rol asignado');
            }
        });
    }
}
