<?php

namespace App\Models\Tramite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramitetipo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tramite_tipo_tramites';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'costo',
        'requiere_pago',
        'requiere_documentos',
        'tiempo_estimado_dias',
        'estado',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'requiere_pago' => 'boolean',
        'requiere_documentos' => 'boolean',
        'tiempo_estimado_dias' => 'integer',
        'estado' => 'string',
    ];
}
