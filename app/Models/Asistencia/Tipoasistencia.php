<?php

namespace App\Models\Asistencia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tipoasistencia extends Model
{
    use hasFactory;
    use SoftDeletes;

    protected $table = 'tipo_asistencias';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'color_hex',
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'tipo_asistencia_id');
    }
}
