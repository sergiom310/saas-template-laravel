<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarifa extends Model
{
    protected $table = 'tarifa';
    
    public $timestamps = false;
    
    protected $fillable = [
        'nombre',
        'status'
    ];

    /**
     * Relación: Una tarifa tiene muchas reglas
     */
    public function reglas(): HasMany
    {
        return $this->hasMany(TarifaRegla::class, 'tarifa_id');
    }
}
