<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rolemoduleexception extends Model
{
    use HasFactory;
    protected $table = 'role_module_exceptions';
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'role_id',
        'module_id',
        'tipo_accion', // "A"dd, "R"emove
        'estado',
    ];
}
