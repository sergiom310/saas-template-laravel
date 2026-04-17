<?php

namespace App\Models\Tenant;

use Spatie\Multitenancy\Models\Tenant;

class CustomTenantModel extends Tenant
{
    protected $table = 'tenants';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */    
    protected $fillable = [
        'name',
        'description',
        'domain',
        'name_company',
        'database',
        'owner_email',
        'is_active',
        'migrated_at',
        'welcome_email_sent_at',
        'expires_at',
        'estado_pago',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'migrated_at' => 'datetime',
        'welcome_email_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'estado_pago' => 'boolean',
        'config' => 'array',
    ];

    protected static function booted()
    {
        static::creating(fn(CustomTenantModel $model) => $model->createDatabase());
    }

    public function createDatabase()
    {
        // Aquí va la lógica para crear la base de datos usando $this->database
        // Ejemplo:
        // \DB::statement("CREATE DATABASE `{$this->database}`");
    }

    /**
     * Relación muchos a muchos con módulos
     */
    public function modulos()
    {
        return $this->belongsToMany(
            \App\Models\Modulo::class,
            'tenant_modulo',
            'tenant_id',
            'modulo_id'
        )->withPivot('metodo_pago', 'fecha_inicio', 'fecha_vencimiento', 'is_active')
          ->withTimestamps();
    }

    /**
     * Obtener módulos activos del tenant
     */
    public function modulosActivos()
    {
        return $this->modulos()->wherePivot('is_active', true);
    }
}
