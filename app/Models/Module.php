<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'modules';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'icono',
        'ruta_base',
        'estado',
    ];

    // Relación con roles a través de role_modules
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_modules')
                    ->withPivot('estado')
                    ->withTimestamps();
    }

    // Relación con excepciones
    public function exceptions()
    {
        return $this->hasMany(Rolemoduleexception::class, 'module_id');
    }

    // Scopes para estado
    public function scopeActivos($query)
    {
        return $query->where('estado', '1');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', '0');
    }

    // Accesor para estado legible
    public function getEstadoTextoAttribute()
    {
        return $this->estado == '1' ? 'Activo' : 'Inactivo';
    }

    // Accesor para badge de estado
    public function getEstadoBadgeAttribute()
    {
        return $this->estado == '1'
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';
    }
}
