<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->belongsTo(Unidad::class);
    }
}
