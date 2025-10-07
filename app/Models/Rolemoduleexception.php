<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rolemoduleexception extends Model
{
    use HasFactory;

    protected $table = 'role_module_exceptions';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'role_id',
        'module_id',
        'tipo_accion', // "A"dd, "R"emove
        'estado',
    ];

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con rol
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relación con módulo
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    // Accesor para tipo de acción legible
    public function getTipoAccionTextoAttribute()
    {
        return $this->tipo_accion == 'A' ? 'Agregar' : 'Quitar';
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', '1');
    }
}
