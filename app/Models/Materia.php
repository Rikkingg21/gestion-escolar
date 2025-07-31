<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Materia extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'materias';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'estado',
    ];
    public function materiaCriterio()
    {
        return $this->belongsTo(Materia\MateriaCriterio::class, 'materia_id');
    }
    public function materiaCompetencia()
    {
        return $this->hasMany(Materia\Materiacompetencia::class, 'materia_id');
    }
}
