<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grado extends Model
{
    use HasFactory;

    protected $table = 'grados';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'grado',
        'seccion',
        'nivel',
        'estado',
    ];

    // Accesor para nombre completo
    public function getNombreCompletoAttribute()
    {
        return "{$this->grado}° '{$this->seccion}' - {$this->nivel}";
    }

    // Scope para filtrar por nivel
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }
}
