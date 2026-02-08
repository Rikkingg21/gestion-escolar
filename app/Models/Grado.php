<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Asistencia\Asistencia;
use App\Models\Asistencia\Asistencia as AsistenciaAsistencia;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'grados';
    protected $primaryKey = 'id';
    public $timestamps = true;
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

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'grado_id');
    }
    public function estudiante()
    {
        return $this->hasMany(Estudiante::class)->where('estado', 1);
    }
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'grado_id');
    }
    public function cursosMalla()
    {
        return $this->hasMany(\App\Models\Maya\Cursogradosecnivanio::class, 'grado_id');
    }
    public function getGradoSeccionAttribute()
    {
        return $this->grado . $this->seccion;
    }
}
