<?php

namespace App\Models\Tramite;

use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        // 'relacion',
        // 'estado_tramite_id',
        // 'estado_pago_id',
        'monto_pagado',
        'fecha_solicitud',
        'fecha_resolucion',
        'observaciones',
    ];

    protected $casts = [
        'monto_pagado' => 'integer', // en céntimos
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

    /*
    public function estadoTramite()
    {
        return $this->belongsTo(Estadotramite::class, 'estado_tramite_id');
    }

    public function estadoPago()
    {
        return $this->belongsTo(Estadopago::class, 'estado_pago_id');
    }
    */
    public function tramiteRegistros()
    {
        return $this->hasMany(Tramiteregistro::class, 'tramite_id');
    }

    public function tramitePagoRegistros()
    {
        return $this->hasMany(Tramitepagoregistro::class, 'tramite_id');
    }

    public function comprobantes()
    {
        return $this->hasMany(Pagocomprobante::class, 'tramite_id');
    }

    public function ultimoEstadoTramite()
    {
        return $this->hasOne(Tramiteregistro::class, 'tramite_id')->latest();
    }

    public function ultimoEstadoPago()
    {
        return $this->hasOne(Tramitepagoregistro::class, 'tramite_id')->latest('fecha_registro');
    }

    // Monto total pagado en céntimos. Fuente única: la columna monto_pagado,
    // mantenida por TramiteadminController al aprobar/rechazar pagos.
    public function getMontoPagadoTotalAttribute()
    {
        return $this->monto_pagado ?? 0;
    }
}
