<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conducta extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'conductas';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'estado',
    ];
    public function periodosBimestres()
    {
        return $this->belongsToMany(
            Periodobimestre::class,
            'conducta_periodo_bimestres',
            'conducta_id',
            'periodo_bimestre_id'
        )->withTimestamps();
    }
}
