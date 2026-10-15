<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Model;

class syncChange extends Model
{
    use HasSyncUuid;

    protected $table = 'sync_changes';

    protected $fillable = [
        'sync_uuid', 'sync_device_id', 'entity_type', 'record_uuid', 'operation',
        'payload', 'base_version', 'status', 'intentos', 'enviado_en',
    ];

    protected $casts = [
        'payload' => 'array',
        'enviado_en' => 'datetime',
    ];

    public function dispositivo()
    {
        return $this->belongsTo(syncDevice::class, 'sync_device_id');
    }

    public function conflicto()
    {
        return $this->hasOne(syncConflict::class, 'sync_change_id');
    }
}