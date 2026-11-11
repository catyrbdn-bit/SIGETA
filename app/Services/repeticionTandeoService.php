<?php

namespace App\Services;

// archivo del sistema: archivo con procesos que utiliza el sistema

use App\Models\TandeoProgramado;
use App\Models\TandeoRepeticion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RepeticionTandeoService
{
    // Crea las fechas que faltan para dejar cargados los próximos días.
    // Crea las próximas repeticiones de los tandeos programados.
    // descripcion: crea las próximas repeticiones de los tandeos programados.
    // entradas: no requiere datos externos
    // salida: guarda los tandeos que corresponden al siguiente periodo
    public function crearSiguientes(): void
    {
        $fechaLimite = now()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();

        $series = TandeoRepeticion::where('activa', true)->get();

        foreach ($series as $serie) {
            // Agrupa los cambios para guardarlos como una sola operación.
            // descripcion: agrupa los cambios para guardarlos como una sola operación.
            // entradas: registros que se actualizan durante la operación
            // salida: confirma los cambios o los revierte si ocurre un error
            DB::transaction(function () use ($serie, $fechaLimite) {
                $cadaDias = (int) $serie->cada_dias;
                $proximaFecha = Carbon::parse(
                    $serie->fecha_ancla->format('Y-m-d').' '.$serie->hora_inicio
                )->addDays($cadaDias);

                while ($proximaFecha->lt(now())) {
                    $proximaFecha->addDays($cadaDias);
                }

                while ($proximaFecha->lessThanOrEqualTo($fechaLimite)) {
                    $proximaFecha->setTimeFromTimeString($serie->hora_inicio);
                    $yaExisteEnSerie = TandeoProgramado::where('repeticion_uuid', $serie->repeticion_uuid)
                        ->whereDate('fecha_repeticion', $proximaFecha->toDateString())
                        ->exists();

                    if (! $yaExisteEnSerie) {
                        $mismoHorario = TandeoProgramado::where('circuito_id', $serie->circuito_id)
                            ->whereDate('fecha', $proximaFecha->toDateString())
                            ->get()
                            // Comprueba si algún tandeo de la lista cumple la condición indicada.
                            // descripcion: comprueba si algún tandeo de la lista cumple la condición indicada.
                            // entradas: tandeo que se está comparando
                            // salida: indica si se encontró un tandeo que cumple la condición
                            ->contains(fn ($tandeo) => Carbon::parse($tandeo->fecha->format('Y-m-d').' '.$tandeo->hora_inicio_programada)
                                ->format('H:i') === $proximaFecha->format('H:i')
                            );

                        if (! $mismoHorario) {
                            $fin = $proximaFecha->copy()->addMinutes((int) $serie->duracion_minutos);
                            $fecha = $proximaFecha->toDateString();
                            $tandeo = new TandeoProgramado;
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
                            ]);
                            $tandeo->save();
                            $tandeadoresIds = $serie->circuito->zona->tandeadores()
                                ->where('usuarios.rol', 'tandeador')
                                ->where('usuarios.activo', true)
                                ->pluck('usuarios.id')
                                ->all();
                            $tandeo->tandeadores()->sync($tandeadoresIds);
                        }
                    }

                    $proximaFecha->addDays($cadaDias);
                }
            });
        }
    }
}
