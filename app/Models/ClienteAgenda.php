<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteAgenda extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'agd_cliente';
    protected $primaryKey = 'id_cliente';
    public $timestamps = true;

    protected $fillable = [
        'cedula',
        'nombre',
        'telefono',
        'observaciones',
    ];
}
