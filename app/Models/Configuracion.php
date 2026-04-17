<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';
    protected $fillable = [
        'imp_logo',
        'imp_mensaje',
        'imp_nit',
        'imp_tel',
        'imp_dir',
    ];
}
