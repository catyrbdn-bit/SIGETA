<?php

namespace App\Services;

use App\Models\TandeoRepeticion;
use App\Models\tandeoProgramado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class repeticionTandeoService
{
    // Crea las fechas que faltan para dejar cargados los próximos días.
    public function crearSiguientes(): void
    {
        $fechaLimite = now()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();

        $series = TandeoRepeticion::where('activa', true)->get();

        foreach ($series as $serie) {
            DB::transaction(function () use ($serie, $fechaLimite) {
                $cadaDias = (int) $serie->cada_dias;
                $proximaFecha = Carbon::parse(
                    $serie->fecha_ancla->format('Y-m-d') . ' ' . $serie->hora_inicio
                )->addDays($cadaDias);

                while ($proximaFecha->lt(now())) {
                    $proximaFecha->addDays($cadaDias);
                }

                while ($proximaFecha->lessThanOrEqualTo($fechaLimite)) {
                    $proximaFecha->setTimeFromTimeString($serie->hora_inicio);
                    $yaExisteEnSerie = tandeoProgramado::where('repeticion_uuid', $serie->repeticion_uuid)
                        ->whereDate('fecha_repeticion', $proximaFecha->toDateString())
                        ->exists();

                    if (! $yaExisteEnSerie) {
                        $mismoHorario = tandeoProgramado::where('circuito_id', $serie->circuito_id)
                            ->whereDate('fecha', $proximaFecha->toDateString())
                            ->get()
                            ->contains(fn ($tandeo) =>
                                Carbon::parse($tandeo->fecha->format('Y-m-d') . ' ' . $tandeo->hora_inicio_programada)
                                    ->format('H:i') === $proximaFecha->format('H:i')
                            );

                        if (! $mismoHorario) {
                            $fin = $proximaFecha->copy()->addMinutes((int) $serie->duracion_minutos);
                            $fecha = $proximaFecha->toDateString();
                            $syncUuid = (string) Uuid::uuid5(
                                Uuid::NAMESPACE_URL,
                                'SIGETA:tandeo:' . $serie->repeticion_uuid . ':' . $fecha
                            );

                            $tandeo = new tandeoProgramado();
                            $tandeo->forceFill([
                                'circuito_id' => $serie->circuito_id,
                                'fecha' => $proximaFecha->toDateString(),
                                'hora_inicio_programada' => $proximaFecha->format('H:i:s'),
                                'hora_fin_programada' => $fin->format('H:i:s'),
                                'estado' => 'programado',
                                'tandeador_id' => $serie->tandeador_id,
                                'repeticion_uuid' => $serie->repeticion_uuid,
                                'repetir_cada_dias' => $serie->cada_dias,
                                'fecha_repeticion' => $fecha,
                                'sync_uuid' => $syncUuid,
                            ]);
                            $tandeo->save();
                            $tandeadoresIds = $serie->circuito->zona->tandeadores()
                                ->where('usuarios.rol', 'tandeador')
                                ->where('usuarios.activo', true)
                                ->pluck('usuarios.id')
                                ->all();
                            $tandeo->tandeadores()->sync($tandeadoresIds);
                            if ($tandeadoresIds) $tandeo->actualizarVersionSync();
                        }
                    }

                    $proximaFecha->addDays($cadaDias);
                }
            });
        }
    }
}
