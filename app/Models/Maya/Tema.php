<?php

namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tema extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'maya_temas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'clase_id',
        'nombre',
        'descripcion',
        'orden',
    ];
    public function clase()
    {
        return $this->belongsTo(Clase::class, 'clase_id');
    }
}
