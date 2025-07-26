<?php
namespace App\Models\Maya;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Maya\Cursogradosecnivanio;
use App\Models\Maya\Unidad;

class Bimestre extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'maya_bimestres';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'curso_grado_sec_niv_anio_id',
        'nombre',
    ];
    public function cursoGradoSecNivAnio()
    {
        return $this->belongsTo(Cursogradosecnivanio::class, 'curso_grado_sec_niv_anio_id');
    }
}
