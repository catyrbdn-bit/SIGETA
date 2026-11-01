<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use Illuminate\Http\Request;
use App\Models\TandeoProgramado;
use App\Models\TandeoRepeticion;
use App\Models\circuitosModel;
use App\Models\usuarioModel;
use App\Models\zonasModel;
use App\Models\cumplimientoTandeo;
use App\Models\Reprogramacion;
use App\Services\repeticionTandeoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class tandeosController extends controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: dato necesario para realizar el proceso
    // salida: muestra la pantalla con los datos consultados
    public function index(repeticionTandeoService $repeticiones)
    {
        $repeticiones->crearSiguientes();

        $tandeos = TandeoProgramado::with([
            'circuito.zona',
            'tandeador',
            'tandeadores',
            // Ordena los cumplimientos para consultar primero el registro más reciente.
            // descripcion: ordena los cumplimientos para consultar primero el registro más reciente.
            // entradas: cumplimientos relacionados con el tandeo
            // salida: devuelve los cumplimientos ordenados por fecha de captura
            'cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            },
        ])
            // Aplica el filtro solo cuando se recibió el dato correspondiente.
            // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
            // entradas: consulta y valor del filtro elegido
            // salida: añade el filtro a la consulta cuando se necesita
            ->when(auth()->user()->rol === 'tandeador', function ($consulta) {
                // Agrega una condición para filtrar los registros consultados.
                // descripcion: agrega una condición para filtrar los registros consultados.
                // entradas: consulta que se está filtrando
                // salida: deja la condición agregada a la consulta
                $consulta->where(function ($filtro) {
                    $filtro->where('tandeador_id', auth()->user()->getKey())
                        // Filtra los registros según la relación que deben cumplir.
                        // descripcion: filtra los registros según la relación que deben cumplir.
                        // entradas: consulta y registros relacionados
                        // salida: añade la condición correspondiente a la consulta
                        ->orWhereHas('tandeadores', function ($personas) {
                            $personas->where('usuarios.id', auth()->user()->getKey());
                        });
                });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $zonas = zonasModel::where('activo', true)->orderBy('nombre')->get();

        return view('tandeos.index', compact('tandeos', 'zonas'));
    }

    // prepara los datos para mostrar el formulario de registro
    // Prepara el formulario para registrar un nuevo dato.
    // descripcion: prepara el formulario para registrar un nuevo dato.
    // entradas: no requiere datos externos
    // salida: muestra el formulario de registro
    public function create()
    {
        $zonas = zonasModel::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $circuitos = circuitosModel::with('zona')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('tandeos.create', compact('zonas', 'circuitos'));
    }

    //Esta función se encarga de almacenar un nuevo tandeo en la base de datos. 
    // Valida los datos de entrada, verifica que el circuito y la zona sean válidos y activos, 
    // y luego crea el tandeo programado. Si se especifica que el tandeo debe repetirse, 
    // también se crean las repeticiones correspondientes. 
    // Valida y guarda la información recibida desde el formulario.
    // descripcion: valida y guarda la información recibida desde el formulario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda el registro y muestra un mensaje o los errores encontrados
    public function store(Request $request)
    {
        // Aqui se validan los datos de entrada
        $datos = $request->validate([
            'zona_id' => ['required', 'exists:zonas,id'],
            'circuito_id' => ['required', 'exists:circuitos,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio_programada' => ['required', 'date_format:H:i'],
            'duracion' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'repetir' => ['nullable', 'boolean'],
            'cada_dias' => ['required_if:repetir,1', 'nullable', 'integer', 'min:1', 'max:365'],
        ]);

        // Verifica que la zona y el circuito sean validos y activos
        $zonaActiva = zonasModel::whereKey($datos['zona_id'])
            ->where('activo', true)
            ->exists();
        // Verifica que el circuito sea valido, activo y pertenezca a la zona seleccionada
        $circuito = circuitosModel::whereKey($datos['circuito_id'])
            ->where('zona_id', $datos['zona_id'])
            ->where('activo', true)
            ->first();
        //Este if verifica si la zona y el circuito son válidos y activos. 
        // Si no lo son, lanza una excepción de validación con un mensaje 
        // de error específico para el campo 'circuito_id'. 
        // Esto asegura que solo se puedan programar tandeos 
        // en circuitos activos que pertenezcan a la zona seleccionada.
        if (! $zonaActiva || ! $circuito) {
            throw ValidationException::withMessages([
                'circuito_id' => 'Selecciona un circuito activo que pertenezca a la zona elegida.',
            ]);
        }

        // Combina la fecha y la hora de inicio en un objeto Carbon
        //el objeto Carbon es una biblioteca de PHP que facilita el manejo de fechas y horas
        $horaInicio = Carbon::parse(
            $datos['fecha'] . ' ' . $datos['hora_inicio_programada']
        );
        // Valida que no exista otro tandeo del mismo circuito con la misma fecha y hora de inicio
        $fechas = [$horaInicio->copy()];
        $serieId = null;
        //este if verifica si el tandeo debe repetirse. Si es asi, calcula las fechas de los tandeos repetidos
        if (! empty($datos['repetir'])) {
            $cadaDias = (int) $datos['cada_dias'];
            $serieId = (string) Str::uuid();
            $fechaLimite = now()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();
            if ($horaInicio->greaterThan(now())) {
                $fechaLimite = $horaInicio->copy()->addDays((int) config('sigeta.ventana_dias', 12))->endOfDay();
            }
            $fechaSiguiente = $horaInicio->copy();

            while (true) {
                $fechaSiguiente->addDays($cadaDias);
                if ($fechaSiguiente->greaterThan($fechaLimite)) {
                    break;
                }
                $fechas[] = $fechaSiguiente->copy();
            }
        }
        // Calcula la duración en minutos del tandeo y obtiene los IDs 
        // de los tandeadores activos de la zona del circuito
        $duracionMinutos = (int) round((float) $datos['duracion'] * 60);
        $tandeadoresIds = $circuito->zona->tandeadores()
            ->where('usuarios.rol', 'tandeador')
            ->where('usuarios.activo', true)
            ->pluck('usuarios.id')
            ->all();
        $tandeadorPrincipal = $tandeadoresIds[0] ?? null;
        // Valida que haya al menos un tandeador activo en la zona del circuito
        // Agrupa los cambios para guardarlos como una sola operación.
        // descripcion: agrupa los cambios para guardarlos como una sola operación.
        // entradas: registros que se actualizan durante la operación
        // salida: confirma los cambios o los revierte si ocurre un error
        DB::transaction(function () use ($fechas, $circuito, $horaInicio, $duracionMinutos, $datos, $serieId, $tandeadoresIds, $tandeadorPrincipal) {
            if ($serieId) {
                TandeoRepeticion::create([
                    'repeticion_uuid' => $serieId,
                    'circuito_id' => $circuito->id,
                    'fecha_ancla' => $horaInicio->toDateString(),
                    'hora_inicio' => $horaInicio->format('H:i:s'),
                    'duracion_minutos' => $duracionMinutos,
                    'tandeador_id' => $tandeadorPrincipal,
                    'cada_dias' => (int) $datos['cada_dias'],
                    'activa' => true,
                ]);
            }
            // Itera sobre las fechas calculadas y crea un tandeo programado para cada una
            foreach ($fechas as $fecha) {
                $inicio = $fecha->copy()->setTimeFromTimeString($horaInicio->format('H:i:s'));
                $this->validarHorarioUnico($circuito->id, $inicio, $serieId);
                $horaFin = $inicio->copy()->addMinutes($duracionMinutos);
                // Crea un nuevo tandeo programado y asocia los tandeadores correspondientes
                $tandeo = TandeoProgramado::create([
                    'circuito_id' => $circuito->id,
                    'fecha' => $inicio->toDateString(),
                    'hora_inicio_programada' => $inicio->format('H:i'),
                    'hora_fin_programada' => $horaFin->format('H:i'),
                    'estado' => 'programado',
                    'tandeador_id' => $tandeadorPrincipal,
                    'repeticion_uuid' => $serieId,
                    'repetir_cada_dias' => $serieId ? (int) $datos['cada_dias'] : null,
                    'fecha_repeticion' => $serieId ? $inicio->toDateString() : null,
                ]);
                $tandeo->tandeadores()->sync($tandeadoresIds);
            }
        });
        // Registra la acción en el historial de actividad del sistema
        app(\App\Services\HistorialActividadService::class)->registrar(
            'tandeos',
            'registro',
            'Se programó un tandeo' . ($serieId ? ' con repetición.' : '.'),
            [
                // Informacion sobre el tandeo registrado
                'circuito' => $circuito->nombre,
                'calles' => $circuito->descripcion,
                'fecha_inicial' => $horaInicio->format('Y-m-d'),
                'hora_inicio' => $horaInicio->format('H:i'),
                'repeticion_dias' => $serieId ? (int) $datos['cada_dias'] : null,
                'tandeos_creados_en_esta_accion' => count($fechas),
            ]
        );
        // Prepara un mensaje de éxito para el usuario, indicando 
        // si se inició una repetición o si el tandeo fue programado exitosamente
        $mensaje = $serieId
            ? '¡Se inició la repetición cada ' . (int) $datos['cada_dias'] . ' días! El sistema seguirá agregando los próximos tandeos.'
            : '¡Tandeo programado exitosamente!';

        return redirect()->route('tandeos.index')->with('success', $mensaje);
    }

    // busca el registro y prepara el formulario de edicion
    // Busca el registro y prepara el formulario para modificarlo.
    // descripcion: busca el registro y prepara el formulario para modificarlo.
    // entradas: identificador del registro relacionado
    // salida: muestra el formulario con los datos actuales
    public function edit($id)
    {
        $tandeo = TandeoProgramado::with('circuito')->findOrFail($id);

        $circuitos = circuitosModel::with('zona')->where('activo', true)->get();

        return view('tandeos.edit', compact('tandeo', 'circuitos'));
    }

    // elimina el registro seleccionado
    // Elimina el registro seleccionado y atiende sus datos relacionados.
    // descripcion: elimina el registro seleccionado y atiende sus datos relacionados.
    // entradas: identificador del registro relacionado
    // salida: elimina el registro y regresa al listado con un mensaje
    public function destroy($id)
    {
        $tandeo = TandeoProgramado::findOrFail($id);
        $serie = $tandeo->repeticion_uuid
            ? TandeoRepeticion::where('repeticion_uuid', $tandeo->repeticion_uuid)->first()
            : null;
        $tandeos = $tandeo->repeticion_uuid
            ? TandeoProgramado::where('repeticion_uuid', $tandeo->repeticion_uuid)->get()
            : collect([$tandeo]);
        $ids = $tandeos->pluck('id');

        // Agrupa los cambios para guardarlos como una sola operación.
        // descripcion: agrupa los cambios para guardarlos como una sola operación.
        // entradas: registros que se actualizan durante la operación
        // salida: confirma los cambios o los revierte si ocurre un error
        DB::transaction(function () use ($serie, $tandeos, $ids) {
            if ($serie && $serie->activa) {
                $serie->update(['activa' => false]);
            }

            cumplimientoTandeo::whereIn('tandeo_id', $ids)->get()
                // Realiza la misma acción con cada registro de la lista.
                // descripcion: realiza la misma acción con cada registro de la lista.
                // entradas: registro que se debe atender
                // salida: actualiza o elimina el registro según la acción indicada
                ->each(fn ($cumplimiento) => $cumplimiento->delete());

            Reprogramacion::whereIn('tandeo_id', $ids)->get()
                // Realiza la misma acción con cada registro de la lista.
                // descripcion: realiza la misma acción con cada registro de la lista.
                // entradas: registro que se debe atender
                // salida: actualiza o elimina el registro según la acción indicada
                ->each(fn ($reprogramacion) => $reprogramacion->delete());

            // Realiza la misma acción con cada registro de la lista.
            // descripcion: realiza la misma acción con cada registro de la lista.
            // entradas: registro que se debe atender
            // salida: actualiza o elimina el registro según la acción indicada
            $tandeos->each(fn ($ocurrencia) => $ocurrencia->delete());
        });

        app(\App\Services\HistorialActividadService::class)->registrar(
            'tandeos',
            'eliminacion',
            'Se eliminó un tandeo y sus registros relacionados.',
            [
                'tandeo_id' => $tandeo->id,
                'circuito_id' => $tandeo->circuito_id,
                'fecha' => $tandeo->fecha?->format('Y-m-d'),
                'hora_inicio' => $tandeo->hora_inicio_programada,
                'repeticion_completa' => (bool) $tandeo->repeticion_uuid,
                'tandeos_eliminados' => $ids->count(),
            ]
        );
        $mensaje = $tandeo->repeticion_uuid
            ? 'Se eliminó la repetición completa y sus registros relacionados.'
            : '¡Tandeo eliminado exitosamente!';

        return redirect()->route('tandeos.index')
            ->with('success', $mensaje);
    }

    // evita repetir el mismo horario para un circuito
    // Comprueba que el horario del circuito respete el ciclo permitido.
    // descripcion: comprueba que el horario del circuito respete el ciclo permitido.
    // entradas: identificador del registro relacionado; fecha u horario que se debe revisar o cambiar
    // salida: continúa si el horario es válido o muestra un error
    private function validarHorarioUnico(int $circuitoId, Carbon $nuevoInicio, ?string $ignorarSerieId = null): void
    {
        $tandeos = TandeoProgramado::where('circuito_id', $circuitoId)
            ->whereDate('fecha', $nuevoInicio->toDateString())
            ->get();

        // Comprueba si algún tandeo de la lista cumple la condición indicada.
        // descripcion: comprueba si algún tandeo de la lista cumple la condición indicada.
        // entradas: tandeo que se está comparando
        // salida: indica si se encontró un tandeo que cumple la condición
        $duplicado = $tandeos->contains(fn ($tandeo) =>
            Carbon::parse($tandeo->fecha->format('Y-m-d') . ' ' . $tandeo->hora_inicio_programada)
                ->format('H:i') === $nuevoInicio->format('H:i')
        );

        if (! $duplicado) {
            $series = TandeoRepeticion::where('activa', true)
                // Aplica el filtro solo cuando se recibió el dato correspondiente.
                // descripcion: aplica el filtro solo cuando se recibió el dato correspondiente.
                // entradas: consulta y valor del filtro elegido
                // salida: añade el filtro a la consulta cuando se necesita
                ->when($ignorarSerieId, fn ($consulta) => $consulta->where('repeticion_uuid', '!=', $ignorarSerieId))
                ->get();

            foreach ($series as $serie) {
                $inicioAncla = Carbon::parse($serie->fecha_ancla->format('Y-m-d') . ' ' . $serie->hora_inicio);
                $intervalo = (int) $serie->cada_dias;

                if ((int) $serie->circuito_id !== $circuitoId
                    || $inicioAncla->format('H:i') !== $nuevoInicio->format('H:i')
                    || $intervalo < 1
                    || $nuevoInicio->lessThanOrEqualTo($inicioAncla)) {
                    continue;
                }

                $dias = $inicioAncla->copy()->startOfDay()->diffInDays($nuevoInicio->copy()->startOfDay());
                if ($dias % $intervalo === 0) {
                    $duplicado = true;
                    break;
                }
            }
        }

        if ($duplicado) {
            throw ValidationException::withMessages([
                'fecha' => 'Ya existe un tandeo de este circuito con la misma fecha y hora de inicio.',
            ]);
        }
    }
}
