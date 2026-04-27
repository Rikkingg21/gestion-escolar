<?php

namespace App\Models\Materia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Materia;
use App\Models\Nota;
use App\Models\Grado;
use App\Models\Materia\Materiacompetencia;
use App\Models\Periodobimestre;

class Materiacriterio extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'materia_criterios';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'materia_competencia_id',
        'materia_id',
        'grado_id',
        'periodo_bimestre_id',
        'anio',
        'bimestre',
        'nombre',
        'descripcion'
    ];
    public function materiaCompetencia()
    {
        return $this->belongsTo(Materiacompetencia::class, 'materia_competencia_id');
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }
    public function periodoBimestre()
    {
        return $this->belongsTo(Periodobimestre::class, 'periodo_bimestre_id');
    }
    // Accessor para obtener el año a través del período
    public function getAnioAttribute()
    {
        return $this->periodoBimestre?->periodo?->anio;
    }

    // Accessor para obtener el bimestre
    public function getBimestreAttribute()
    {
        return $this->periodoBimestre?->bimestre;
    }
}
