<?php

namespace App\Services;

use App\Models\CumplimientoTandeo;
use App\Models\Reprogramacion;
use App\Models\TandeoRepeticion;
use App\Models\circuitosModel;
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
    public function simularApagon(
        Carbon $desde,
        array $ajustes = [],
        array $motivos = [],
        ?string $tipoPunto = null,
        ?int $horasFuga = null,
        string $motivoIncidencia = 'Afectado por el apagón'
    ): array
    {
        $hasta = $desde->copy()->addDays(config('sigeta.ventana_dias'));

        // Buscamos tandeos pendientes dentro del periodo de 12 dias.
        $tandeos = tandeoProgramado::with([
            'circuito.puntoAbastecimiento',
            'circuito.zona',
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
            ->where(function ($consulta) {
                $consulta->whereNull('estado')
                    ->orWhereNotIn('estado', ['cumplido', 'atraso', 'no_cumplido', 'con atraso', 'no cumplido']);
            })
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString())
            ->get()
            ->filter(function ($tandeo) use ($desde, $hasta) {
                if ($tandeo->inicio < $desde || $tandeo->inicio > $hasta) {
                    return false;
                }

                // Un tandeo ya cumplido no se modifica. Si se cambió su horario,
                // ignoramos el cumplimiento viejo capturado antes del nuevo inicio.
                $cumplimiento = $tandeo->cumplimientos->first();
                if (!$cumplimiento || !$cumplimiento->fecha_captura) {
                    return true;
                }
                if (Carbon::parse($cumplimiento->fecha_captura)->lt($tandeo->inicio)) {
                    return true;
                }

                return !in_array($cumplimiento->resultado, ['cumplido', 'atraso', 'no_cumplido'], true);
            })
            ->sortBy('inicio')
            ->values();

        $movimientos = [];
        $horariosRevisados = [];
        $advertencias = [];

        // Aplicamos el atraso del punto a cada tandeo del periodo.
        foreach ($tandeos as $tandeo) {
            $punto = $tandeo->circuito->puntoAbastecimiento;
            $esAfectado = $tipoPunto
                ? ($punto && $punto->tipo === $tipoPunto)
                : ($punto && $punto->horas_atraso_apagon > 0);
            $ajusteManual = $ajustes[$tandeo->id] ?? null;
            $esManual = $ajusteManual !== null;
            $esCadena = false;
            $puntoId = $esAfectado ? $punto->id : null;

            if ($ajusteManual) {
                $nuevoInicio = $ajusteManual->copy();
            } elseif ($esAfectado) {
                $minutosAtraso = (int) round(($horasFuga ?? $punto->horas_atraso_apagon) * 60);
                $nuevoInicio = $tandeo->inicio->copy()->addMinutes($minutosAtraso);
            } else {
                $nuevoInicio = $tandeo->inicio->copy();
            }

            if ($tipoPunto && $esAfectado && $ajusteManual) {
                $minutosElegidos = $nuevoInicio->getTimestamp() - $tandeo->inicio->getTimestamp();
                $minutosMinimos = (int) round(($horasFuga ?? 0) * 60);

                if ($minutosElegidos < $minutosMinimos) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'nuevo' => "El atraso de esta fuga no puede ser menor a {$horasFuga} horas.",
                    ]);
                }
            }

            $duracion = $tandeo->inicio->diffInMinutes($tandeo->fin);
            $nuevoFin = $nuevoInicio->copy()->addMinutes($duracion);

            // Si el nuevo horario choca con uno ya revisado, lo recorremos al final.
            foreach ($horariosRevisados as $anterior) {
                $hayEmpalme = $this->seEmpalman(
                    $nuevoInicio,
                    $nuevoFin,
                    $anterior['inicio_nuevo'],
                    $anterior['fin_nuevo']
                );
                $yaSeEmpalmaban = $this->seEmpalman(
                    $tandeo->inicio,
                    $tandeo->fin,
                    $anterior['inicio_anterior'],
                    $anterior['fin_anterior']
                );

                if ($hayEmpalme && !$yaSeEmpalmaban) {
                    $nuevoInicio = $anterior['fin_nuevo']->copy();
                    $nuevoFin = $nuevoInicio->copy()->addMinutes($duracion);
                    $esCadena = true;

                    if (!$puntoId) {
                        $puntoId = $anterior['punto_id'];
                    }
                }
            }

            $horariosRevisados[] = [
                'inicio_anterior' => $tandeo->inicio->copy(),
                'fin_anterior' => $tandeo->fin->copy(),
                'inicio_nuevo' => $nuevoInicio->copy(),
                'fin_nuevo' => $nuevoFin->copy(),
                'punto_id' => $puntoId,
            ];

            if ($esAfectado || $esManual || $esCadena) {
                $movimientos[$tandeo->id] = $this->mover(
                    $tandeo,
                    $nuevoInicio,
                    $esCadena,
                    $puntoId,
                    $esManual,
                    $motivos[$tandeo->id] ?? ($esCadena ? 'Recorrido por empalme' : ($esAfectado ? $motivoIncidencia : null))
                );
            }
        }

        foreach ($movimientos as $movimiento) {
            if ($movimiento['inicio_nuevo'] < $movimiento['inicio_anterior']) {
                $advertencias[] = "El tandeo de {$movimiento['tandeo']->circuito->nombre} se está adelantando en lugar de atrasarse.";
            }
        }

        return [
            'movimientos' => array_values($movimientos),
            'advertencias' => $advertencias,
        ];
    }

    /** Calcula una reprogramación manual y los empalmes nuevos que provoca. */
    public function simularManual(int $tandeoId, Carbon $nuevoInicio, array $ajustes = [], array $motivos = [], ?string $motivoInicial = null, ?int $circuitoId = null, ?int $duracionManual = null): array
    {
        $seleccionado = tandeoProgramado::with('circuito.puntoAbastecimiento', 'circuito.zona')->findOrFail($tandeoId);
        $circuitoNuevo = null;
        $duracionNueva = $duracionManual;

        if ($circuitoId && (int) $seleccionado->circuito_id !== $circuitoId) {
            $circuitoNuevo = circuitosModel::with('puntoAbastecimiento', 'zona')->findOrFail($circuitoId);
            if ($duracionNueva === null) {
                $duracionNueva = (int) round((float) $circuitoNuevo->duracion * 60);
            }
        }
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
        $inicioOriginal = $seleccionado->inicio;

        $tandeos = tandeoProgramado::with('circuito.puntoAbastecimiento', 'circuito.zona')
            ->where(function ($q) {
                $q->whereNull('estado')
                  ->orWhereNotIn('estado', ['cumplido', 'atraso', 'no_cumplido', 'con atraso', 'no cumplido']);
            })
            ->whereBetween('fecha', [$inicioVentana->toDateString(), $finVentana->toDateString()])
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


        if ($circuitoNuevo) {
            $tandeo->circuito_id = $circuitoNuevo->id;
            $tandeo->setRelation('circuito', $circuitoNuevo);
        }

        $puntoId = $tandeo->circuito->puntoAbastecimiento?->id;
        $movimiento = $this->mover($tandeo, $nuevoInicio->copy(), false, $puntoId, true, $motivoInicial ?: 'Reprogramación manual', $duracionNueva);
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

        $this->validarHorariosUnicos($movimientos);

        return ['movimientos' => array_values($movimientos), 'advertencias' => $advertencias];
    }

    /** Guarda una reprogramación manual y sus recorridos en una sola transacción. */
    public function aplicarManual(int $tandeoId, Carbon $nuevoInicio, int $usuarioId, array $ajustes = [], array $motivos = [], ?string $motivoInicial = null, ?int $circuitoId = null, ?int $tandeadorId = null, ?int $duracionManual = null, ?int $horasAtraso = null, ?int $cumplimientoId = null, ?string $observacionesCumplimiento = null): string
    {
        $resultado = $this->simularManual($tandeoId, $nuevoInicio, $ajustes, $motivos, $motivoInicial, $circuitoId, $duracionManual);
        $cadena = (string) Str::uuid();

        // Guardamos el historial y los horarios juntos para evitar registros incompletos.
        DB::transaction(function () use ($resultado, $cadena, $usuarioId, $tandeoId, $circuitoId, $tandeadorId, $horasAtraso, $cumplimientoId, $observacionesCumplimiento) {
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

                if ((int) $m['tandeo']->id === $tandeoId) {
                    $circuitoNuevo = circuitosModel::with('zona')->find(
                        $circuitoId ?? $m['tandeo']->circuito_id
                    );
                    $tandeadoresIds = $circuitoNuevo
                        ? $circuitoNuevo->zona->tandeadores()
                            ->where('usuarios.rol', 'tandeador')
                            ->where('usuarios.activo', true)
                            ->pluck('usuarios.id')
                            ->all()
                        : [];

                    $m['tandeo']->update([
                        'circuito_id' => $circuitoNuevo?->id ?? $m['tandeo']->circuito_id,
                        'tandeador_id' => $tandeadoresIds[0] ?? null,
                    ]);
                    $m['tandeo']->tandeadores()->sync($tandeadoresIds);
                    $m['tandeo']->actualizarVersionSync();
                }

                if ((int) $m['tandeo']->id === $tandeoId) {
                    if ($horasAtraso !== null) {
                        $valoresCumplimiento = [
                            'resultado' => 'atraso',
                            'horas_atraso' => $horasAtraso,
                            'motivo' => 'otro',
                            'observaciones' => $observacionesCumplimiento ?: 'Atraso registrado desde Cumplimiento.',
                            'capturado_por_id' => $usuarioId,
                            'fecha_captura' => now(),
                        ];
                        $cumplimiento = $cumplimientoId
                            ? CumplimientoTandeo::where('id', $cumplimientoId)->where('tandeo_id', $tandeoId)->first()
                            : null;
                        if ($cumplimiento) $cumplimiento->update($valoresCumplimiento);
                        else CumplimientoTandeo::create(array_merge($valoresCumplimiento, ['tandeo_id' => $tandeoId]));
                    }
                }
            }

        });

        return $cadena;
    }
    public function aplicarApagon(Carbon $desde, int $usuarioId, array $ajustes = [], array $motivos = []): string
    {
        return $this->guardarIncidencia($this->simularApagon($desde, $ajustes, $motivos), $usuarioId, 'apagon_general');
    }

    public function aplicarFuga(
        Carbon $desde,
        int $usuarioId,
        string $tipoPunto,
        int $horasAtraso,
        array $ajustes = [],
        array $motivos = []
    ): string {
        $etiqueta = $tipoPunto === 'laguna' ? 'Fuga en laguna' : 'Fuga en pozo';
        $resultado = $this->simularApagon($desde, $ajustes, $motivos, $tipoPunto, $horasAtraso, $etiqueta);

        return $this->guardarIncidencia($resultado, $usuarioId, 'fuga_' . $tipoPunto);
    }

    private function guardarIncidencia(array $resultado, int $usuarioId, string $tipoOrigen): string
    {
        $cadena = (string) Str::uuid();

        // Guardamos el historial y los horarios juntos para evitar registros incompletos.
        DB::transaction(function () use ($resultado, $cadena, $usuarioId, $tipoOrigen) {
            foreach ($resultado['movimientos'] as $m) {
                Reprogramacion::create([
                    'tandeo_id'               => $m['tandeo']->id,
                    'cadena_uuid'             => $cadena,
                    'tipo_origen'             => $tipoOrigen,
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

    private function mover(tandeoProgramado $t, Carbon $nuevoInicio, bool $esCadena, ?int $puntoId, bool $manual, ?string $motivo, ?int $duracionNueva = null): array
    {
        $duracion = $t->inicio->diffInMinutes($t->fin);

        return [
            'tandeo'          => $t,
            'inicio_anterior' => $t->inicio->copy(),
            'fin_anterior'    => $t->fin->copy(),
            'inicio_nuevo'    => $nuevoInicio,
            'fin_nuevo'       => $nuevoInicio->copy()->addMinutes($duracionNueva ?? $duracion),
            'es_cadena'       => $esCadena,
            'punto_id'        => $puntoId,
            'manual'          => $manual,
            'motivo'          => $motivo,
            'es_repeticion'   => false,
        ];
    }

    private function seEmpalman(Carbon $inicioA, Carbon $finA, Carbon $inicioB, Carbon $finB): bool
    {
        return $inicioA < $finB && $inicioB < $finA;
    }

    private function validarHorariosUnicos(array $movimientos): void
    {
        $cambios = [];

        foreach ($movimientos as $movimiento) {
            $cambios[$movimiento['tandeo']->id] = [
                'circuito_id' => $movimiento['tandeo']->circuito_id,
                'inicio' => $movimiento['inicio_nuevo'],
            ];
        }

        $tandeos = tandeoProgramado::all();
        $horarios = [];

        foreach ($tandeos as $tandeo) {
            $cambio = $cambios[$tandeo->id] ?? null;
            $circuitoId = $cambio['circuito_id'] ?? $tandeo->circuito_id;
            $inicio = $cambio['inicio'] ?? $tandeo->inicio;
            $clave = $circuitoId . '|' . $inicio->format('Y-m-d H:i');
            $horarios[$clave][] = $cambio !== null;
        }

        foreach ($horarios as $coincidencias) {
            if (count($coincidencias) > 1 && in_array(true, $coincidencias, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'inicio_nuevo' => 'Ya existe un tandeo de este circuito con la misma fecha y hora de inicio.',
                ]);
            }
        }
    }
}
