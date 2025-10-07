<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
    public function getColorAttribute()
    {
        $colors = [
            'admin' => 'danger',
            'director' => 'warning',
            'coordinador' => 'dark',
            'docente' => 'success',
            'auxiliar' => 'info',
            'estudiante' => 'primary',
            'apoderado' => 'secondary'
        ];

        return $colors[$this->nombre] ?? 'light';
    }
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'role_modules')
                    ->withPivot('estado')
                    ->withTimestamps();
    }

    // Relación con excepciones
    public function moduleExceptions()
    {
        return $this->hasMany(Rolemoduleexception::class, 'role_id');
    }

    // Scope para roles activos
    public function scopeActivos($query)
    {
        return $query->where('estado', '1');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', '0');
    }
}
