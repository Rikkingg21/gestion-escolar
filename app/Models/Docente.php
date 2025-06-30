<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Docente extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'docentes';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'especialidad',
        'materia_id',
        'grado_id',
        'estado',
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
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}
