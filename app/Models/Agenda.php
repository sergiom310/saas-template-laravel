<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'agd_agenda';
    protected $primaryKey = 'id_agenda';

    protected $fillable = [
        'fecha',
        'id_franja',
        'id_profesional',
        'id_cliente',
        'procedimiento',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_creacion' => 'datetime',
    ];

    /**
     * Relación con FranjaHoraria
     */
    public function franjaHoraria()
    {
        return $this->belongsTo(FranjaHoraria::class, 'id_franja', 'id_franja');
    }

    /**
     * Relación con Profesional
     */
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'id_profesional', 'id_profesional');
    }

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(ClienteAgenda::class, 'id_cliente', 'id_cliente');
    }
}
