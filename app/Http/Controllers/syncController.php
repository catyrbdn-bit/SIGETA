<?php

namespace App\Http\Controllers;

use App\Models\circuitosModel;
use App\Models\cumplimientoTandeo;
use App\Models\PuntoAbastecimiento;
use App\Models\Reprogramacion;
use App\Models\TandeoRepeticion;
use App\Models\syncChange;
use App\Models\syncConflict;
use App\Models\syncDevice;
use App\Models\tandeoProgramado;
use App\Models\usuarioModel;
use App\Models\zonasModel;
use Illuminate\Http\Request;

class syncController extends Controller
{
    // Recibe cambios de una computadora y revisa si se pueden guardar en el servidor.
    public function subir(Request $request)
    {
        $datos = $request->validate([
            'device_uuid' => 'required|uuid',
            'device_name' => 'nullable|string|max:100',
            'cambios' => 'required|array|max:100',
            'cambios.*.sync_uuid' => 'required|uuid',
            'cambios.*.entity_type' => 'required|in:zonas,puntos_abastecimiento,circuitos,tandeos_programados,tandeo_repeticiones,cumplimiento_tandeos,reprogramaciones',
            'cambios.*.record_uuid' => 'required|uuid',
            'cambios.*.operation' => 'required|in:guardar,eliminar',
            'cambios.*.payload' => 'nullable|array',
            'cambios.*.base_version' => 'required|integer|min:0',
        ]);

        $dispositivo = syncDevice::firstOrCreate(
            ['sync_uuid' => $datos['device_uuid']],
            ['nombre' => $datos['device_name'] ?? 'Dispositivo SIGETA', 'activo' => true]
        );

        $resultados = [];

        foreach ($datos['cambios'] as $cambioDatos) {
            $cambio = syncChange::where('sync_uuid', $cambioDatos['sync_uuid'])->first();

            if ($cambio && !in_array($cambio->status, ['pendiente_dependencia'], true)) {
                $resultados[] = [
                    'sync_uuid' => $cambio->sync_uuid,
                    'estado' => $cambio->status,
                ];
                continue;
            }

            if (!$cambio) {
                $cambio = syncChange::create([
                    'sync_uuid' => $cambioDatos['sync_uuid'],
                    'sync_device_id' => $dispositivo->id,
                    'entity_type' => $cambioDatos['entity_type'],
                    'record_uuid' => $cambioDatos['record_uuid'],
                    'operation' => $cambioDatos['operation'],
                    'payload' => $cambioDatos['payload'] ?? [],
                    'base_version' => $cambioDatos['base_version'],
                    'status' => 'recibido',
                ]);
            }

            $estado = $this->aplicarCambio($cambio);
            $resultados[] = [
                'sync_uuid' => $cambio->sync_uuid,
                'estado' => $estado,
            ];
        }

        return response()->json(['resultados' => $resultados]);
    }

    // Envia a la computadora los cambios que todavia no ha recibido.
    public function descargar(Request $request)
    {
        $datos = $request->validate([
            'device_uuid' => 'required|uuid',
            'desde' => 'nullable|integer|min:0',
        ]);

        $desde = (int) ($datos['desde'] ?? 0);
        $dispositivo = syncDevice::firstOrCreate(
            ['sync_uuid' => $datos['device_uuid']],
            ['nombre' => 'Dispositivo SIGETA', 'activo' => true]
        );

        $cambios = syncChange::with('dispositivo')
            ->where('id', '>', $desde)
            ->whereIn('status', ['pendiente', 'aplicado'])
            ->orderBy('id')
            ->limit(100)
            ->get();

        $ultimoCambio = $cambios->last();
        $cursor = $ultimoCambio ? $ultimoCambio->id : $desde;

        $lista = $cambios->map(function ($cambio) {
            return [
                'id' => $cambio->id,
                'sync_uuid' => $cambio->sync_uuid,
                'device_uuid' => $cambio->dispositivo?->sync_uuid,
                'entity_type' => $cambio->entity_type,
                'record_uuid' => $cambio->record_uuid,
                'operation' => $cambio->operation,
                'payload' => $cambio->payload,
                'base_version' => $cambio->base_version,
            ];
        });

        $dispositivo->update([
            'last_change_id' => $cursor,
            'last_sync_at' => now(),
        ]);

        return response()->json([
            'cambios' => $lista,
            'cursor' => $cursor,
        ]);
    }

