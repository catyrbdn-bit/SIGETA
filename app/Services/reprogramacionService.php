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
     *
     * $ajustes: [tandeo_id => Carbon] horarios nuevos escritos a mano
     * $motivos: [tandeo_id => string] motivos escritos a mano
     */
    public function simularApagon(Carbon $desde, array $ajustes = [], array $motivos = []): array
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

        // Tandeos movidos a mano en eventos anteriores: el motor los respeta
        $yaManuales = Reprogramacion::whereIn('tandeo_id', $tandeos->pluck('id'))
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
            $nuevo = $t->inicio->copy()->addMinutes((int) round($punto->horas_atraso_apagon * 60));
            $manual = false;

            if (isset($ajustes[$t->id])) {
                $nuevo = $ajustes[$t->id]->copy();
                $manual = true;
            }

            $mov = $this->mover($t, $nuevo, false, $punto->id, $manual, $motivos[$t->id] ?? null);
            $movimientos[$t->id] = $mov;
            $cola[] = $mov;
        }

        // 2. Cadena: si el atraso provoca un empalme nuevo con otro tandeo, ese se recorre
        // Si un horario choca, recorremos ese tandeo y revisamos de nuevo.
        while ($actual = array_shift($cola)) {
            foreach ($tandeos as $otro) {
                if (isset($movimientos[$otro->id])) {
                    continue;
                }

                // Solo hay conflicto si el atraso lo provoca ahora
                if (! $this->seEmpalman($actual['inicio_nuevo'], $actual['fin_nuevo'], $otro->inicio, $otro->fin)) {
                    continue;
                }

                // Si ya se empalmaban en el programa original, se tolera
                if ($this->seEmpalman($actual['inicio_anterior'], $actual['fin_anterior'], $otro->inicio, $otro->fin)) {
                    continue;
                }

                $ajuste = $ajustes[$otro->id] ?? null;

                // Un tandeo ya movido a mano antes no se toca, salvo que se ajuste ahora
                if (! $ajuste && in_array($otro->id, $yaManuales)) {
                    $advertencias[] = "El tandeo de {$otro->circuito->nombre} fue reprogramado manualmente y choca con la cadena; no se movió.";
                    continue;
                }

                $mov = $this->mover(
                    $otro,
                    $ajuste ? $ajuste->copy() : $actual['fin_nuevo']->copy(),
                    true,
                    $actual['punto_id'],
                    (bool) $ajuste,
                    $motivos[$otro->id] ?? null
                );
                $movimientos[$otro->id] = $mov;
                $cola[] = $mov;
            }
        }

        foreach ($movimientos as $m) {
            if ($m['inicio_nuevo'] < $m['inicio_anterior']) {
                $advertencias[] = "El tandeo de {$m['tandeo']->circuito->nombre} se está adelantando en lugar de atrasarse.";
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
    /** Calcula una reprogramación manual y los empalmes nuevos que provoca. */
    public function simularManual(int $tandeoId, Carbon $nuevoInicio, array $ajustes = [], array $motivos = [], ?string $motivoInicial = null): array
    {
        $seleccionado = tandeoProgramado::with('circuito.puntoAbastecimiento', 'circuito.zona')->findOrFail($tandeoId);
        // Elegimos la fecha menor para revisar los tandeos cercanos.
        $inicioOriginal = $seleccionado->inicio;
        if ($nuevoInicio->lessThan($inicioOriginal)) {
            $inicioVentana = $nuevoInicio->copy();
        } else {
            $inicioVentana = $inicioOriginal->copy();
        }

        // Revisamos los 12 dias posteriores a la fecha mayor.
        if ($nuevoInicio->greaterThan($inicioOriginal)) {
            $finReferencia = $nuevoInicio->copy();
        } else {
            $finReferencia = $inicioOriginal->copy();
        }

        $inicioVentana->startOfDay();
        $finVentana = $finReferencia->addDays(config('sigeta.ventana_dias'))->endOfDay();

        $tandeos = tandeoProgramado::with('circuito.puntoAbastecimiento', 'circuito.zona')
            ->where(function ($q) {
                $q->whereNull('estado')
                  ->orWhereNotIn('estado', ['cumplido', 'atraso', 'no_cumplido', 'con atraso', 'no cumplido']);
            })
            ->whereDate('fecha', '>=', $inicioVentana->toDateString())
            ->whereDate('fecha', '<=', $finVentana->toDateString())
            ->get()
            ->filter(fn ($t) => $t->inicio >= $inicioVentana && $t->inicio <= $finVentana)
            ->sortBy('inicio')
            ->values();

        $tandeo = $tandeos->firstWhere('id', $tandeoId);
        if (! $tandeo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tandeo_id' => 'No se puede reprogramar este tandeo porque ya tiene un estado final.',
            ]);
        }

        $yaManuales = Reprogramacion::whereIn('tandeo_id', $tandeos->pluck('id'))
            ->where('es_manual', true)
            ->pluck('tandeo_id')
            ->all();

        $puntoId = $tandeo->circuito->puntoAbastecimiento?->id;
        $movimiento = $this->mover($tandeo, $nuevoInicio->copy(), false, $puntoId, true, $motivoInicial ?: 'Reprogramación manual');
        // Empezamos la cadena con el tandeo que la persona eligio.
        $movimientos = [$tandeoId => $movimiento];
        $cola = [$movimiento];
        $advertencias = [];

        // Si un horario choca, recorremos ese tandeo y revisamos de nuevo.
        while ($actual = array_shift($cola)) {
            foreach ($tandeos as $otro) {
                if (isset($movimientos[$otro->id])) {
                    continue;
                }
                if (! $this->seEmpalman($actual['inicio_nuevo'], $actual['fin_nuevo'], $otro->inicio, $otro->fin)) {
                    continue;
                }
                if ($this->seEmpalman($actual['inicio_anterior'], $actual['fin_anterior'], $otro->inicio, $otro->fin)) {
                    continue;
                }

                $ajuste = $ajustes[$otro->id] ?? null;
                if (! $ajuste && in_array($otro->id, $yaManuales)) {
                    $advertencias[] = "El tandeo de {$otro->circuito->nombre} fue reprogramado manualmente y choca con la cadena; no se movió.";
                    continue;
                }

                $mov = $this->mover(
                    $otro,
                    $ajuste ? $ajuste->copy() : $actual['fin_nuevo']->copy(),
                    true,
                    $actual['punto_id'],
                    (bool) $ajuste,
                    $motivos[$otro->id] ?? 'Recorrido por empalme'
                );
                $movimientos[$otro->id] = $mov;
                $cola[] = $mov;
            }
        }

        foreach ($movimientos as $m) {
            if ($m['inicio_nuevo'] < $m['inicio_anterior']) {
                $advertencias[] = "El tandeo de {$m['tandeo']->circuito->nombre} se está adelantando en lugar de atrasarse.";
            }
        }

        return ['movimientos' => array_values($movimientos), 'advertencias' => $advertencias];
    }

    /** Guarda una reprogramación manual y sus recorridos en una sola transacción. */
    public function aplicarManual(int $tandeoId, Carbon $nuevoInicio, int $usuarioId, array $ajustes = [], array $motivos = [], ?string $motivoInicial = null): string
    {
        $resultado = $this->simularManual($tandeoId, $nuevoInicio, $ajustes, $motivos, $motivoInicial);
        $cadena = (string) Str::uuid();

        // Guardamos el historial y los horarios juntos para evitar registros incompletos.
        DB::transaction(function () use ($resultado, $cadena, $usuarioId) {
            foreach ($resultado['movimientos'] as $m) {
                Reprogramacion::create([
                    'tandeo_id' => $m['tandeo']->id,
                    'cadena_uuid' => $cadena,
                    'tipo_origen' => 'manual',
                    'es_manual' => $m['manual'],
                    'es_efecto_cadena' => $m['es_cadena'],
                    'punto_abastecimiento_id' => $m['punto_id'],
                    'inicio_anterior' => $m['inicio_anterior'],
                    'fin_anterior' => $m['fin_anterior'],
                    'inicio_nuevo' => $m['inicio_nuevo'],
                    'fin_nuevo' => $m['fin_nuevo'],
                    'horas_atraso' => round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 1),
                    'usuario_id' => $usuarioId,
                    'observaciones' => $m['motivo'],
                ]);

                $m['tandeo']->aplicarHorario($m['inicio_nuevo'], $m['fin_nuevo']);
            }
        });

        return $cadena;
    }
    public function aplicarApagon(Carbon $desde, int $usuarioId, array $ajustes = [], array $motivos = []): string
    {
        $resultado = $this->simularApagon($desde, $ajustes, $motivos);
        $cadena = (string) Str::uuid();

        // Guardamos el historial y los horarios juntos para evitar registros incompletos.
        DB::transaction(function () use ($resultado, $cadena, $usuarioId) {
            foreach ($resultado['movimientos'] as $m) {
                Reprogramacion::create([
                    'tandeo_id'               => $m['tandeo']->id,
                    'cadena_uuid'             => $cadena,
                    'tipo_origen'             => 'apagon_general',
                    'es_manual'               => $m['manual'],
                    'es_efecto_cadena'        => $m['es_cadena'],
                    'punto_abastecimiento_id' => $m['punto_id'],
                    'inicio_anterior'         => $m['inicio_anterior'],
                    'fin_anterior'            => $m['fin_anterior'],
                    'inicio_nuevo'            => $m['inicio_nuevo'],
                    'fin_nuevo'               => $m['fin_nuevo'],
                    'horas_atraso'            => round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 1),
                    'usuario_id'              => $usuarioId,
                    'observaciones'           => $m['motivo'],
                ]);

                $m['tandeo']->aplicarHorario($m['inicio_nuevo'], $m['fin_nuevo']);
            }
        });

        return $cadena;
    }

    private function mover(tandeoProgramado $t, Carbon $nuevoInicio, bool $esCadena, ?int $puntoId, bool $manual, ?string $motivo): array
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
            'manual'          => $manual,
            'motivo'          => $motivo,
        ];
    }

    private function seEmpalman(Carbon $inicioA, Carbon $finA, Carbon $inicioB, Carbon $finB): bool
    {
        return $inicioA < $finB && $inicioB < $finA;
    }
}