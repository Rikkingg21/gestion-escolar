<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colegio extends Model
{
    protected $table = 'colegio_config';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'ruc',
        'director_actual',
        'logo_path',
        'es_privado',
        'pensiones_activo',
        'usa_pasarela_pagos',
        'culqi_modo_prueba',
        'culqi_public_key',
        'culqi_secret_key',
    ];

    protected $casts = [
        'es_privado' => 'boolean',
        'pensiones_activo' => 'boolean',
        'usa_pasarela_pagos' => 'boolean',
        'culqi_modo_prueba' => 'boolean',
    ];

    // Obtener la instancia única del colegio (singleton)
    public static function configuracion()
    {
        static $instance = null;

        if (is_null($instance)) {
            $instance = static::firstOrCreate(['id' => 1]);
        }

        return $instance;
    }

    // Accesor para la URL del logo
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? asset($this->logo_path) : asset('storage/logo/logo-actual.png');
    }

    // El módulo de pensiones solo está disponible si la IE es privada y está activado
    public function pensionesHabilitadas(): bool
    {
        return $this->es_privado && $this->pensiones_activo;
    }

    // La pasarela de pagos está habilitada si se activa y se guardaron las keys
    public function pasarelaHabilitada(): bool
    {
        return $this->usa_pasarela_pagos && $this->culqi_public_key && $this->culqi_secret_key;
    }

    // Indica si Culqi está en modo prueba (sandbox) o producción
    public function culqiEnModoPrueba(): bool
    {
        return $this->culqi_modo_prueba;
    }
}
