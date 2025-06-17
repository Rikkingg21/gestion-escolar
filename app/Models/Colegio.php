<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'logo_path'
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
        return $this->logo_path ? asset($this->logo_path) : asset('images/default-logo.png');
    }

}
