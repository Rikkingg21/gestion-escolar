<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $table = 'estudiantes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'grado_id',
        'apoderado_id',
        'fecha_nacimiento',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con Grado (asumiendo que existe el modelo Grado)
    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }
    public function apoderado()
    {
        return $this->belongsTo(Apoderado::class);
    }
}
