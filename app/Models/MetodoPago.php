<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $connection = 'tenant';
    protected $table = 'metodo_pago';

    public $timestamps = false;

    protected $fillable = [
        'detalle',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Scope para obtener solo métodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
}