    // Aplica un cambio solo si el registro no fue modificado por alguien mas.
    private function aplicarCambio(syncChange $cambio): string
    {
        $modelos = [
            'zonas' => zonasModel::class,
            'puntos_abastecimiento' => PuntoAbastecimiento::class,
            'circuitos' => circuitosModel::class,
            'tandeos_programados' => tandeoProgramado::class,
            'tandeo_repeticiones' => TandeoRepeticion::class,
            'cumplimiento_tandeos' => cumplimientoTandeo::class,
            'reprogramaciones' => Reprogramacion::class,
        ];

        $modelo = $modelos[$cambio->entity_type] ?? null;
        if (!$modelo) {
            $cambio->update(['status' => 'rechazado']);
            return 'rechazado';
        }

        $registro = $modelo::where('sync_uuid', $cambio->record_uuid)->first();

        if ($cambio->operation === 'eliminar') {
            if (!$registro) {
                $cambio->update(['status' => 'aplicado']);
                return 'aplicado';
            }

            if ((int) $registro->sync_version !== (int) $cambio->base_version) {
                return $this->guardarConflicto($cambio, $registro);
            }

            $registro->omitirSeguimientoSync();
            $registro->delete();
            $cambio->update(['status' => 'aplicado']);
            return 'aplicado';
        }

        if ($registro && (int) $registro->sync_version !== (int) $cambio->base_version) {
            if ($cambio->entity_type === 'tandeos_programados' && (int) $cambio->base_version === 0) {
                $datosDuplicados = $this->resolverReferencias($cambio->entity_type, $cambio->payload ?? []);
                if ($datosDuplicados && $this->mismoTandeo($registro, $datosDuplicados)) {
                    $cambio->update(['status' => 'aplicado']);
                    return 'aplicado';
                }
            }

            return $this->guardarConflicto($cambio, $registro);
        }

        if (!$registro && (int) $cambio->base_version > 0) {
            return $this->guardarConflicto($cambio, null);
        }

        $datos = $this->resolverReferencias($cambio->entity_type, $cambio->payload ?? []);
        if ($datos === null) {
            $cambio->update(['status' => 'pendiente_dependencia']);
            return 'pendiente_dependencia';
        }

        $tandeadoresIds = null;
        if (in_array($cambio->entity_type, ['zonas', 'tandeos_programados'], true)
            && array_key_exists('tandeadores_ids', $datos)) {
            $tandeadoresIds = $datos['tandeadores_ids'];
            unset($datos['tandeadores_ids']);
        }

        if ($cambio->entity_type === 'cumplimiento_tandeos' && !empty($datos['resultado'])) {
            $tandeo = tandeoProgramado::find($datos['tandeo_id'] ?? null);

            if ($tandeo && now()->lt($tandeo->inicio)) {
                $cambio->update(['status' => 'rechazado']);
                return 'rechazado';
            }
        }

        if (!$registro) {
            $registro = new $modelo();
        }

        $registro->omitirSeguimientoSync();
        $registro->forceFill($datos);
        $registro->sync_uuid = $cambio->record_uuid;
        $registro->sync_version = (int) (($cambio->payload['sync_version'] ?? 0) ?: $cambio->base_version + 1);
        $registro->save();

        if ($registro instanceof zonasModel && $tandeadoresIds !== null) {
            $registro->tandeadores()->sync($tandeadoresIds);
        }
        if ($registro instanceof tandeoProgramado && $tandeadoresIds !== null) {
            $registro->tandeadores()->sync($tandeadoresIds);
        }

        if ($registro instanceof TandeoRepeticion && ! $registro->activa) {
            $this->eliminarOcurrenciasDeLaRepeticion($registro->repeticion_uuid);
        }

        $cambio->update(['status' => 'aplicado']);
        return 'aplicado';
    }

