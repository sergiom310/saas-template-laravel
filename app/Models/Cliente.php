<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'cod_cli';
    public $timestamps = false;

    protected $fillable = [
        'nom_cli',
        'telefono',
        'tipo_vehi',
        'placa',
        'valor_mensual',
        'desde',
        'hasta',
        'estado',
        'imp',
    ];

    protected $casts = [
        'desde' => 'date',
        'hasta' => 'date',
        'valor_mensual' => 'decimal:2',
    ];

    /**
     * Relación: Un cliente pertenece a un tipo de vehículo
     */
    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehi', 'id');
    }
}
