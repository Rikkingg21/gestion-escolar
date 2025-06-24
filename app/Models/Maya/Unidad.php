<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Maya\Bimestre;
use App\Models\Maya\Semana;

class Unidad extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'maya_unidades';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'bimestre_id',
        'nombre',
    ];
    public function bimestre()
    {
        return $this->belongsTo(Bimestre::class, 'bimestre_id');
    }
    public function semanas()
    {
        return $this->hasMany(Semana::class, 'unidad_id');
    }
}
