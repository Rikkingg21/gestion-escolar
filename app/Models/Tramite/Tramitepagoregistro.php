<?php

namespace App\Models\Tramite;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramitepagoregistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tramite_pago_registros';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'tramite_id',
        'pago_comprobante_id',
        'estado_pago_id',
        'monto',
        'fecha_registro',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    // Relaciones
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'tramite_id');
    }

    public function pagoComprobante()
    {
        return $this->belongsTo(Pagocomprobante::class, 'pago_comprobante_id');
    }

    public function estadoPago()
    {
        return $this->belongsTo(Estadopago::class, 'estado_pago_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessor para monto formateado
    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto, 2);
    }

    // Accessor para fecha formateada
    public function getFechaRegistroFormateadaAttribute()
    {
        return $this->fecha_registro ? $this->fecha_registro->format('d/m/Y H:i:s') : null;
    }
}
