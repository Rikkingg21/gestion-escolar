<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rolemodule extends Model
{
    use HasFactory;

    protected $table = 'role_modules';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'role_id',
        'module_id',
        'estado',
    ];

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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', '1');
    }
}
