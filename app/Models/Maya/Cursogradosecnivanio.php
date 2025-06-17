<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->belongsTo(\App\Models\Grado::class, 'grado_id');
    }
    public function docente()
    {
        return $this->belongsTo(\App\Models\Docente::class, 'docente_designado_id');
    }
    public function materia()
    {
        return $this->belongsTo(\App\Models\Materia::class, 'materia_id');
    }
}
