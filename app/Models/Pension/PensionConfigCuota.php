<?php

namespace App\Models\Pension;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PensionConfigCuota extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pension_config_cuotas';

    public $timestamps = true;

    protected $primaryKey = 'id';

    protected $fillable = [
        'pension_config_id',
        'concepto',
        'mes',
        'anio',
        'fecha_vencimiento',
        'monto',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'fecha_vencimiento' => 'date',
        'monto' => 'integer',
    ];

    public function config()
    {
        return $this->belongsTo(PensionConfig::class, 'pension_config_id');
    }

    public function pensiones()
    {
        return $this->hasMany(Pension::class, 'config_cuota_id');
    }

    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto / 100, 2);
    }

    public function getFechaVencimientoFormateadaAttribute()
    {
        return $this->fecha_vencimiento ? $this->fecha_vencimiento->format('d/m/Y') : 'N/A';
    }
}
