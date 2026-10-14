<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CumplimientoTandeo;
use App\Models\tandeoProgramado;
use App\Models\zonasModel;

class cumplimientoController extends Controller
{
    public function index()
    {
        $inicioSemana = now()->startOfWeek()->toDateString();
        $finSemana = now()->endOfWeek()->toDateString();

        $tandeos = tandeoProgramado::whereDate('fecha', '>=', $inicioSemana)
            ->whereDate('fecha', '<=', $finSemana)
            ->with('circuito.zona')
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        $cumplimientos = CumplimientoTandeo::whereIn('tandeo_id', $tandeos->pluck('id'))
            ->orderByDesc('fecha_captura')
            ->orderByDesc('id')
            ->get()
            ->unique('tandeo_id')
            ->keyBy('tandeo_id');

        $zonas = zonasModel::where('activo', true)->get();

        return view('cumplimiento.index', compact('tandeos', 'zonas', 'cumplimientos'));
    }

    // Guarda de una sola vez los cambios de las filas editadas.
    public function store(Request $request)
    {
        $datos = $request->validate([
            'cumplimientos' => ['required', 'array'],
            'cumplimientos.*.resultado' => ['nullable', 'in:cumplido,atraso,no_cumplido'],
            'cumplimientos.*.horas_atraso' => ['nullable', 'integer', 'min:1', 'max:24'],
            'cumplimientos.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $tandeosValidos = tandeoProgramado::whereIn('id', array_keys($datos['cumplimientos']))
            ->whereBetween('fecha', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($datos, $tandeosValidos) {
            foreach ($datos['cumplimientos'] as $tandeoId => $fila) {
                if (!in_array((int) $tandeoId, $tandeosValidos)) {
                    continue;
                }

                $resultado = $fila['resultado'] ?? null;
                if (!$resultado || $resultado === 'en_curso') {
                    continue;
                }

                $horasAtraso = $fila['horas_atraso'] ?? null;
                if ($resultado === 'atraso' && !$horasAtraso) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "cumplimientos.$tandeoId.horas_atraso" => 'Indica cuántas horas de atraso tiene este tandeo.',
                    ]);
                }
                if ($resultado !== 'atraso') {
                    $horasAtraso = null;
                }

                $registro = CumplimientoTandeo::where('tandeo_id', $tandeoId)
                    ->orderByDesc('fecha_captura')
                    ->orderByDesc('id')
                    ->first();

                $valores = [
                    'resultado' => $resultado,
                    'horas_atraso' => $horasAtraso,
                    'observaciones' => $fila['observaciones'] ?? null,
                    'capturado_por_id' => auth()->user()->getKey(),
                    'fecha_captura' => now(),
                ];

                if ($registro) {
                    $registro->update($valores);
                } else {
                    CumplimientoTandeo::create(array_merge($valores, ['tandeo_id' => $tandeoId]));
                }
            }
        });

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Cambios de cumplimiento guardados correctamente.');
    }

    // Botón "Apagón general": el registro/reprogramación se atiende en FN.8.
    public function apagonGeneral(Request $request)
    {
        $request->validate(['horas_atraso' => 'required|integer|min:1']);

        $tandeosHoy = tandeoProgramado::whereDate('fecha', now())
            ->where('estado', 'programado')
            ->get();

        foreach ($tandeosHoy as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'apagon_general',
                'capturado_por_id' => auth()->user()->getKey(),
                'fecha_captura' => now(),
            ]);
        }

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Apagón general registrado, se aplicó a todas las zonas.');
    }

    // Botón "Fuga": afecta los tandeos de hoy solo en la zona indicada.
    public function fuga(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'horas_atraso' => 'required|integer|min:1',
        ]);

        $tandeosZona = tandeoProgramado::whereDate('fecha', now())
            ->where('estado', 'programado')
            ->whereHas('circuito', function ($query) use ($request) {
                $query->where('zona_id', $request->zona_id);
            })
            ->get();

        foreach ($tandeosZona as $tandeo) {
            CumplimientoTandeo::create([
                'tandeo_id' => $tandeo->id,
                'resultado' => 'atraso',
                'horas_atraso' => $request->horas_atraso,
                'motivo' => 'fuga',
                'capturado_por_id' => auth()->user()->getKey(),
                'fecha_captura' => now(),
            ]);
        }

        return redirect()->route('cumplimiento.index')
            ->with('success', 'Fuga registrada, se aplicó a la zona seleccionada.');
    }
}
