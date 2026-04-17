<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'bitacora';

    protected $fillable = [
        'status',
        'tabla_id',
        'user_id',
        'estado_id',
        'nom_tabla',
        'obs_bitacora',
    ];

    /**
     * Get the user that owns the bitacora.
     */
    public function users()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the estado that owns the bitacora.
     */
    public function estado()
    {
        return $this->belongsTo('App\Models\Estados');
    }

}
