<?php

namespace App\Models\Materia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Materia;
use App\Models\Materia\Materiacompetencia;

class Materiacriterio extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'materia_criterios';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'materia_competencia_id',
        'nombre',
        'descripcion'
    ];
    public function materiaCompetencia()
    {
        return $this->belongsTo(Materiacompetencia::class, 'materia_competencia_id');
    }
}
