<?php

namespace App\Models\Pension;

use App\Models\Tramite\Estadopago;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PensionPagoRegistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pension_pago_registros';

    public $timestamps = true;

    protected $primaryKey = 'id';

    protected $fillable = [
        'pension_id',
        'pago_id',
        'estado_pago_id',
        'monto',
        'fecha_registro',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'integer',
        'fecha_registro' => 'datetime',
    ];

    public function pension()
    {
        return $this->belongsTo(Pension::class, 'pension_id');
    }

    public function pago()
    {
        return $this->belongsTo(PensionPago::class, 'pago_id');
    }

    public function estadoPago()
    {
        return $this->belongsTo(Estadopago::class, 'estado_pago_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto / 100, 2);
    }

    public function getFechaRegistroFormateadaAttribute()
    {
        return $this->fecha_registro ? $this->fecha_registro->format('d/m/Y H:i:s') : null;
    }
}
