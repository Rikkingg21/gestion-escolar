<?php

namespace App\Models;

use App\Models\Materia\Materiacriterio;
use App\Models\Maya\Bimestre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nota extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'estudiante_notas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'estudiante_id',
        'materia_criterio_id',
        'bimestre_id',
        'publico',
        'nota',

    ];
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    public function criterio()
    {
        return $this->belongsTo(Materiacriterio::class, 'materia_criterio_id');
    }
    public function bimestre()
    {
        return $this->belongsTo(Bimestre::class, 'bimeste_id');
    }
}
