<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apoderado extends Model
{
    protected $table = 'apoderados';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'parentesco',
        'telefono1',
        'telefono2'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'apoderado_id');
    }
}
