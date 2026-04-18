<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodosPago extends Model
{
    protected $table = 'metodo_pago';

    public $timestamps = false;

    protected $fillable = [
        'detalle',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con los pagos de ventas
     */
    public function ventaPagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'metodo_pago_id');
    }

    /**
     * Scope para obtener solo métodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
}
