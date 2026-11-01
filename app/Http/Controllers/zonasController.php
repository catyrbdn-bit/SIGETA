<?php

namespace App\Http\Controllers;
// archivo del sistema: archivo con acciones para las pantallas del sistema

use Illuminate\Http\Request;
use App\Models\zonasModel;
use App\Models\usuarioModel;

class zonasController extends controller
{
    // consulta y muestra los registros de la pantalla
    // Consulta los registros y prepara la pantalla de listado.
    // descripcion: consulta los registros y prepara la pantalla de listado.
    // entradas: no requiere datos externos
    // salida: muestra la pantalla con los datos consultados
    public function index()
    {
        $zonas = zonasModel::with('tandeadores')->get();
        return view('zonas.index', compact('zonas'));
    }


    //funcion create para mostrar el formulario de creacion de zona
    // Prepara el formulario para registrar un nuevo dato.
    // descripcion: prepara el formulario para registrar un nuevo dato.
    // entradas: no requiere datos externos
    // salida: muestra el formulario de registro
    public function create()
    {
        $tandeadores = usuarioModel::where('rol', 'tandeador')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('zonas.create', compact('tandeadores'));
    }


    //funcion store para guardar la zona en la base de datos
    // Valida y guarda la información recibida desde el formulario.
    // descripcion: valida y guarda la información recibida desde el formulario.
    // entradas: solicitud con los datos enviados por el usuario
    // salida: guarda el registro y muestra un mensaje o los errores encontrados
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
            'tandeadores' => 'nullable|array',
            'tandeadores.*' => 'integer|exists:usuarios,id,rol,tandeador,activo,1',
        ]);

        $zona = zonasModel::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'activo' => $datos['activo'],
        ]);

        $asignaciones = $zona->tandeadores()->sync($datos['tandeadores'] ?? []);

        if (!empty($asignaciones['attached'])) {
            $zona->touch();
        }

        app(\App\Services\HistorialActividadService::class)->registrar(
            'colonias',
            'registro',
            'Se registró una colonia.',
            ['colonia' => $zona->nombre, 'tandeadores_asignados' => $datos['tandeadores'] ?? []]
        );
        return redirect()->route('zonas.index')
            ->with('success', '¡Colonia registrada exitosamente!');
    }

    //funcion edit para mostrar el formulario de edicion de zona
    // Busca el registro y prepara el formulario para modificarlo.
    // descripcion: busca el registro y prepara el formulario para modificarlo.
    // entradas: identificador del registro relacionado
    // salida: muestra el formulario con los datos actuales
    public function edit($id)
    {
        $zona = zonasModel::with('tandeadores')->findOrFail($id);
        $tandeadores = usuarioModel::where('rol', 'tandeador')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('zonas.edit', compact('zona', 'tandeadores'));
    }

    //funcion update para actualizar la zona en la base de datos
    // Valida y guarda los cambios del registro.
    // descripcion: valida y guarda los cambios del registro.
    // entradas: solicitud con los datos enviados por el usuario; identificador del registro relacionado
    // salida: actualiza el registro y muestra un mensaje o los errores encontrados
    public function update(Request $request, $id)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'required|boolean',
            'tandeadores' => 'nullable|array',
            'tandeadores.*' => 'integer|exists:usuarios,id,rol,tandeador,activo,1',
            'actualizar_tandeos_futuros' => 'nullable|boolean',
        ]);

        $zona = zonasModel::findOrFail($id);
        $antes = $zona->only(['nombre', 'activo']);
        $nuevosTandeadores = array_map('intval', $datos['tandeadores'] ?? []);
        $cantidadTandeosActualizados = 0;

        // Agrupa los cambios para guardarlos como una sola operación.
        // descripcion: agrupa los cambios para guardarlos como una sola operación.
        // entradas: registros que se actualizan durante la operación
        // salida: confirma los cambios o los revierte si ocurre un error
        \Illuminate\Support\Facades\DB::transaction(function () use ($zona, $datos, $nuevosTandeadores, &$cantidadTandeosActualizados) {
            $asignaciones = $zona->tandeadores()->sync($nuevosTandeadores);

            $zona->fill([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? $zona->descripcion,
                'activo' => $datos['activo'],
            ]);

            if ($zona->isDirty()) {
                $zona->save();
            } elseif (!empty($asignaciones['attached']) || !empty($asignaciones['detached'])) {
                $zona->touch();
            }

            if (empty($datos['actualizar_tandeos_futuros'])) {
                return;
            }

            // Filtra los registros según la relación que deben cumplir.
            // descripcion: filtra los registros según la relación que deben cumplir.
            // entradas: consulta y registros relacionados
            // salida: añade la condición correspondiente a la consulta
            $tandeosFuturos = \App\Models\tandeoProgramado::whereHas('circuito', function ($consulta) use ($zona) {
                $consulta->where('zona_id', $zona->id);
            })->with('circuito')->get()
                // Conserva solo los registros que cumplen la condición indicada.
                // descripcion: conserva solo los registros que cumplen la condición indicada.
                // entradas: registro que se está revisando
                // salida: indica si el registro se conserva o se descarta
                ->filter(fn ($tandeo) => $tandeo->inicio->greaterThan(now()));

            $tandeadorPrincipal = count($nuevosTandeadores) === 1 ? $nuevosTandeadores[0] : null;

            foreach ($tandeosFuturos as $tandeo) {
                $cambios = $tandeo->tandeadores()->sync($nuevosTandeadores);

                if ($tandeo->tandeador_id != $tandeadorPrincipal) {
                    $tandeo->tandeador_id = $tandeadorPrincipal;
                    $tandeo->save();
                } elseif (!empty($cambios['attached']) || !empty($cambios['detached'])) {
                }

                $cantidadTandeosActualizados++;
            }

            $series = \App\Models\TandeoRepeticion::where('activa', true)
                // Filtra los registros según la relación que deben cumplir.
                // descripcion: filtra los registros según la relación que deben cumplir.
                // entradas: consulta y registros relacionados
                // salida: añade la condición correspondiente a la consulta
                ->whereHas('circuito', function ($consulta) use ($zona) {
                    $consulta->where('zona_id', $zona->id);
                })->get();

            foreach ($series as $serie) {
                if ($serie->tandeador_id != $tandeadorPrincipal) {
                    $serie->tandeador_id = $tandeadorPrincipal;
                    $serie->save();
                }
            }
        });

        app(\App\Services\HistorialActividadService::class)->registrar(
            'colonias',
            'edicion',
            'Se actualizaron los datos de una colonia.',
            [
                'colonia' => $zona->nombre,
                'antes' => $antes,
                'despues' => $zona->only(['nombre', 'activo']),
                'tandeadores_asignados' => $nuevosTandeadores,
                'se_actualizaron_tandeos_futuros' => !empty($datos['actualizar_tandeos_futuros']),
                'tandeos_futuros_actualizados' => $cantidadTandeosActualizados,
            ]
        );

        $mensaje = !empty($datos['actualizar_tandeos_futuros'])
            ? "Colonia actualizada. Se actualizaron {$cantidadTandeosActualizados} tandeos futuros; las próximas repeticiones usarán los tandeadores seleccionados."
            : 'Colonia actualizada exitosamente.';

        return redirect()->route('zonas.index')->with('success', $mensaje);
    }
    // elimina la colonia seleccionada y sus datos asociados
    // Elimina el registro seleccionado y atiende sus datos relacionados.
    // descripcion: elimina el registro seleccionado y atiende sus datos relacionados.
    // entradas: identificador del registro relacionado
    // salida: elimina el registro y regresa al listado con un mensaje
    public function destroy($id)
    {
        $zona = zonasModel::findOrFail($id);
        $datosZona = $zona->only(["nombre", "activo"]);
        $zona->delete();
        app(\App\Services\HistorialActividadService::class)->registrar(
            "colonias", "eliminacion", "Se eliminó una colonia.", ["colonia_eliminada" => $datosZona]
        );

        return redirect()->route('zonas.index')
                        ->with('success', 'Colonia eliminada exitosamente');

    }

    //alertas para confirmar la eliminacion de la zona
    // Prepara la confirmación para eliminar la colonia y sus datos asociados.
    // descripcion: prepara la confirmación para eliminar la colonia y sus datos asociados.
    // entradas: identificador del registro relacionado
    // salida: muestra la pantalla de confirmación
    public function confirmDelete($id)
    {
        $zona = zonasModel::findOrFail($id);
        return view('zonas.delete', compact('zona'));

    }
}

