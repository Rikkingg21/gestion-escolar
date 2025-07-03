<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apoderado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'apoderados';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'parentesco',
        'estado'
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
