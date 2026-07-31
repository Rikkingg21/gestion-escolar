<?php

namespace App\Models\Metodopago;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tipopago extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tipo_pagos';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'categoria',
        'entidad_financiera',
        'numero_cuenta',
        'cci',
        'titular_cuenta',
        'numero_celular',
        'requiere_verificacion',
        'color_hex',
        'estado',       // '1' o '0'
        'es_efectivo',  // '1' o '0'
    ];

    protected $casts = [
        'requiere_verificacion' => 'boolean',
        'estado' => 'string',
        'es_efectivo' => 'string',
    ];
}
