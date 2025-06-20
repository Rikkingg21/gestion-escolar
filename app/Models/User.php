<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'dni', 'nombre_usuario', 'nombre', 'apellido_paterno',
        'apellido_materno', 'email', 'password', 'foto_path', 'estado'
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

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDirector()
    {
        return $this->hasRole('director');
    }

    // ... otros métodos para verificar roles
}
