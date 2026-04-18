<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    use HasFactory;

    protected $table = 'venta_pagos';

    protected $fillable = [
        'venta_id',
        'metodo_pago_id',
        'monto',
        'referencia',
        'observaciones',
        'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'metodo_pago_id' => 'integer',
            'fecha_pago' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /**
     * Relación con MetodoPago
     */
    public function metodoPago()
    {
        return $this->belongsTo(MetodosPago::class, 'metodo_pago_id', 'id');
    }

    public function scopeFechaEntre($query, string $desde, string $hasta)
    {
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }
}
