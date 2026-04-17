<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoVehiculo extends Model
{
    protected $table = 'tipo_vehiculo';
    
    public $timestamps = false;
    
    protected $fillable = [
        'nombre',
        'imagen',
        'etiqueta_detalle',
        'status'
    ];

    /**
     * Relación: Un tipo de vehículo tiene muchas reglas de tarifa
     */
    public function tarifaReglas(): HasMany
    {
        return $this->hasMany(TarifaRegla::class, 'tipo_vehiculo_id');
    }
}
