@extends('layouts.app')

@section('titulo', 'Programar tandeo - SIGETA')

@section('estilos')
<style>

        * {
            box-sizing: border-box;
        }
.contenido-vista {
            max-width: 1000px;
            margin: 32px auto;
            padding: 0 20px;
            font-family: Arial, sans-serif;
            color: #243447;
            background: #f5f7fa;
        }

        h1, h2 {
            margin-bottom: 8px;
        }

        .subtitulo {
            margin-top: 0;
            margin-bottom: 24px;
            color: #5d6b78;
        }

        .panel {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #dce3ea;
            border-radius: 10px;
            background: #fff;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid #b9c4ce;
            border-radius: 6px;
            font-size: 15px;
        }

        .campos-horario {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e1e7ed;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef3f7;
        }

        tr[hidden] {
            display: none;
        }

        .codigo {
            font-weight: bold;
            white-space: nowrap;
        }

        .descripcion {
            color: #3d4b57;
        }

        .ayuda {
            margin-top: 8px;
            color: #657482;
            font-size: 14px;
        }

        .mensaje-error {
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid #e2a3a3;
            border-radius: 6px;
            color: #8b2020;
            background: #fff0f0;
        }

        .botones {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 11px 18px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            background: #1f4e79;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }

        .boton-secundario {
            color: #243447;
            background: #e1e7ed;
        }

        @media (max-width: 700px) {
            .campos-horario {
                grid-template-columns: 1fr;
            }

            .tabla-circuitos {
                overflow-x: auto;
            }
        }
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
<h1>SIGETA</h1>
    <h2>Programar nuevo tandeo</h2>
    <p class="subtitulo">Selecciona primero la colonia y después el circuito o las calles.</p>

    @if ($errors->any())
        <div class="mensaje-error">
            <strong>Revisa la información:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('storeTandeo') }}" method="POST">
        @csrf

        <section class="panel">
            <label for="zona_id">1. Selecciona la colonia</label>
            <select id="zona_id" name="zona_id" required>
                <option value="">Selecciona una colonia</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}" @selected(old('zona_id') == $zona->id)>
                        {{ $zona->nombre }}
                    </option>
                @endforeach
            </select>
        </section>

        <section class="panel" id="seccion-circuitos" hidden>
            <label>2. Selecciona el circuito por sus calles o descripción</label>
            <p class="ayuda">
                La tabla solo mostrará circuitos de la colonia seleccionada.
                El código se muestra como referencia.
            </p>

            <div class="tabla-circuitos">
                <table>
                    <thead>
                        <tr>
                            <th>Elegir</th>
                            <th>Código</th>
                            <th>Calles o descripción</th>
                            <th>Duración configurada</th>
                        </tr>
                    </thead>
                    <tbody id="filas-circuitos">
                        @foreach ($circuitos as $circuito)
                            <tr data-zona-id="{{ $circuito->zona_id }}" hidden>
                                <td>
                                    <input
                                        type="radio"
                                        name="circuito_id"
                                        value="{{ $circuito->id }}"
                                        data-duracion="{{ $circuito->duracion }}"
                                        @checked(old('circuito_id') == $circuito->id)
                                    >
                                </td>
                                <td class="codigo">{{ $circuito->nombre }}</td>
                                <td class="descripcion">
                                    {{ $circuito->descripcion ?: 'Sin descripción registrada' }}
                                </td>
                                <td>{{ $circuito->duracion }} horas</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p id="sin-circuitos" class="ayuda" hidden>
                Esta colonia no tiene circuitos activos registrados.
            </p>
        </section>

        <section class="panel">
            <h3>3. Fecha y horario</h3>

            <div class="campos-horario">
                <div>
                    <label for="fecha">Fecha</label>
                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="{{ old('fecha') }}"
                        required
                    >
                    <p id="fecha-legible" aria-live="polite"></p>
                </div>
                

                <div>
                    <label for="hora_inicio_programada">Hora de inicio</label>
                    <input
                        type="time"
                        id="hora_inicio_programada"
                        name="hora_inicio_programada"
                        value="{{ old('hora_inicio_programada') }}"
                        required
                    >
                </div>

                <div>
                    <label for="duracion">Duración en horas</label>
                    <input
                        type="number"
                        id="duracion"
                        name="duracion"
                        min="0.25"
                        max="24"
                        step="0.25"
                        value="{{ old('duracion') }}"
                        required
                    >
                    <div class="ayuda">
                        Se completa con la duración del circuito seleccionado, pero puedes cambiarla para este tandeo.
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <label for="tandeador_id">Tandeador asignado</label>
            <select id="tandeador_id" name="tandeador_id">
                <option value="">Sin asignar</option>
                @foreach ($tandeadores as $tandeador)
                    <option value="{{ $tandeador->id }}" @selected(old('tandeador_id') == $tandeador->id)>
                        {{ $tandeador->nombre }}
                    </option>
                @endforeach
            </select>
        </section>

        <div class="botones">
            <a href="{{ route('tandeos.index') }}" class="boton boton-secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); return false; }">Volver</a>
            <button type="submit" class="boton">Registrar tandeo</button>
        </div>
    </form>

    <script>
        const zonaSelect = document.getElementById('zona_id');
        const seccionCircuitos = document.getElementById('seccion-circuitos');
        const filasCircuitos = Array.from(
            document.querySelectorAll('#filas-circuitos tr')
        );
        const mensajeSinCircuitos = document.getElementById('sin-circuitos');
        const duracionInput = document.getElementById('duracion');

        function mostrarCircuitos() {
            const zonaSeleccionada = zonaSelect.value;
            let cantidadVisibles = 0;

            filasCircuitos.forEach(function (fila) {
                const visible = zonaSeleccionada !== ''
                    && fila.dataset.zonaId === zonaSeleccionada;

                fila.hidden = !visible;

                const radio = fila.querySelector('input[name="circuito_id"]');
                radio.required = visible;

                if (!visible && radio.checked) {
                    radio.checked = false;
                }

                if (visible) {
                    cantidadVisibles++;
                }
            });

            seccionCircuitos.hidden = zonaSeleccionada === '';
            mensajeSinCircuitos.hidden =
                zonaSeleccionada === '' || cantidadVisibles > 0;

            const circuitoMarcado = document.querySelector(
                'input[name="circuito_id"]:checked'
            );

            if (circuitoMarcado && !duracionInput.value) {
                duracionInput.value = circuitoMarcado.dataset.duracion;
            }

            if (!zonaSeleccionada) {
                duracionInput.value = '';
            }
        }

        zonaSelect.addEventListener('change', function () {
            filasCircuitos.forEach(function (fila) {
                const radio = fila.querySelector('input[name="circuito_id"]');
                radio.checked = false;
            });

            duracionInput.value = '';
            mostrarCircuitos();
        });

        document.querySelectorAll('input[name="circuito_id"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                duracionInput.value = this.dataset.duracion;
            });
        });


        const campoFecha = document.getElementById('fecha');
        const fechaLegible = document.getElementById('fecha-legible');

        function mostrarFechaLegible() {
            if (!campoFecha.value) {
                fechaLegible.textContent = '';
                return;
            }

            // Mediodía evita que la zona horaria cambie el día mostrado.
            const fecha = new Date(campoFecha.value + 'T12:00:00');
            const opciones = {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            };

            let texto = new Intl.DateTimeFormat('es-MX', opciones)
                .format(fecha)
                .replace(',', '');

            fechaLegible.textContent = texto.charAt(0).toUpperCase() + texto.slice(1);
        }

        campoFecha.addEventListener('change', mostrarFechaLegible);
        mostrarFechaLegible();

        mostrarCircuitos();
    </script>
</div>
@endsection
