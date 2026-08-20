<?php

namespace App\Models\AulaVirtual;

use App\Models\Docente;
use App\Models\Maya\Cursogradosecnivanio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aulavirtualsesion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_aula_virtual_sesiones';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'curso_grado_sec_niv_anio_id',
        'docente_id',
        'titulo',
        'plataforma',
        'enlace',
        'enlace_material',
        'fecha',
        'hora',
        'motivo',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'estado' => 'string',
    ];

    public function curso()
    {
        return $this->belongsTo(Cursogradosecnivanio::class, 'curso_grado_sec_niv_anio_id');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    public function getMateriaAttribute()
    {
        return $this->curso?->materia;
    }

    public function getGradoAttribute()
    {
        return $this->curso?->grado;
    }

    public function getPeriodoAttribute()
    {
        return $this->curso?->periodo;
    }
}
