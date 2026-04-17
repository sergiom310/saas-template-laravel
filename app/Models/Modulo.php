<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';

    protected $fillable = [
        'nombre_modulo',
        'slug',
        'descripcion',
        'precio_mensual',
        'precio_anual',
        'is_active',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con tenants
     */
    public function tenants()
    {
        return $this->belongsToMany(
            \App\Models\Tenant\CustomTenantModel::class,
            'tenant_modulo',
            'modulo_id',
            'tenant_id'
        )->withPivot('metodo_pago', 'fecha_inicio', 'fecha_vencimiento', 'is_active')
          ->withTimestamps();
    }

    /**
     * Scope para módulos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
