<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombp', 'descrip', 'preciop', 'activo'
    ];

    /**
     * Get the turnos_servicios for the producto.
     */
    public function turnosServicios()
    {
        return $this->hasMany('App\TurnosServicios');
    }    
}
