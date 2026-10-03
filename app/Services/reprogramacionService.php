<?php

namespace App\Services;

use App\Models\Reprogramacion;
use App\Models\tandeoProgramado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class reprogramacionService
{
    /**
     * Calcula los movimientos de un apagón general SIN guardar nada.
     * Cada circuito se atrasa según las horas de su punto de abastecimiento.
     */
    public function simularApagon(Carbon $desde): array
    {
        $hasta = $desde->copy()->addDays(config('sigeta.ventana_dias'));

        $tandeos = tandeoProgramado::with('circuito.puntoAbastecimiento', 'circuito.zona')
            ->where(function ($q) {
                $q->whereNull('estado')
                  ->orWhereNotIn('estado', ['cumplido', 'atraso', 'no_cumplido', 'con atraso', 'no cumplido']);
            })
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString())
            ->get()
            ->filter(fn ($t) => $t->inicio >= $desde && $t->inicio <= $hasta)
            ->sortBy('inicio')
            ->values();

        // Tandeos que ya fueron movidos a mano: el motor los respeta
        $manuales = Reprogramacion::whereIn('tandeo_id', $tandeos->pluck('id'))
            ->where('es_manual', true)
            ->pluck('tandeo_id')
            ->all();

        $movimientos = [];
        $advertencias = [];
        $cola = [];

        // 1. Afectados directos: tandeos del día del apagón cuyo circuito tiene punto de abastecimiento
        $origen = $tandeos->filter(function ($t) use ($desde) {
            $punto = $t->circuito->puntoAbastecimiento;

            return $punto
                && $punto->horas_atraso_apagon > 0
                && $t->inicio->isSameDay($desde);
        });

        foreach ($origen as $t) {
            $punto = $t->circuito->puntoAbastecimiento;
            $minutos = (int) round($punto->horas_atraso_apagon * 60);

            $mov = $this->mover($t, $t->inicio->copy()->addMinutes($minutos), false, $punto->id);
            $movimientos[$t->id] = $mov;
            $cola[] = $mov;
        }

        // 2. Cadena: si un tandeo movido empalma con otro, ese se recorre más tarde
        while ($actual = array_shift($cola)) {
            foreach ($tandeos as $otro) {
                if (isset($movimientos[$otro->id])) {
                    continue;
                }
                if (! $this->mismoAlcance($actual['tandeo'], $otro)) {
                    continue;
                }
                if (! $this->seEmpalman($actual['inicio_nuevo'], $actual['fin_nuevo'], $otro->inicio, $otro->fin)) {
                    continue;
                }

                if (in_array($otro->id, $manuales)) {
                    $advertencias[] = "El tandeo #{$otro->id} fue reprogramado manualmente y choca con la cadena; no se movió.";
                    continue;
                }

                $mov = $this->mover($otro, $actual['fin_nuevo']->copy(), true, $actual['punto_id']);
                $movimientos[$otro->id] = $mov;
                $cola[] = $mov;
            }
        }

        return [
            'movimientos'  => array_values($movimientos),
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Guarda los movimientos y el historial en una sola transacción.
     * Devuelve el uuid de la cadena.
     */
    public function aplicarApagon(Carbon $desde, int $usuarioId): string
    {
        $resultado = $this->simularApagon($desde);
        $cadena = (string) Str::uuid();

        DB::transaction(function () use ($resultado, $cadena, $usuarioId) {
            foreach ($resultado['movimientos'] as $m) {
                Reprogramacion::create([
                    'tandeo_id'               => $m['tandeo']->id,
                    'cadena_uuid'             => $cadena,
                    'tipo_origen'             => 'apagon_general',
                    'es_manual'               => false,
                    'es_efecto_cadena'        => $m['es_cadena'],
                    'punto_abastecimiento_id' => $m['punto_id'],
                    'inicio_anterior'         => $m['inicio_anterior'],
                    'fin_anterior'            => $m['fin_anterior'],
                    'inicio_nuevo'            => $m['inicio_nuevo'],
                    'fin_nuevo'               => $m['fin_nuevo'],
                    'horas_atraso'            => round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 1),
                    'usuario_id'              => $usuarioId,
                ]);

                $m['tandeo']->aplicarHorario($m['inicio_nuevo'], $m['fin_nuevo']);
            }
        });

        return $cadena;
    }

    private function mover(tandeoProgramado $t, Carbon $nuevoInicio, bool $esCadena, ?int $puntoId): array
    {
        $duracion = $t->inicio->diffInMinutes($t->fin);

        return [
            'tandeo'          => $t,
            'inicio_anterior' => $t->inicio->copy(),
            'fin_anterior'    => $t->fin->copy(),
            'inicio_nuevo'    => $nuevoInicio,
            'fin_nuevo'       => $nuevoInicio->copy()->addMinutes($duracion),
            'es_cadena'       => $esCadena,
            'punto_id'        => $puntoId,
        ];
    }

    private function seEmpalman(Carbon $inicioA, Carbon $finA, Carbon $inicioB, Carbon $finB): bool
    {
        return $inicioA < $finB && $inicioB < $finA;
    }

    private function mismoAlcance(tandeoProgramado $a, tandeoProgramado $b): bool
    {
        $campo = config('sigeta.alcance_cadena') === 'punto'
            ? 'punto_abastecimiento_id'
            : 'zona_id';

        return $a->circuito->$campo !== null
            && $a->circuito->$campo === $b->circuito->$campo;
    }
}