<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesional extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'agd_profesional';
    protected $primaryKey = 'id_profesional';

    protected $fillable = [
        'nombre',
        'id_especialidad',
        'telefono',
        'activo',
    ];

    /**
     * Relación con Especialidad
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}
