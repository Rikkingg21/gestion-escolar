<?php

namespace App\Models\Pension;

use App\Models\Matricula;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pension extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pensiones';

    public $timestamps = true;

    protected $primaryKey = 'id';

    const ESTADO_PENDIENTE = 'pendiente';

    const ESTADO_PAGADO = 'pagado';

    const ESTADO_ANULADO = 'anulado';

    protected $fillable = [
        'matricula_id',
        'config_cuota_id',
        'concepto',
        'mes',
        'anio',
        'fecha_vencimiento',
        'monto',
        'monto_pagado',
        'estado',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'fecha_vencimiento' => 'date',
        'monto' => 'integer',
        'monto_pagado' => 'integer',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function configCuota()
    {
        return $this->belongsTo(PensionConfigCuota::class, 'config_cuota_id');
    }

    public function pagos()
    {
        return $this->hasMany(PensionPago::class, 'pension_id');
    }

    public function pagoRegistros()
    {
        return $this->hasMany(PensionPagoRegistro::class, 'pension_id');
    }

    public function esPagada(): bool
    {
        return $this->estado === self::ESTADO_PAGADO || $this->monto_pagado >= $this->monto;
    }

    public function getEstadoEfectivoAttribute(): string
    {
        if ($this->esPagada()) {
            return self::ESTADO_PAGADO;
        }

        if ($this->estado === self::ESTADO_ANULADO) {
            return self::ESTADO_ANULADO;
        }

        if ($this->fecha_vencimiento && $this->fecha_vencimiento->lt(today())) {
            return 'atrasado';
        }

        return self::ESTADO_PENDIENTE;
    }

    public function getEstadoEfectivoLabelAttribute(): string
    {
        return match ($this->estado_efectivo) {
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_ANULADO => 'Anulado',
            'atrasado' => 'Atrasado',
            default => 'Pendiente',
        };
    }

    public function getEstadoEfectivoColorAttribute(): string
    {
        return match ($this->estado_efectivo) {
            self::ESTADO_PAGADO => 'success',
            self::ESTADO_ANULADO => 'secondary',
            'atrasado' => 'danger',
            default => 'warning',
        };
    }

    public function getAtrasadaAttribute(): bool
    {
        return $this->estado_efectivo === 'atrasado';
    }

    public function getMontoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto / 100, 2);
    }

    public function getMontoPagadoFormateadoAttribute()
    {
        return 'S/ '.number_format($this->monto_pagado / 100, 2);
    }

    public function getSaldoPendienteAttribute(): int
    {
        return max(0, $this->monto - $this->monto_pagado);
    }

    public function getSaldoPendienteFormateadoAttribute()
    {
        return 'S/ '.number_format($this->saldo_pendiente / 100, 2);
    }

    public function getFechaVencimientoFormateadaAttribute()
    {
        return $this->fecha_vencimiento ? $this->fecha_vencimiento->format('d/m/Y') : 'N/A';
    }

    public function scopePagadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PAGADO);
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeAnuladas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ANULADO);
    }

    public function scopeAtrasadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
            ->whereDate('fecha_vencimiento', '<', today()->toDateString());
    }

    public function scopePendientesOAtrasadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }
}
