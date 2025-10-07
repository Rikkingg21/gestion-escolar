<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'modules';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'icono',
        'ruta_base',
        'estado',
    ];
}
