<?php

namespace App\Models\Asistencia;

use App\Models\Asistencia\Tipoasistencia;
use App\Models\Grado;
use App\Models\Estudiante;
//use App\Models\Maya\Bimestre;
use App\Models\Periodobimestre;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asistencia extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'estudiante_asistencias';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'estudiante_id',
        'grado_id',
        //'bimestre',
        'tipo_asistencia_id',
        'periodo_id',
        'periodobimestre_id',
        'fecha',
        'hora',
        'registrador_id',
        'estado',
        'descripcion',

    ];
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }
    public function tipoasistencia()
    {
        return $this->belongsTo(Tipoasistencia::class, 'tipo_asistencia_id');
    }
    /*
    public function bimestre()
    {
        return $this->belongsTo(Bimestre::class, 'bimestre');
    }*/
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
    public function periodobimestre()
    {
        return $this->belongsTo(Periodobimestre::class, 'periodobimestre_id');
    }
}
