<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';
    public $timestamps = true;

    protected $fillable = [
        'cod_cli',
        'val_pag',
        'desde',
        'hasta',
        'fecha_pag',
        'horap',
        'user_sys',
        'cod_forp',
    ];

    protected $casts = [
        'desde' => 'date',
        'hasta' => 'date',
        'fecha_pag' => 'date',
        'val_pag' => 'decimal:0',
    ];

    /**
     * Relación: Un pago pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cod_cli', 'cod_cli');
    }

    /**
     * Relación: Un pago pertenece a un método de pago
     */
    public function metodo_pago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'cod_forp', 'id');
    }
}