    private function resolverReferencias(string $tipo, array $datos): ?array
    {
        if ($tipo === 'zonas' && array_key_exists('tandeadores_usuarios', $datos)) {
            if (!is_array($datos['tandeadores_usuarios'])) {
                return null;
            }

            $nombres = array_values(array_unique($datos['tandeadores_usuarios']));
            $usuarios = usuarioModel::whereIn('usuario', $nombres)
                ->where('rol', 'tandeador')
                ->get();

            if ($usuarios->count() !== count($nombres)) {
                return null;
            }

            $datos['tandeadores_ids'] = $usuarios->pluck('id')->all();
            unset($datos['tandeadores_usuarios']);
        }

        if ($tipo === 'circuitos') {
            $zona = zonasModel::where('sync_uuid', $datos['zona_sync_uuid'] ?? '')->first();
            $punto = $this->buscarPorUuid(PuntoAbastecimiento::class, $datos['punto_abastecimiento_sync_uuid'] ?? null);
            if (!$zona || (($datos['punto_abastecimiento_sync_uuid'] ?? null) && !$punto)) return null;
            $datos['zona_id'] = $zona->id;
            $datos['punto_abastecimiento_id'] = $punto?->id;
            unset($datos['zona_sync_uuid'], $datos['punto_abastecimiento_sync_uuid']);
        }

        if ($tipo === 'tandeos_programados') {
            $circuito = circuitosModel::where('sync_uuid', $datos['circuito_sync_uuid'] ?? '')->first();
            $tandeador = $this->buscarUsuario($datos['tandeador_usuario'] ?? null);
            if (!$circuito || (($datos['tandeador_usuario'] ?? null) && !$tandeador)) return null;
            $datos['circuito_id'] = $circuito->id;
            $datos['tandeador_id'] = $tandeador?->id;

            if (array_key_exists('tandeadores_usuarios', $datos)) {
                $nombres = array_values(array_unique($datos['tandeadores_usuarios']));
                $usuarios = usuarioModel::whereIn('usuario', $nombres)->where('rol', 'tandeador')->get();
                if ($usuarios->count() !== count($nombres)) return null;
                $datos['tandeadores_ids'] = $usuarios->pluck('id')->all();
                unset($datos['tandeadores_usuarios']);
            }

            unset($datos['circuito_sync_uuid'], $datos['tandeador_usuario']);
        }

        if ($tipo === 'tandeo_repeticiones') {
            $circuito = circuitosModel::where('sync_uuid', $datos['circuito_sync_uuid'] ?? '')->first();
            $tandeador = $this->buscarUsuario($datos['tandeador_usuario'] ?? null);
            if (!$circuito || (($datos['tandeador_usuario'] ?? null) && !$tandeador)) return null;
            $datos['circuito_id'] = $circuito->id;
            $datos['tandeador_id'] = $tandeador?->id;
            unset($datos['circuito_sync_uuid'], $datos['tandeador_usuario']);
        }

        if ($tipo === 'cumplimiento_tandeos') {
            $tandeo = tandeoProgramado::where('sync_uuid', $datos['tandeo_sync_uuid'] ?? '')->first();
            $usuario = $this->buscarUsuario($datos['capturado_por_usuario'] ?? null);
            if (!$tandeo || (($datos['capturado_por_usuario'] ?? null) && !$usuario)) return null;
            $datos['tandeo_id'] = $tandeo->id;
            $datos['capturado_por_id'] = $usuario?->id;
            unset($datos['tandeo_sync_uuid'], $datos['capturado_por_usuario']);
        }

        if ($tipo === 'reprogramaciones') {
            $tandeo = tandeoProgramado::where('sync_uuid', $datos['tandeo_sync_uuid'] ?? '')->first();
            $punto = $this->buscarPorUuid(PuntoAbastecimiento::class, $datos['punto_abastecimiento_sync_uuid'] ?? null);
            $zona = $this->buscarPorUuid(zonasModel::class, $datos['zona_sync_uuid'] ?? null);
            $usuario = $this->buscarUsuario($datos['usuario'] ?? null);
            if (!$tandeo || !$usuario) return null;
            if (($datos['punto_abastecimiento_sync_uuid'] ?? null) && !$punto) return null;
            if (($datos['zona_sync_uuid'] ?? null) && !$zona) return null;
            $datos['tandeo_id'] = $tandeo->id;
            $datos['punto_abastecimiento_id'] = $punto?->id;
            $datos['zona_id'] = $zona?->id;
            $datos['usuario_id'] = $usuario->id;
            unset($datos['tandeo_sync_uuid'], $datos['punto_abastecimiento_sync_uuid'], $datos['zona_sync_uuid'], $datos['usuario']);
        }

        return $datos;
    }

