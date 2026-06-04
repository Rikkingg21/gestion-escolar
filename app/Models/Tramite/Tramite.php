<?php

namespace App\Models\Tramite;

use App\Models\User;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramite extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tramite_tramites';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'codigo_tramite',
        'user_id',
        'tipo_tramite_id',
        'estudiante_id',
        'relacion',
        'estado_tramite_id',
        'estado_pago_id',
        'monto_pagado',
        'fecha_solicitud',
        'fecha_resolucion',
        'observaciones',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_solicitud' => 'date',
        'fecha_resolucion' => 'date',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoTramite()
    {
        return $this->belongsTo(Tramitetipo::class, 'tipo_tramite_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function estadoTramite()
    {
        return $this->belongsTo(EstadoTramite::class, 'estado_tramite_id');
    }

    public function estadoPago()
    {
        return $this->belongsTo(EstadoPago::class, 'estado_pago_id');
    }
}
