<?php

namespace App\Models\Tramite;

use App\Models\Metodopago\Tipopago;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagocomprobante extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tramite_pago_comprobantes';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'tramite_id',
        'user_id',
        'metodo_pago_id',
        'numero_operacion',
        'monto',
        'fecha_pago',
        'comprobante_path',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    // Relaciones
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'tramite_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(Tipopago::class, 'metodo_pago_id');
    }

    // Accessor para monto formateado
    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto, 2);
    }

    // Accessor para saber si es imagen
    public function getEsImagenAttribute()
    {
        $extension = pathinfo($this->comprobante_path, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif', 'webp']);
    }

    // Accessor para saber si es PDF
    public function getEsPdfAttribute()
    {
        $extension = pathinfo($this->comprobante_path, PATHINFO_EXTENSION);

        return strtolower($extension) === 'pdf';
    }
}
