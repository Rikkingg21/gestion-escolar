<?php

namespace App\Models\Materia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Materia;
use App\Models\Materia\Materiacompetencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recuperacioncompetencia extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'estudiante_recuperacion_competencias';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'estudiante_id',
        'materia_competencia_id',
        'materia_id',
        'periodo_id',
        'docente_id',
        'nivel_logro_inicial',
        'nivel_logro_final',
        //'modalidad',
    ];
    public function estudiante()
    {
        return $this->belongsTo(\App\Models\Estudiante::class, 'estudiante_id');
    }
    public function materiaCompetencia()
    {
        return $this->belongsTo(Materiacompetencia::class, 'materia_competencia_id');
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
    public function periodo()
    {
        return $this->belongsTo(\App\Models\Periodo::class, 'periodo_id');
    }
    public function docente()
    {
        return $this->belongsTo(\App\Models\Docente::class, 'docente_id');
    }
}
