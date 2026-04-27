<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;


class Periodobimestre extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'periodo_bimestres';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'periodo_id',
        'bimestre',
        'sigla',
        'fecha_inicio',
        'fecha_fin',
        'tipo_bimestre', //('A' es academico, 'R' es recuperación)
    ];
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }
}
