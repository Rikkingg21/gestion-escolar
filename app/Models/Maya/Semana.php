<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Maya\Unidad;
use App\Models\Maya\Clase;

class Semana extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'maya_semanas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'unidad_id',
        'nombre',
    ];
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }
    public function clases()
    {
        return $this->hasMany(Clase::class, 'semana_id');
    }
}
