<?php

namespace App\Models\Reporte;

use App\Models\Reporte\Reporte;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estadoreporte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'estado_reportes';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'reporte_id',
        'estado',
    ];

    public function reporte()
    {
        return $this->belongsTo(Reporte::class, 'reporte_id');
    }
}
