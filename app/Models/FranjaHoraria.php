<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranjaHoraria extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'agd_franja_horaria';
    protected $primaryKey = 'id_franja';
    public $timestamps = true;

    protected $fillable = [
        'hora_inicio',
        'hora_fin',
    ];
}
