<?php

namespace App\Models\Tramite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoPago extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'estado_pagos';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'color',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];
}
