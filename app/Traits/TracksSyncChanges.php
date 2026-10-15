<?php

namespace App\Traits;

use App\Services\syncService;

trait TracksSyncChanges
{
    protected $syncBaseVersion = 0;
    protected $syncSkipTracking = false;

    protected static function bootTracksSyncChanges(): void
    {
        static::creating(function ($model) {
            if (!$model->syncSkipTracking) {
                $model->sync_version = 1;
                $model->syncBaseVersion = 0;
            }
        });

        static::updating(function ($model) {
            if ($model->syncSkipTracking) {
                return;
            }

            $model->syncBaseVersion = (int) ($model->getOriginal('sync_version') ?? 0);
            $model->sync_version = $model->syncBaseVersion + 1;
        });

        static::created(function ($model) {
            if (!$model->syncSkipTracking) {
                app(syncService::class)->registrarCambio($model, 'guardar', 0);
            }
        });

        static::updated(function ($model) {
            if (!$model->syncSkipTracking) {
                app(syncService::class)->registrarCambio(
                    $model,
                    'guardar',
                    (int) $model->syncBaseVersion
                );
            }
        });

        static::deleted(function ($model) {
            if (!$model->syncSkipTracking) {
                app(syncService::class)->registrarCambio(
                    $model,
                    'eliminar',
                    (int) $model->sync_version
                );
            }
        });
    }

    public function omitirSeguimientoSync(): void
    {
        $this->syncSkipTracking = true;
    }
}