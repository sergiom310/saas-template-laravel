<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoAgenda extends Model
{
    protected $connection = 'tenant';
    protected $table = 'agd_pago_agenda';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_agenda',
        'monto',
        'metodo_pago',
        'estado',
        'fecha_pago'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'metodo_pago' => 'integer',
        'fecha_pago' => 'datetime'
    ];

    /**
     * Relación con Agenda
     */
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'id_agenda', 'id_agenda');
    }

    /**
     * Relación con MetodoPago
     */
    public function metodoPago()
    {
        return $this->belongsTo(MetodosPago::class, 'metodo_pago', 'id');
    }
}
