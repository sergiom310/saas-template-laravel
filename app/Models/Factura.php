<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    protected $table = 'factura';
    
    protected $fillable = [
        'tarifa_id',
        'tipo_vehiculo_id',
        'placa',
        'fecha_entrada',
        'fecha_salida',
        'minutos_total',
        'regla_total_id',
        'regla_fraccion_id',
        'valor_calculado',
        'valor_manual',
        'valor_pagado',
        'pendiente',
        'metodo_pago_id',
        'estado',
        'user_created',
        'user_updated',
        'observacion',
        'detalle',
        'queda',
        'pendiente_flag',
        'val_pago1',
        'multa',
        'pleno'
    ];

    protected $casts = [
        'fecha_entrada' => 'datetime',
        'fecha_salida' => 'datetime',
        'minutos_total' => 'integer',
        'valor_calculado' => 'decimal:0',
        'valor_manual' => 'decimal:0',
        'valor_pagado' => 'decimal:0',
        'pendiente' => 'decimal:0',
        'val_pago1' => 'decimal:0'
    ];

    /**
     * Relación: Una factura pertenece a una tarifa
     */
    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    /**
     * Relación: Una factura pertenece a un tipo de vehículo
     */
    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id');
    }

    /**
     * Relación: Regla total aplicada
     */
    public function reglaTotal(): BelongsTo
    {
        return $this->belongsTo(TarifaRegla::class, 'regla_total_id');
    }

    /**
     * Relación: Regla de fracción aplicada
     */
    public function reglaFraccion(): BelongsTo
    {
        return $this->belongsTo(TarifaRegla::class, 'regla_fraccion_id');
    }

    /**
     * Relación: Usuario que creó la factura
     */
    public function userCreator(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_created');
    }

    /**
     * Relación: Usuario que actualizó la factura
     */
    public function userUpdater(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'user_updated');
    }

    /**
     * Relación: Método de pago utilizado
     */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
