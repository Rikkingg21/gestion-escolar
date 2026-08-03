<?php

namespace App\Models\Tramite;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramiteregistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'm_tramite_registros';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'tramite_id',
        'estado_tramite_id',
        'observacion',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'tramite_id');
    }

    public function estadoTramite()
    {
        return $this->belongsTo(Estadotramite::class, 'estado_tramite_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessor para fecha formateada
    public function getFechaFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }
}
