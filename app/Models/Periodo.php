<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Periodo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'periodos';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'estado',
        'anio',
        'fecha_inicio',
        'fecha_fin',
        'tipo_periodo',
        'descripcion',
    ];
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'periodo_id');
    }
    public function periodobimestres()
    {
        return $this->hasMany(Periodobimestre::class, 'periodo_id');
    }
}
