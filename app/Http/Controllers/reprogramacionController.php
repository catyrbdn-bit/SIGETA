<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use App\Models\TandeoProgramado;
use App\Models\CircuitosModel;
use App\Models\UsuarioModel;
use App\Services\ReprogramacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReprogramacionController extends Controller
{
    // recibe los datos y servicios que necesita la clase
    // Recibe y guarda los datos que necesita este componente.
    // descripcion: recibe y guarda los datos que necesita este componente.
    // entradas: dato necesario para realizar el proceso
    // salida: deja preparados los datos para usarlos en el componente
    public function __construct(private ReprogramacionService $servicio)
    {
    }

    // prepara la vista previa de una reprogramacion manual
    // Calcula una reprogramación manual y prepara la vista previa.
    // descripcion: calcula una reprogramación manual y prepara la vista previa.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra los cambios propuestos antes de guardarlos
    public function previaManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasAtraso] = $this->datosManual($request);
        $resultado = $this->servicio->simularManual($id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $duracionManual);
        $desde = $inicio;
        $modo = 'manual';
        $origenCumplimiento = $request->boolean('origen_cumplimiento');
        $cumplimientoId = $request->input('cumplimiento_id');
        $cumplimientoObservaciones = $request->input('cumplimiento_observaciones', '');
        $periodo = $request->input('periodo', 'semana');
        $fechaConsulta = $request->input('fecha_consulta', now()->toDateString());
        $returnTo = $this->destinoRegreso($request->input('return_to', 'tandeos.index'));
        $urlRegreso = $this->urlRegreso($returnTo, $periodo, $fechaConsulta);
        $tandeoId = $id;
        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId', 'circuitoId', 'tandeadorId', 'duracionManual', 'horasAtraso', 'origenCumplimiento', 'cumplimientoId', 'cumplimientoObservaciones', 'periodo', 'fechaConsulta', 'returnTo', 'urlRegreso'));
    }

    // valida y guarda la reprogramacion manual
    // Confirma y guarda la reprogramación manual.
    // descripcion: confirma y guarda la reprogramación manual.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda los cambios y regresa con un mensaje
    public function storeManual(Request $request)
    {
        [$id, $inicio, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasAtraso] = 
        $this->datosManual($request);
        
        $origenCumplimiento = $request->boolean('origen_cumplimiento');
        $horasCumplimiento = $origenCumplimiento ? $horasAtraso : null;
        $cumplimientoId = $origenCumplimiento && $request->filled('cumplimiento_id') ? (int) $request->cumplimiento_id : null;
        
        $observacionesCumplimiento = $origenCumplimiento ? $request->input('cumplimiento_observaciones') : null;
        $this->servicio->aplicarManual($id, $inicio, (int) auth()->user()->id, $ajustes, $motivos, $motivo, $circuitoId, $tandeadorId, $duracionManual, $horasCumplimiento, $cumplimientoId, $observacionesCumplimiento);
        $mensaje = $origenCumplimiento
            ? 'Reprogramación confirmada y atraso guardado en Cumplimiento.'
            : 'Horario reprogramado y tandeos afectados recorridos correctamente.';

        $returnTo = $this->destinoRegreso($request->input('return_to', $origenCumplimiento ? 'cumplimiento.index' : 'tandeos.index'));
        $destino = $this->urlRegreso(
            $returnTo,
            $request->input('periodo', 'semana'),
            $request->input('fecha_consulta', now()->toDateString())
        );

        return redirect($destino)->with('success', $mensaje);
    }

    // organiza los datos enviados para la reprogramacion manual
    // Organiza los datos recibidos para continuar con la reprogramación.
    // descripcion: organiza los datos recibidos para continuar con la reprogramación.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: devuelve los datos de la solicitud en un formato preparado
    private function datosManual(Request $request): array
    {
        $request->validate([
            'tandeo_id' => ['required', 'exists:tandeos_programados,id'],
            'circuito_id' => ['required', 'exists:circuitos,id'],
            'tandeador_id' => ['nullable', 'exists:usuarios,id'],
            'inicio_nuevo' => ['nullable', 'date'],
            'fecha_nueva' => ['nullable', 'date'],
            'hora_inicio_nueva' => ['nullable', 'date_format:H:i'],
            'hora_fin_nueva' => ['nullable', 'date_format:H:i'],
            'duracion_manual' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'horas_atraso' => ['nullable', 'integer', 'min:1', 'max:24'],
            'origen_cumplimiento' => ['nullable', 'boolean'],
            'cumplimiento_id' => ['nullable', 'exists:cumplimiento_tandeos,id'],
            'cumplimiento_observaciones' => ['nullable', 'string', 'max:1000'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo_inicio' => ['nullable', 'string', 'max:255'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $id = (int) $request->tandeo_id;
        [$ajustes, $motivos] = $this->leerAjustes($request);
        $circuitoId = (int) $request->circuito_id;
        $tandeadorId = $request->tandeador_id ? (int) $request->tandeador_id : null;

        if ($tandeadorId) {
            $circuito = CircuitosModel::find($circuitoId);
            $asignado = $circuito && UsuarioModel::whereKey($tandeadorId)
                ->where('rol', 'tandeador')
                // Filtra los registros según la relación que deben cumplir.
                // descripcion: filtra los registros según la relación que deben cumplir.
                // entradas: consulta y registros relacionados
                // salida: añade la condición correspondiente a la consulta
                ->whereHas('zonas', function ($consulta) use ($circuito) {
                    $consulta->where('zonas.id', $circuito->zona_id);
                })
                ->exists();

            if (!$asignado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tandeador_id' => 'El tandeador seleccionado no está asignado a la colonia de este circuito.',
                ]);
            }
        }

        $inicioTexto = $request->input("nuevo.$id") ?: $request->input('inicio_nuevo');
        if (! $inicioTexto && $request->filled('fecha_nueva') && $request->filled('hora_inicio_nueva')) {
            $inicioTexto = $request->fecha_nueva . ' ' . $request->hora_inicio_nueva;
        }
        if (! $inicioTexto) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'inicio_nuevo' => 'Indica la nueva fecha y hora del tandeo.',
            ]);
        }

        $horasAtraso = $request->boolean('origen_cumplimiento') && $request->filled('horas_atraso')
            ? (int) $request->input('horas_atraso')
            : null;
        $duracionManual = $request->input('duracion_manual');
        if ($duracionManual === null && $request->filled('fecha_nueva') && $request->filled('hora_fin_nueva')) {
            $finNuevo = Carbon::parse($request->fecha_nueva . ' ' . $request->hora_fin_nueva);
            $inicioNuevo = Carbon::parse($inicioTexto);

            if ($finNuevo->lessThanOrEqualTo($inicioNuevo)) {
                $finNuevo->addDay();
            }

            $duracionManual = (int) $inicioNuevo->diffInMinutes($finNuevo);
        }

        $motivo = trim((string) $request->input("motivo.$id", $request->input('motivo_inicio', 'Reprogramación manual')));
        unset($ajustes[$id]);
        unset($motivos[$id]);

        return [$id, Carbon::parse($inicioTexto), $ajustes, $motivos, $motivo ?: 'Reprogramación manual', $circuitoId, $tandeadorId, $duracionManual !== null ? (int) $duracionManual : null, $horasAtraso];
    }

    // limita el regreso a una de las pantallas permitidas
    // descripcion: evita regresar a una direccion externa o no reconocida
    // entradas: nombre de la pantalla a la que se debe regresar
    // salida: nombre de ruta permitido
    private function destinoRegreso(?string $destino): string
    {
        return in_array($destino, ['dashboard', 'tandeos.index', 'cumplimiento.index'], true)
            ? $destino
            : 'tandeos.index';
    }

    // prepara la direccion para regresar a la pantalla de origen
    // descripcion: agrega los datos del periodo cuando el origen es cumplimiento
    // entradas: pantalla de origen y periodo que se estaba consultando
    // salida: direccion segura de regreso
    private function urlRegreso(string $destino, string $periodo, string $fecha, array $filtros = []): string
    {
        if (in_array($destino, ['cumplimiento.index', 'dashboard'], true)) {
            if (!in_array($periodo, ['mes', 'semana', 'dia'], true)) {
                $periodo = 'semana';
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $fecha = now()->toDateString();
            }

            $parametros = ['periodo' => $periodo, 'fecha' => $fecha];
            if ($destino === 'dashboard') {
                $parametros['buscar'] = (string) ($filtros['buscar'] ?? '');
                $parametros['estado'] = (string) ($filtros['estado'] ?? 'todos');
            }

            return route($destino, $parametros);
        }

        return route($destino);
    }
    // Vista previa: calcula los movimientos pero NO guarda nada.
    // También se usa para "Recalcular" con los ajustes escritos a mano.
    // Calcula los tandeos afectados por un apagón y prepara la vista previa.
    // descripcion: calcula los tandeos afectados por un apagón y prepara la vista previa.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra los cambios propuestos antes de guardarlos
    public function previa(Request $request)
    {
        $request->validate([
            'desde'    => ['nullable', 'date'],
            'nuevo.*'  => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = $request->filled('desde')
            ? Carbon::parse($request->desde)
            : now()->startOfMinute();

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $resultado = $this->servicio->simularApagon($desde, $ajustes, $motivos);

        $modo = 'apagon';
        $tandeoId = null;
        $periodo = $request->input('periodo', 'semana');
        $fechaConsulta = $request->input('fecha_consulta', now()->toDateString());
        $returnTo = $this->destinoRegreso($request->input('return_to', 'cumplimiento.index'));
        $busqueda = $request->input('buscar', '');
        $estado = $request->input('estado', 'todos');
        $filtros = ['buscar' => $busqueda, 'estado' => $estado];
        $urlRegreso = $this->urlRegreso($returnTo, $periodo, $fechaConsulta, $filtros);
        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId', 'periodo', 'fechaConsulta', 'returnTo', 'urlRegreso', 'busqueda', 'estado'));
    }

    // Confirmación: aplica los movimientos y guarda el historial
    // Valida y guarda la información recibida desde el formulario.
    // descripcion: valida y guarda la información recibida desde el formulario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda el registro y muestra un mensaje o los errores encontrados
    public function store(Request $request)
    {
        $request->validate([
            'desde'    => ['required', 'date'],
            'nuevo.*'  => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = Carbon::parse($request->desde);

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $this->servicio->aplicarApagon($desde, (int) auth()->user()->id, $ajustes, $motivos);

        $returnTo = $this->destinoRegreso($request->input('return_to', 'cumplimiento.index'));
        $filtros = ['buscar' => $request->input('buscar', ''), 'estado' => $request->input('estado', 'todos')];
        $destino = $this->urlRegreso(
            $returnTo,
            $request->input('periodo', 'semana'),
            $request->input('fecha_consulta', now()->toDateString()),
            $filtros
        );

        return redirect($destino)->with('success', 'Apagón general registrado y tandeos reprogramados.');
    }

    // prepara la vista previa de la fuga
    // Calcula los tandeos afectados por una fuga y prepara la vista previa.
    // descripcion: calcula los tandeos afectados por una fuga y prepara la vista previa.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: muestra los cambios propuestos antes de guardarlos
    public function previaFuga(Request $request)
    {
        $datos = $request->validate([
            'tipo_fuga' => ['required', 'in:laguna,pozo'],
            'horas_atraso' => ['required', 'integer', 'min:1', 'max:24'],
            'desde' => ['nullable', 'date'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMinute();
        [$ajustes, $motivos] = $this->leerAjustes($request);
        $tipoFuga = $datos['tipo_fuga'];
        $horasAtraso = (int) $datos['horas_atraso'];
        $etiqueta = $tipoFuga === 'laguna' ? 'Fuga en laguna' : 'Fuga en pozo';

        $resultado = $this->servicio->simularApagon(
            $desde,
            $ajustes,
            $motivos,
            $tipoFuga,
            $horasAtraso,
            $etiqueta
        );

        $modo = 'fuga';
        $tandeoId = null;
        $periodo = $request->input('periodo', 'semana');
        $fechaConsulta = $request->input('fecha_consulta', now()->toDateString());
        $returnTo = $this->destinoRegreso($request->input('return_to', 'cumplimiento.index'));
        $busqueda = $request->input('buscar', '');
        $estado = $request->input('estado', 'todos');
        $filtros = ['buscar' => $busqueda, 'estado' => $estado];
        $urlRegreso = $this->urlRegreso($returnTo, $periodo, $fechaConsulta, $filtros);

        return view('reprogramaciones.previa', compact('desde', 'resultado', 'modo', 'tandeoId', 'tipoFuga', 'horasAtraso', 'periodo', 'fechaConsulta', 'returnTo', 'urlRegreso', 'busqueda', 'estado'));
    }

    // valida y guarda la reprogramacion por fuga
    // Confirma y guarda la reprogramación por fuga.
    // descripcion: confirma y guarda la reprogramación por fuga.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda los cambios y regresa con un mensaje
    public function storeFuga(Request $request)
    {
        $datos = $request->validate([
            'tipo_fuga' => ['required', 'in:laguna,pozo'],
            'horas_atraso' => ['required', 'integer', 'min:1', 'max:24'],
            'desde' => ['required', 'date'],
            'nuevo.*' => ['nullable', 'date'],
            'motivo.*' => ['nullable', 'string', 'max:255'],
        ]);

        [$ajustes, $motivos] = $this->leerAjustes($request);

        $this->servicio->aplicarFuga(
            Carbon::parse($datos['desde']),
            (int) auth()->user()->id,
            $datos['tipo_fuga'],
            (int) $datos['horas_atraso'],
            $ajustes,
            $motivos
        );

        $nombre = $datos['tipo_fuga'] === 'laguna' ? 'Laguna' : 'Pozo';

        $returnTo = $this->destinoRegreso($request->input('return_to', 'cumplimiento.index'));
        $filtros = ['buscar' => $request->input('buscar', ''), 'estado' => $request->input('estado', 'todos')];
        $destino = $this->urlRegreso(
            $returnTo,
            $request->input('periodo', 'semana'),
            $request->input('fecha_consulta', now()->toDateString()),
            $filtros
        );

        return redirect($destino)->with('success', "Fuga en {$nombre} registrada y tandeos afectados reprogramados.");
    }

    /**
     * Separa lo que la persona cambió a mano de lo que propuso el sistema.
     * Un horario cuenta como ajustado si es distinto al propuesto,
     * o si ya venía ajustado de un recálculo anterior.
     */
    // Organiza los horarios modificados en la vista previa.
    // descripcion: organiza los horarios modificados en la vista previa.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: devuelve los ajustes para volver a calcular los movimientos
    private function leerAjustes(Request $request): array
    {
        $nuevos     = $request->input('nuevo', []);
        $propuestos = $request->input('propuesto', []);
        $previos    = $request->input('manual_prev', []);
        $textos     = $request->input('motivo', []);
        $bases      = $request->input('motivo_base', []);

        $ajustes = [];
        foreach ($nuevos as $id => $valor) {
            if (! $valor) {
                continue;
            }

            $cambio = $valor !== ($propuestos[$id] ?? null) || ! empty($previos[$id]);

            if ($cambio) {
                $ajustes[(int) $id] = Carbon::parse($valor);
            }
        }

        $motivos = [];
        foreach ($textos as $id => $texto) {
            $texto = trim((string) $texto);

            if ($texto !== '' && $texto !== ($bases[$id] ?? '')) {
                $motivos[(int) $id] = $texto;
            }
        }

        return [$ajustes, $motivos];
    }
}