    private function buscarPorUuid(string $modelo, ?string $uuid)
    {
        return $uuid ? $modelo::where('sync_uuid', $uuid)->first() : null;
    }

    private function buscarUsuario(?string $nombre)
    {
        return $nombre ? usuarioModel::where('usuario', $nombre)->first() : null;
    }

    private function mismoTandeo(tandeoProgramado $existente, array $datos): bool
    {
        foreach ([
            'circuito_id', 'fecha', 'hora_inicio_programada', 'hora_fin_programada',
            'estado', 'tandeador_id', 'repeticion_uuid', 'repetir_cada_dias', 'fecha_repeticion',
        ] as $campo) {
            $local = $existente->getAttribute($campo);
            $remoto = $datos[$campo] ?? null;

            if ($campo === 'fecha' && $local && $remoto) {
                $local = \Carbon\Carbon::parse($local)->toDateString();
                $remoto = \Carbon\Carbon::parse($remoto)->toDateString();
            } elseif (in_array($campo, ['hora_inicio_programada', 'hora_fin_programada'], true) && $local && $remoto) {
                $local = \Carbon\Carbon::parse($local)->format('H:i:s');
                $remoto = \Carbon\Carbon::parse($remoto)->format('H:i:s');
            }

            if ($local != $remoto) {
                return false;
            }
        }

        return true;
    }

    private function eliminarOcurrenciasDeLaRepeticion(string $serieId): void
    {
        $tandeos = tandeoProgramado::where('repeticion_uuid', $serieId)->get();
        $ids = $tandeos->pluck('id');

        cumplimientoTandeo::whereIn('tandeo_id', $ids)->get()->each(function ($registro) {
            $registro->omitirSeguimientoSync();
            $registro->delete();
        });

        Reprogramacion::whereIn('tandeo_id', $ids)->get()->each(function ($registro) {
            $registro->omitirSeguimientoSync();
            $registro->delete();
        });

        $tandeos->each(function ($tandeo) {
            $tandeo->omitirSeguimientoSync();
            $tandeo->delete();
        });
    }

    private function guardarConflicto(syncChange $cambio, $registro): string
    {
        syncConflict::firstOrCreate(
            ['sync_change_id' => $cambio->id],
            [
                'datos_locales' => $cambio->payload ?? [],
                'datos_centrales' => $registro ? $registro->getAttributes() : [],
                'motivo' => 'El registro cambió en otra computadora.',
                'estado' => 'pendiente_revision',
            ]
        );

        $cambio->update(['status' => 'conflicto']);
        return 'conflicto';
    }
}
