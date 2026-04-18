<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $fillable = [
        'nombre',
        'nit',
        'direccion',
        'telefono',
        'email',
        'sitio_web',
        'logo',
        'imagen_header',
        'ciudad',
        'pais',
        'descripcion',
        'moneda',
        'impuesto_label',
        'impuesto_porcentaje',
    ];

    protected function casts(): array
    {
        return [
            'impuesto_porcentaje' => 'decimal:2',
        ];
    }
}
