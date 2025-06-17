<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Criterio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'maya_criterios_evaluaciones';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'tema_id',
        'descripcion',
        'tipo',
        'peso',
        'orden',
    ];

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id');
    }
}
