<?php

namespace App\Models;

use App\Models\Maya\Bimestre;
use App\Models\Maya\Cursogradosecnivanio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conductaperiodobimestrenota extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'conducta_periodo_bimestre_notas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'periodo_bimestre_id',
        'conducta_periodo_bimestre_id',
        'curso_grado_sec_niv_anio_id',
        'publico',
        'nota',
    ];
    protected $casts = [
        'publico' => 'string', // Forzamos a string para el ENUM
        'nota' => 'integer',  // Aseguramos que la nota sea entera
    ];
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    public function conductaPeriodoBimestre()
    {
        return $this->belongsTo(Conductaperiodobimestre::class, 'conducta_periodo_bimestre_id');
    }
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
    public function periodo_bimestre()
    {
        return $this->belongsTo(Bimestre::class, 'periodo_bimestre_id');
    }
    public function curso_grado_sec_niv_anio()
    {
        return $this->belongsTo(Cursogradosecnivanio::class, 'curso_grado_sec_niv_anio_id');
    }
}
