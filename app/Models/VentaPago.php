<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    use HasFactory;

    protected $table = 'agd_venta_pagos';

    protected $fillable = [
        'venta_id',
        'metodo_pago_id',
        'monto',
        'referencia',
        'observaciones',
        'fecha_pago',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    /**
     * Relación con la venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /**
     * Relación con el método de pago
     */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodosPago::class, 'metodo_pago_id');
    }

    /**
     * Scope para filtrar por método de pago
     */
    public function scopeMetodo($query, $metodoId)
    {
        return $query->where('metodo_pago_id', $metodoId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeFechaEntre($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }
}
