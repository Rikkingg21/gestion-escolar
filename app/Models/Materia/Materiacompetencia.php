<?php

namespace App\Models\Materia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Materia;
use App\Models\Materia\MateriaCriterio;

class Materiacompetencia extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'materia_competencias';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'materia_id',
        'nombre',
        'descripcion'
    ];
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
    public function materiaCriterio()
    {
        return $this->belongsTo(Materia\MateriaCriterio::class, 'materia_criterio_id');
    }
}
