<?php

namespace App\Models\Pension;

use App\Models\Grado;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PensionConfig extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pension_configs';

    public $timestamps = true;

    protected $primaryKey = 'id';

    protected $fillable = [
        'periodo_id',
        'grado_id',
        'estado',
    ];

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function cuotas()
    {
        return $this->hasMany(PensionConfigCuota::class, 'pension_config_id');
    }
}
