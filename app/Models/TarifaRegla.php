<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaRegla extends Model
{
    protected $table = 'tarifa_regla';
    
    public $timestamps = false;
    
    protected $fillable = [
        'tarifa_id',
        'tipo_vehiculo_id',
        'minutos_desde',
        'minutos_hasta',
        'contexto',
        'tipo_calculo',
        'valor',
        'prioridad'
    ];

    protected $casts = [
        'valor' => 'decimal:0',
        'minutos_desde' => 'integer',
        'minutos_hasta' => 'integer',
        'prioridad' => 'integer'
    ];

    /**
     * Relación: Una regla pertenece a una tarifa
     */
    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    /**
     * Relación: Una regla pertenece a un tipo de vehículo
     */
    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id');
    }
}
