<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Maya\Semana;
use App\Models\Maya\Tema;

class Clase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'maya_clases';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'semana_id',
        'fecha_clase',
        'descripcion',
    ];

    public function semana()
    {
        return $this->belongsTo(Semana::class, 'semana_id');
    }
    public function temas()
    {
        return $this->hasMany(Tema::class, 'clase_id');
    }
}
