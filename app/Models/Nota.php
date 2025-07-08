<?php

namespace App\Models;

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
        'nota',
        'estudiante_id',
        'criterio_evaluación_id',
    ];
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    public function criterio()
    {
        return $this->belongsTo(Maya\Criterio::class, 'criterio_id');
    }
}
