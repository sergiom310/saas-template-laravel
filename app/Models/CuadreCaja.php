<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuadreCaja extends Model
{
    protected $table = 'cuadre_caja';
    protected $primaryKey = 'id_cuadre';
    public $timestamps = false;

    protected $fillable = [
        'usuario',
        'fecha_apertura',
        'fecha_cierre',
        'total_ingresos',
        'base',
        'estado',
    ];
}
