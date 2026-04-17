<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;
use App\Models\ClienteAgenda;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agd_ventas';

    protected $fillable = [
        'user_id',
        'cliente_id',
        'subtotal',
        'descuento',
        'total',
        'estado',
        'observaciones',
        'fecha_venta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la venta
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el cliente (opcional)
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteAgenda::class, 'cliente_id', 'id_cliente');
    }

    /**
     * Relación con los detalles de la venta
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    /**
     * Relación con los pagos de la venta
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'venta_id');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeFechaEntre($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_venta', [$desde, $hasta]);
    }
}
