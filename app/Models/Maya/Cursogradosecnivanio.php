<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Maya\Bimestre;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Materia;

class Cursogradosecnivanio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'maya_curso_grado_sec_niv_anios';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'docente_designado_id',
        'grado_id',
        'anio',
        'materia_id',
    ];
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }
    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_designado_id');
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
    public function bimestres()
    {
        return $this->hasMany(Bimestre::class, 'curso_grado_sec_niv_anio_id');
    }
    public function getHighestBimestreWithAsistencia()
    {
        return $this->bimestres()
            ->whereHas('asistencias')
            ->orderBy('id', 'desc')
            ->first()
            ?->id;
    }
}
