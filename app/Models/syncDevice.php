<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Model;

class syncDevice extends Model
{
    use HasSyncUuid;

    protected $table = 'sync_devices';

    protected $fillable = ['sync_uuid', 'nombre', 'last_sync_at', 'last_change_id', 'activo'];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'activo' => 'boolean',
    ];

    public function cambios()
    {
        return $this->hasMany(syncChange::class, 'sync_device_id');
    }
}