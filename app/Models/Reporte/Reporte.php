<?php

namespace App\Models\Reporte;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Materia;
use App\Models\Reporte\Estadoreporte;

class Reporte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'reportes';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'creador_id',
        'destinatario_id',
        'materia_id',
        'asunto',
        'fecha',
        'hora',
    ];
    public function creador()
    {
        return $this->belongsTo(User::class, 'creador_id');
    }
    public function destinatario()
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }
    public function estadoreporte()
    {
        return $this->hasOne(\App\Models\Reporte\Estadoreporte::class, 'reporte_id');
    }
}
