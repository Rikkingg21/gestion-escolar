<?php

namespace App\Models\Pension;

use App\Models\Metodopago\Tipopago;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PensionPago extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pension_pagos';

    public $timestamps = true;

    protected $primaryKey = 'id';

    protected $fillable = [
        'pension_id',
        'user_id',
        'metodo_pago_id',
        'numero_operacion',
        'monto',
        'fecha_pago',
        'comprobante_path',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'integer',
        'fecha_pago' => 'datetime',
    ];

    public function pension()
    {
        return $this->belongsTo(Pension::class, 'pension_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(Tipopago::class, 'metodo_pago_id');
    }

    public function pagoRegistros()
    {
        return $this->hasMany(PensionPagoRegistro::class, 'pago_id');
    }

    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto / 100, 2);
    }

    public function getFechaPagoFormateadaAttribute()
    {
        return $this->fecha_pago ? $this->fecha_pago->format('d/m/Y H:i:s') : null;
    }
}
