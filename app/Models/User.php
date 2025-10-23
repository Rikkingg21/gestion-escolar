<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'dni', 'nombre_usuario', 'nombre', 'apellido_paterno',
        'apellido_materno', 'email', 'password', 'foto_path', 'estado', 'telefono'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class);
    }

    public function docente()
    {
        return $this->hasOne(Docente::class);
    }

    public function apoderado()
    {
        return $this->hasOne(Apoderado::class);
    }

    public function auxiliar()
    {
        return $this->hasOne(Auxiliar::class);
    }

    public function director()
    {
        return $this->hasOne(Director::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('nombre', $role);
        }

        return !! $role->intersect($this->roles)->count();
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', '1');
    }

    public function scopeLectores($query)
    {
        return $query->where('estado', '2');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', '0');
    }
    public function canAccessModule($moduleName)
    {
        $currentRole = session('current_role');

        if (!$currentRole) {
            return false;
        }

        return \App\Models\Role::where('nombre', $currentRole)
            ->where('estado', '1')
            ->whereHas('modules', function ($query) use ($moduleName) {
                $query->where('modules.nombre', $moduleName)
                      ->where('modules.estado', '1')
                      ->where('role_modules.estado', '1');
            })->exists();
    }
}
