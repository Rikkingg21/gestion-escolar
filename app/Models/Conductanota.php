<?php

namespace App\Models;

use App\Models\Conducta;
use App\Models\Materia\Materiacompetencia;
use App\Models\Maya\Bimestre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conductanota extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'conducta_notas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'estudiante_id',
        'conducta_id',
        'periodo_id',
        'bimestre',
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
    public function conducta()
    {
        return $this->belongsTo(Conducta::class, 'conducta_id');
    }
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}
