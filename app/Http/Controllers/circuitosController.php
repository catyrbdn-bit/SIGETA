<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\CircuitosModel;
use App\Models\ZonasModel;
use App\Models\PuntoAbastecimiento;

class CircuitosController extends Controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla con los datos consultados
    public function index()
    {
        $circuitos = CircuitosModel::with('zona', 'puntoAbastecimiento')->get();
        return view('circuitos.index', compact('circuitos'));
    }

    // prepara los datos para mostrar el formulario de registro
    // Prepara el formulario para registrar un nuevo dato.
    // descripcion: prepara el formulario para registrar un nuevo dato.
    // entradas: no requiere datos externos
    // salida: muestra el formulario de registro
    public function create()
    {
        $zonas = ZonasModel::all();
        $puntos = PuntoAbastecimiento::where('activo', true)->orderBy('nombre')->get();
        return view('circuitos.create', compact('zonas', 'puntos'));
    }

    // valida y guarda la informacion recibida
    // Valida y guarda la información recibida desde el formulario.
    // descripcion: valida y guarda la información recibida desde el formulario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda el registro y muestra un mensaje o los errores encontrados
    public function store(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'descripcion' => 'nullable|string|max:255',
            'duracion' => 'required|numeric|min:1',
            'punto_abastecimiento_id' => 'nullable|exists:puntos_abastecimiento,id',
            'activo' => 'required|boolean',
        ]);

        $circuito = CircuitosModel::create([
            'zona_id' => $request->zona_id,
            'nombre' => CircuitosModel::generarCodigo((int) $request->zona_id),
            'descripcion' => $request->descripcion,
            'duracion' => $request->duracion,
            'punto_abastecimiento_id' => $request->punto_abastecimiento_id ?: null,
            'activo' => $request->activo,
        ]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'registro',
            'Se registró un circuito.',
            ['circuito' => $circuito->nombre, 'zona_id' => $circuito->zona_id, 'calles' => $circuito->descripcion]
        );
        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito registrado exitosamente!');
    }

    // busca el registro y prepara el formulario de edicion
    // Busca el registro y prepara el formulario para modificarlo.
    // descripcion: busca el registro y prepara el formulario para modificarlo.
    // entradas: identificador del registro relacionado
    // salida: muestra el formulario con los datos actuales
    public function edit($id)
    {
        $circuito = CircuitosModel::findOrFail($id);
        $zonas = ZonasModel::all();
        $puntos = PuntoAbastecimiento::where('activo', true)->orderBy('nombre')->get();
        return view('circuitos.edit', compact('circuito', 'zonas', 'puntos'));
    }

    // valida y guarda los cambios del registro
    // Valida y guarda los cambios del registro.
    // descripcion: valida y guarda los cambios del registro.
    // entradas: solicitud con los datos enviados por el usuario; identificador del registro relacionado
    // salida: actualiza el registro y muestra un mensaje o los errores encontrados
    public function update(Request $request, $id)
    {
        $circuito = CircuitosModel::findOrFail($id);

        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'descripcion' => 'nullable|string|max:255',
            'duracion' => 'required|integer|min:1',
            'punto_abastecimiento_id' => 'nullable|exists:puntos_abastecimiento,id',
            'activo' => 'required|boolean',
        ]);

        // Si cambia de zona, el código se regenera para que las iniciales coincidan
        $nombre = $circuito->nombre;
        if ((int) $request->zona_id !== (int) $circuito->zona_id) {
            $nombre = CircuitosModel::generarCodigo((int) $request->zona_id);
        }

        $antes = $circuito->only(["zona_id", "nombre", "descripcion", "duracion", "punto_abastecimiento_id", "activo"]);
        $circuito->update([
            'zona_id' => $request->zona_id,
            'nombre' => $nombre,
            'descripcion' => $request->descripcion,
            'duracion' => $request->duracion,
            'punto_abastecimiento_id' => $request->punto_abastecimiento_id ?: null,
            'activo' => $request->activo,
        ]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'edicion',
            'Se actualizaron los datos de un circuito.',
            ['circuito' => $circuito->nombre, 'antes' => $antes, 'despues' => $circuito->only(["zona_id", "nombre", "descripcion", "duracion", "punto_abastecimiento_id", "activo"])]
        );
        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito actualizado exitosamente!');
    }

    // elimina el registro seleccionado
    // Elimina el registro seleccionado y atiende sus datos relacionados.
    // descripcion: elimina el registro seleccionado y atiende sus datos relacionados.
    // entradas: identificador del registro relacionado
    // salida: elimina el registro y regresa al listado con un mensaje
    public function destroy($id)
    {
        $circuito = CircuitosModel::findOrFail($id);

        $tandeosAsociados = $circuito->tandeos()
            // Ordena los cumplimientos para consultar primero el registro más reciente.
            // descripcion: ordena los cumplimientos para consultar primero el registro más reciente.
            // entradas: cumplimientos relacionados con el tandeo
            // salida: devuelve los cumplimientos ordenados por fecha de captura
            ->with(['cumplimientos' => function ($consulta) {
                $consulta->orderByDesc('fecha_captura')->orderByDesc('id');
            }])
            ->orderBy('fecha')
            ->orderBy('hora_inicio_programada')
            ->get();

        if ($tandeosAsociados->count() > 0) {
            // si tiene historial: no se puede borrar, se muestra la lista de asociados
            // y se ofrece la opción de desactivar
            return view('circuitos.tandeosAsociados', compact('circuito', 'tandeosAsociados'));
        }

        // si no hay tandeos asociados: se puede eliminar de forma segura
        $datosCircuito = $circuito->only(["zona_id", "nombre", "descripcion", "duracion"]);
        $circuito->delete();
        app(\App\Services\HistorialActividadService::class)->registrar(
            "circuitos", "eliminacion", "Se eliminó un circuito.", ["circuito_eliminado" => $datosCircuito]
        );

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito eliminado exitosamente!');
    }

    // desactiva el circuito seleccionado
    // Desactiva el circuito para que no se use en nuevos tandeos.
    // descripcion: desactiva el circuito para que no se use en nuevos tandeos.
    // entradas: identificador del registro relacionado
    // salida: guarda el cambio y regresa al listado con un mensaje
    public function desactivar($id)
    {
        $circuito = CircuitosModel::findOrFail($id);
        $circuito->update(['activo' => false]);

        app(\App\Services\HistorialActividadService::class)->registrar(
            'circuitos',
            'edicion',
            'Se desactivó un circuito.',
            ['circuito' => $circuito->nombre, 'activo' => false]
        );

        return redirect()->route('circuitos.index')
            ->with('success', '¡Circuito desactivado exitosamente!');
    }

    // muestra la confirmacion para eliminar la colonia
    // Prepara la confirmación para eliminar la colonia y sus datos asociados.
    // descripcion: prepara la confirmación para eliminar la colonia y sus datos asociados.
    // entradas: identificador del registro relacionado
    // salida: muestra la pantalla de confirmación
    public function confirmDelete($id)
    {
        $circuito = CircuitosModel::findOrFail($id);
        return view('circuitos.delete', compact('circuito'));
    }
}