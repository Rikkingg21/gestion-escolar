<?php

namespace App\Models\AulaVirtual;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materialtrabajo extends Model
{
    use HasFactory;

    protected $table = 'm_aula_virtual_material_trabajo';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'docente_id',
        'enlace_google_drive',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }
}
