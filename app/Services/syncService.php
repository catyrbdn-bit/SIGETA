<?php

namespace App\Services;

use App\Models\circuitosModel;
use App\Models\cumplimientoTandeo;
use App\Models\PuntoAbastecimiento;
use App\Models\Reprogramacion;
use App\Models\TandeoRepeticion;
use App\Models\syncChange;
use App\Models\syncDevice;
use App\Models\tandeoProgramado;
use App\Models\usuarioModel;
use App\Models\zonasModel;
use Illuminate\Database\Eloquent\Model;

class syncService
{
    // Guarda cada cambio local para enviarlo a la otra computadora despues.
    public function registrarCambio(Model $model, string $operacion, int $versionBase): void
    {
        syncChange::create([
            'sync_device_id' => $this->obtenerDispositivo()->id,
            'entity_type' => $this->obtenerTipo($model),
            'record_uuid' => $model->sync_uuid,
            'operation' => $operacion,
            'payload' => $this->prepararDatos($model, $operacion),
            'base_version' => $versionBase,
            'status' => 'pendiente',
        ]);
    }

    private function obtenerDispositivo(): syncDevice
    {
        $dispositivo = syncDevice::first();

        if (!$dispositivo) {
            $dispositivo = syncDevice::create([
                'nombre' => gethostname() ?: 'Esta computadora',
                'activo' => true,
            ]);
        }

        return $dispositivo;
    }

    private function obtenerTipo(Model $model): string
    {
        $tipos = [
            zonasModel::class => 'zonas',
            PuntoAbastecimiento::class => 'puntos_abastecimiento',
            circuitosModel::class => 'circuitos',
            tandeoProgramado::class => 'tandeos_programados',
            TandeoRepeticion::class => 'tandeo_repeticiones',
            cumplimientoTandeo::class => 'cumplimiento_tandeos',
            Reprogramacion::class => 'reprogramaciones',
        ];

        return $tipos[get_class($model)] ?? $model->getTable();
    }

    // Prepara los datos para que funcionen aunque cada computadora tenga IDs locales distintos.
    private function prepararDatos(Model $model, string $operacion): array
    {
        if ($operacion === 'eliminar') {
            return [
                'sync_uuid' => $model->sync_uuid,
                'sync_version' => $model->sync_version,
            ];
        }

        $datos = $model->getAttributes();
        unset($datos['id'], $datos['created_at'], $datos['updated_at']);

        if ($model instanceof circuitosModel) {
            $datos['zona_sync_uuid'] = $this->uuidRelacionado(zonasModel::class, $model->zona_id);
            $datos['punto_abastecimiento_sync_uuid'] = $this->uuidRelacionado(
                PuntoAbastecimiento::class,
                $model->punto_abastecimiento_id
            );
            unset($datos['zona_id'], $datos['punto_abastecimiento_id']);
        }

        if ($model instanceof tandeoProgramado) {
            $datos['circuito_sync_uuid'] = $this->uuidRelacionado(circuitosModel::class, $model->circuito_id);
            $datos['tandeador_usuario'] = $this->nombreUsuario($model->tandeador_id);
            unset($datos['circuito_id'], $datos['tandeador_id']);
        }

        if ($model instanceof TandeoRepeticion) {
            $datos['circuito_sync_uuid'] = $this->uuidRelacionado(circuitosModel::class, $model->circuito_id);
            $datos['tandeador_usuario'] = $this->nombreUsuario($model->tandeador_id);
            unset($datos['circuito_id'], $datos['tandeador_id']);
        }

        if ($model instanceof cumplimientoTandeo) {
            $datos['tandeo_sync_uuid'] = $this->uuidRelacionado(tandeoProgramado::class, $model->tandeo_id);
            $datos['capturado_por_usuario'] = $this->nombreUsuario($model->capturado_por_id);
            unset($datos['tandeo_id'], $datos['capturado_por_id']);
        }

        if ($model instanceof Reprogramacion) {
            $datos['tandeo_sync_uuid'] = $this->uuidRelacionado(tandeoProgramado::class, $model->tandeo_id);
            $datos['punto_abastecimiento_sync_uuid'] = $this->uuidRelacionado(
                PuntoAbastecimiento::class,
                $model->punto_abastecimiento_id
            );
            $datos['zona_sync_uuid'] = $this->uuidRelacionado(zonasModel::class, $model->zona_id);
            $datos['usuario'] = $this->nombreUsuario($model->usuario_id);
            unset($datos['tandeo_id'], $datos['punto_abastecimiento_id'], $datos['zona_id'], $datos['usuario_id']);
        }

        return $datos;
    }

    private function uuidRelacionado(string $modelo, $id): ?string
    {
        if (!$id) {
            return null;
        }

        return $modelo::whereKey($id)->value('sync_uuid');
    }

    private function nombreUsuario($id): ?string
    {
        if (!$id) {
            return null;
        }

        return usuarioModel::whereKey($id)->value('usuario');
    }
}
