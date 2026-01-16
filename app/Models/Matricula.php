<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Estudiante;
use App\Models\Periodo;
use App\Models\Grado;

class Matricula extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'matriculas';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'grado_id',
        'estado',
    ];
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }
    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }
}
