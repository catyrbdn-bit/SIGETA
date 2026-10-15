<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSyncUuid
{
    protected static function bootHasSyncUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->sync_uuid)) {
                $model->sync_uuid = (string) Str::uuid();
            }
        });
    }
}