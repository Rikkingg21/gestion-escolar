<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conductaperiodobimestre extends Model
{
    use hasFactory;
    use SoftDeletes;
    protected $table = 'conducta_periodo_bimestres';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'periodo_bimestre_id',
        'conducta_id',
    ];
    public function periodoBimestre()
    {
        return $this->belongsTo(Periodobimestre::class, 'periodo_bimestre_id');
    }
    public function conducta()
    {
        return $this->belongsTo(Conducta::class, 'conducta_id');
    }
}
