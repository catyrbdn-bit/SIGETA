{{-- vista de reportes con filtros y vista previa --}}
{{-- archivo del sistema: pantalla para consultar y descargar reportes --}}
@extends('layouts.app')

@section('titulo', 'Reportes')

@section('content')
<div class="contenido-vista">
    <div class="mb-4">
        <h1 class="titulo-vista">Reportes</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" id="formulario-reportes" data-filtros-persistentes="reportes">
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3">Filtros de consulta</h3>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="zona_id">Colonia</label>
                        <select class="form-select" id="zona_id" name="zona_id">
                            <option value="">Todas las colonias</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona->id }}" @selected((string) $zonaSeleccionada === (string) $zona->id)>{{ $zona->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="circuito_id">Circuito</label>
                        <select class="form-select" id="circuito_id" name="circuito_id">
                            <option value="">Todos los circuitos</option>
                            @foreach ($circuitos as $circuito)
                                <option value="{{ $circuito->id }}" @selected((string) $circuitoSeleccionado === (string) $circuito->id)>{{ $circuito->nombre }} ({{ $circuito->zona->nombre }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="tandeador_id">Tandeador</label>
                        <select class="form-select" id="tandeador_id" name="tandeador_id">
                            <option value="">Selecciona un tandeador</option>
                            @foreach ($tandeadores as $tandeador)
                                <option value="{{ $tandeador->id }}" @selected((string) $tandeadorSeleccionado === (string) $tandeador->id)>
                                    {{ $tandeador->nombre }} {{ $tandeador->apellido_paterno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="{{ $desde }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="{{ $hasta }}">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <a href="{{ route('reportes.index', ['limpiar' => 1]) }}" class="btn btn-outline-secondary" data-quitar-filtros="reportes">Quitar filtros</a>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="h5 mb-3">Elige el reporte que deseas revisar</h3>
        <div class="row g-3 mb-4">
            @php
                $opcionesReportes = [
                    'cumplimiento-zona' => ['Tandeos cumplidos y atrasados por colonia', 'Compara los resultados y muestra el porcentaje de efectividad.'],
                    'reprogramaciones-motivo' => ['Reprogramaciones por motivo', 'Resume las causas registradas en el periodo.'],
                    'cumplimiento-circuito' => ['Cumplimiento detallado por circuito', 'Muestra tandeos programados, ejecutados y con desfase.'],
                    'pendientes' => ['Tandeos pendientes de registrar', 'Muestra tandeos terminados que aún no tienen cumplimiento.'],
                    'atrasos-colonia' => ['Atrasos por colonia', 'Ordena los tandeos por días de atraso y muestra el motivo.'],
                    'resumen-semanal-tandeador' => ['Resumen semanal por tandeador', 'Selecciona un tandeador para revisar e imprimir únicamente sus horarios de esta semana en PDF.'],
                ];
            @endphp
            @foreach ($opcionesReportes as $valorTipo => $opcion)
                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h4 class="h6">{{ $opcion[0] }}</h4>
                            <p class="text-muted">{{ $opcion[1] }}</p>
                            <button type="submit" class="btn btn-primary mt-auto"
                                formaction="{{ route('reportes.vista-previa', ['tipo' => $valorTipo]) }}">
                                Ver vista previa
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </form>

    @if (isset($tipoReporte))
        <section class="card" id="vista-previa-reporte">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <h3 class="h4 mb-1">Vista previa: {{ $tituloReporte }}</h3>
                        <p class="text-muted mb-0">Periodo: {{ $periodo }} · Colonia: {{ $zonaReporte }} · Circuito: {{ $circuitoReporte }}</p>
                    </div>
                    @if ($tipoReporte === 'cumplimiento-circuito')
                        <a class="btn btn-primary descargar-reporte"
                            href="{{ route('reportes.cumplimiento.circuito.excel', request()->only(['zona_id', 'circuito_id', 'desde', 'hasta', 'tandeador_id'])) }}"
                            data-nombre="cumplimiento-por-circuito.xlsx">Descargar Excel</a>
                    @else
                        <a class="btn btn-primary descargar-reporte"
                            href="{{ route('reportes.pdf', array_merge(['tipo' => $tipoReporte], request()->only(['zona_id', 'circuito_id', 'desde', 'hasta', 'tandeador_id']))) }}"
                            data-nombre="reporte-{{ $tipoReporte }}.pdf">Descargar PDF</a>
                    @endif
                </div>

                @if ($tipoReporte === 'cumplimiento-zona')
                    <section id="grafica-comparativa" class="mb-4 p-3 bg-light rounded" aria-label="Gráfica comparativa de cumplimiento por colonia">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h4 class="h5 mb-1">Comparación de resultados por colonia</h4>
                                <p class="text-muted mb-0">Cada barra representa el total de cumplimientos registrados en la colonia.</p>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-2">
                                <button class="btn btn-outline-primary" id="boton-descargar-grafica" type="button" aria-expanded="false" aria-controls="opciones-descarga-grafica">Descargar gráfica</button>
                                <div class="d-none flex-wrap gap-2" id="opciones-descarga-grafica">
                                    <button class="btn btn-outline-secondary" type="button" data-descargar-grafica="pastel" @disabled(empty($filasPreview))>Gráfica pastel</button>
                                    <button class="btn btn-outline-secondary" type="button" data-descargar-grafica="barras" @disabled(empty($filasPreview))>Gráfica de barras</button>
                                </div>
                            </div>
                        </div>
                        @forelse ($filasPreview as $fila)
                            @php
                                $totalColonia = max(1, $fila['total']);
                                $porcentajeColoniaCumplido = ($fila['cumplidos'] / $totalColonia) * 100;
                                $porcentajeColoniaAtrasado = ($fila['atrasados'] / $totalColonia) * 100;
                                $porcentajeColoniaNoCumplido = ($fila['no_cumplidos'] / $totalColonia) * 100;
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>{{ $fila['zona'] }}</strong>
                                    <span>{{ $fila['total'] }} registros</span>
                                </div>
                                <div class="d-flex w-100 overflow-hidden rounded" role="img" aria-label="{{ $fila['zona'] }}: {{ $fila['cumplidos'] }} cumplidos, {{ $fila['atrasados'] }} con atraso y {{ $fila['no_cumplidos'] }} no cumplidos" style="height: 24px; background:#e4e9ee;">
                                    @if ($fila['cumplidos'])<span style="width:{{ $porcentajeColoniaCumplido }}%; background:#198754"></span>@endif
                                    @if ($fila['atrasados'])<span style="width:{{ $porcentajeColoniaAtrasado }}%; background:#f0ad4e"></span>@endif
                                    @if ($fila['no_cumplidos'])<span style="width:{{ $porcentajeColoniaNoCumplido }}%; background:#dc3545"></span>@endif
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-1 small">
                                    <span><span class="badge" style="background:#198754">&nbsp;</span> Cumplidos: {{ $fila['cumplidos'] }}</span>
                                    <span><span class="badge" style="background:#f0ad4e">&nbsp;</span> Con atraso: {{ $fila['atrasados'] }}</span>
                                    <span><span class="badge" style="background:#dc3545">&nbsp;</span> No cumplidos: {{ $fila['no_cumplidos'] }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay cumplimientos para comparar con los filtros seleccionados.</p>
                        @endforelse
                    </section>
                    <script>
                        (() => {
                            const filasGrafica = @json($filasPreview);
                            const botonDescargar = document.getElementById('boton-descargar-grafica');
                            const opcionesDescarga = document.getElementById('opciones-descarga-grafica');
                            const coloresGrafica = {
                                cumplidos: '#198754',
                                atrasados: '#f0ad4e',
                                no_cumplidos: '#dc3545',
                            };
                            const etiquetasGrafica = {
                                cumplidos: 'Cumplidos',
                                atrasados: 'Con atraso',
                                no_cumplidos: 'No cumplidos',
                            };

                            if (!botonDescargar || !opcionesDescarga) {
                                return;
                            }

                            botonDescargar.addEventListener('click', () => {
                                const desplegado = !opcionesDescarga.classList.contains('d-none');
                                opcionesDescarga.classList.toggle('d-none', desplegado);
                                opcionesDescarga.classList.toggle('d-flex', !desplegado);
                                botonDescargar.setAttribute('aria-expanded', String(!desplegado));
                            });

                            function prepararLienzo(titulo, alto = 560) {
                                const lienzo = document.createElement('canvas');
                                lienzo.width = 1400;
                                lienzo.height = alto;
                                const contexto = lienzo.getContext('2d');
                                contexto.fillStyle = '#ffffff';
                                contexto.fillRect(0, 0, lienzo.width, lienzo.height);
                                contexto.fillStyle = '#173b5e';
                                contexto.font = 'bold 30px Arial';
                                contexto.fillText(titulo, 50, 58);
                                return { lienzo, contexto };
                            }

                            function descargarLienzo(lienzo, nombre) {
                                lienzo.toBlob((archivo) => {
                                    if (!archivo) {
                                        return;
                                    }

                                    const enlace = document.createElement('a');
                                    const url = URL.createObjectURL(archivo);
                                    enlace.href = url;
                                    enlace.download = nombre;
                                    enlace.click();
                                    URL.revokeObjectURL(url);
                                }, 'image/png');
                            }

                            function dibujarBarras() {
                                const espacioGrupo = 150;
                                const margenIzquierdo = 90;
                                const margenDerecho = 90;
                                const anchoLienzo = Math.max(1100, margenIzquierdo + margenDerecho + filasGrafica.length * espacioGrupo);
                                const alto = 820;
                                const lienzo = document.createElement('canvas');
                                lienzo.width = anchoLienzo;
                                lienzo.height = alto;
                                const contexto = lienzo.getContext('2d');
                                contexto.fillStyle = '#ffffff';
                                contexto.fillRect(0, 0, anchoLienzo, alto);
                                contexto.fillStyle = '#173b5e';
                                contexto.font = 'bold 30px Arial';
                                contexto.fillText('Tandeos cumplidos y atrasados por colonia', margenIzquierdo, 55);

                                const categorias = ['cumplidos', 'atrasados', 'no_cumplidos'];
                                const valores = filasGrafica.flatMap((fila) => categorias.map((categoria) => Number(fila[categoria]) || 0));
                                const maximo = Math.max(1, ...valores);
                                const divisiones = 5;
                                const maximoRedondeado = Math.ceil(maximo / divisiones) * divisiones || divisiones;
                                const grafica = {
                                    izquierda: margenIzquierdo,
                                    derecha: anchoLienzo - margenDerecho,
                                    arriba: 145,
                                    abajo: 620,
                                };
                                const altoGrafica = grafica.abajo - grafica.arriba;

                                contexto.font = '16px Arial';
                                contexto.textAlign = 'right';
                                for (let division = 0; division <= divisiones; division += 1) {
                                    const valor = (maximoRedondeado / divisiones) * division;
                                    const y = grafica.abajo - (valor / maximoRedondeado) * altoGrafica;
                                    contexto.strokeStyle = '#dce3ea';
                                    contexto.lineWidth = 1;
                                    contexto.beginPath();
                                    contexto.moveTo(grafica.izquierda, y);
                                    contexto.lineTo(grafica.derecha, y);
                                    contexto.stroke();
                                    contexto.fillStyle = '#526271';
                                    contexto.fillText(String(Math.round(valor)), grafica.izquierda - 14, y + 5);
                                }

                                const anchoGrupo = (grafica.derecha - grafica.izquierda) / Math.max(1, filasGrafica.length);
                                const anchoBarra = Math.min(34, Math.max(8, (anchoGrupo - 30) / categorias.length));
                                filasGrafica.forEach((fila, indice) => {
                                    const centroGrupo = grafica.izquierda + anchoGrupo * (indice + 0.5);
                                    categorias.forEach((categoria, categoriaIndice) => {
                                        const cantidad = Number(fila[categoria]) || 0;
                                        const altoBarra = (cantidad / maximoRedondeado) * altoGrafica;
                                        const x = centroGrupo + (categoriaIndice - 1) * (anchoBarra + 5) - anchoBarra / 2;
                                        const y = grafica.abajo - altoBarra;
                                        contexto.fillStyle = coloresGrafica[categoria];
                                        contexto.fillRect(x, y, anchoBarra, altoBarra);
                                    });

                                    const colonia = String(fila.zona || 'Sin colonia');
                                    contexto.save();
                                    contexto.translate(centroGrupo, grafica.abajo + 22);
                                    contexto.rotate(-Math.PI / 4);
                                    contexto.textAlign = 'right';
                                    contexto.fillStyle = '#243447';
                                    contexto.font = '15px Arial';
                                    contexto.fillText(colonia, 0, 0);
                                    contexto.restore();
                                });

                                contexto.save();
                                contexto.translate(28, grafica.arriba + altoGrafica / 2);
                                contexto.rotate(-Math.PI / 2);
                                contexto.textAlign = 'center';
                                contexto.fillStyle = '#243447';
                                contexto.font = 'bold 17px Arial';
                                contexto.fillText('Cantidad de tandeos', 0, 0);
                                contexto.restore();
                                contexto.textAlign = 'center';
                                contexto.fillStyle = '#243447';
                                contexto.font = 'bold 17px Arial';
                                contexto.fillText('Colonia', (grafica.izquierda + grafica.derecha) / 2, alto - 28);

                                let leyendaX = Math.max(margenIzquierdo, (anchoLienzo - 600) / 2);
                                categorias.forEach((categoria) => {
                                    contexto.fillStyle = coloresGrafica[categoria];
                                    contexto.fillRect(leyendaX, 95, 20, 20);
                                    contexto.textAlign = 'left';
                                    contexto.fillStyle = '#243447';
                                    contexto.font = '17px Arial';
                                    contexto.fillText(etiquetasGrafica[categoria], leyendaX + 28, 111);
                                    leyendaX += 200;
                                });

                                descargarLienzo(lienzo, 'grafica-barras-cumplimiento-por-colonia.png');
                            }
                            function dibujarPastel() {
                                const { lienzo, contexto } = prepararLienzo('Resultados generales de cumplimiento', 680);
                                const totales = ['cumplidos', 'atrasados', 'no_cumplidos'].reduce((resultado, categoria) => {
                                    resultado[categoria] = filasGrafica.reduce((suma, fila) => suma + (Number(fila[categoria]) || 0), 0);
                                    return resultado;
                                }, {});
                                const totalGeneral = Object.values(totales).reduce((suma, cantidad) => suma + cantidad, 0);
                                const centroX = 390;
                                const centroY = 370;
                                const radio = 235;
                                let anguloInicial = -Math.PI / 2;

                                if (!totalGeneral) {
                                    contexto.fillStyle = '#687785';
                                    contexto.font = '24px Arial';
                                    contexto.fillText('No hay resultados para graficar.', 50, 125);
                                } else {
                                    ['cumplidos', 'atrasados', 'no_cumplidos'].forEach((categoria) => {
                                        const cantidad = totales[categoria];
                                        if (!cantidad) {
                                            return;
                                        }

                                        const anguloFinal = anguloInicial + (cantidad / totalGeneral) * Math.PI * 2;
                                        contexto.beginPath();
                                        contexto.moveTo(centroX, centroY);
                                        contexto.arc(centroX, centroY, radio, anguloInicial, anguloFinal);
                                        contexto.closePath();
                                        contexto.fillStyle = coloresGrafica[categoria];
                                        contexto.fill();
                                        anguloInicial = anguloFinal;
                                    });
                                }

                                ['cumplidos', 'atrasados', 'no_cumplidos'].forEach((categoria, indice) => {
                                    const y = 245 + indice * 82;
                                    const cantidad = totales[categoria];
                                    const porcentaje = totalGeneral ? ((cantidad / totalGeneral) * 100).toFixed(1) : '0.0';
                                    contexto.fillStyle = coloresGrafica[categoria];
                                    contexto.fillRect(760, y - 21, 28, 28);
                                    contexto.fillStyle = '#243447';
                                    contexto.font = 'bold 24px Arial';
                                    contexto.fillText(etiquetasGrafica[categoria], 810, y);
                                    contexto.font = '21px Arial';
                                    contexto.fillText(`${cantidad} (${porcentaje}%)`, 810, y + 32);
                                });

                                contexto.fillStyle = '#526271';
                                contexto.font = '18px Arial';
                                contexto.fillText(`Total de resultados: ${totalGeneral}`, 760, 510);
                                descargarLienzo(lienzo, 'grafica-pastel-cumplimiento-por-colonia.png');
                            }

                            opcionesDescarga.querySelectorAll('[data-descargar-grafica]').forEach((boton) => {
                                boton.addEventListener('click', () => {
                                    if (boton.dataset.descargarGrafica === 'pastel') {
                                        dibujarPastel();
                                    } else {
                                        dibujarBarras();
                                    }
                                });
                            });
                        })();
                    </script>
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Colonia</th><th>Cumplidos</th><th>Con atraso</th><th>No cumplidos</th><th>Total registrado</th><th>Efectividad</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['zona'] }}</td><td class="text-center">{{ $fila['cumplidos'] }}</td><td class="text-center">{{ $fila['atrasados'] }}</td><td class="text-center">{{ $fila['no_cumplidos'] }}</td><td class="text-center">{{ $fila['total'] }}</td><td class="text-center">{{ number_format($fila['efectividad'], 1) }}%</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay cumplimientos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'reprogramaciones-motivo')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Motivo</th><th>Reprogramaciones</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['motivo'] }}</td><td class="text-center">{{ $fila['cantidad'] }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center">No hay reprogramaciones para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'atrasos-colonia')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Colonia</th><th>Tandeo / lugar</th><th>Fecha</th><th>Días de atraso</th><th>Motivos registrados</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['colonia'] }}</td><td>{{ $fila['tandeo'] }}</td><td>{{ $fila['fecha'] }}</td><td class="text-center">{{ $fila['dias'] }}</td><td>{{ $fila['motivo'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No hay atrasos de al menos un día para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @elseif ($tipoReporte === 'resumen-semanal-tandeador')
                    <h4 class="h5">Tandeador: {{ $tandeadorReporte->nombre }} {{ $tandeadorReporte->apellido_paterno }} {{ $tandeadorReporte->apellido_materno }}</h4>
                    <p class="text-muted">El PDF contiene únicamente los tandeos de esta persona dentro del rango seleccionado.</p>
                    @foreach ($semanasReporte as $diasBloque)
                        <h5 class="h6 mt-3">Del {{ \Carbon\Carbon::parse($diasBloque[0]['fecha'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($diasBloque[count($diasBloque) - 1]['fecha'])->format('d/m/Y') }}</h5>
                        <div class="table-responsive"><table class="table table-bordered table-striped align-middle text-center">
                            <thead><tr><th>Colonia</th><th>Descripción</th>@foreach ($diasBloque as $dia)<th>{{ $dia['titulo'] }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse ($filasPreview as $fila)
                                    <tr>
                                        <td>{{ $fila['colonia'] }}</td><td>{{ $fila['calle'] }}</td>
                                        @foreach ($diasBloque as $dia)
                                            <td>@foreach (($fila['horarios'][$dia['fecha']] ?? []) as $horario)<div>{{ $horario }}</div>@endforeach</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ 2 + count($diasBloque) }}" class="text-center">Este tandeador no tiene tandeos en el rango seleccionado.</td></tr>
                                @endforelse
                            </tbody>
                        </table></div>
                    @endforeach
                @elseif ($tipoReporte === 'cumplimiento-circuito')
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Circuito</th><th>Colonia</th><th>Lugar/tandeo</th><th>Programados</th><th>Ejecutados</th><th>Con desfase</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['circuito'] }}</td><td>{{ $fila['zona'] }}</td><td>{{ $fila['calle'] }}</td><td class="text-center">{{ $fila['programados'] }}</td><td class="text-center">{{ $fila['ejecutados'] }}</td><td class="text-center">{{ $fila['desfasados'] }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No hay tandeos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @else
                    <div class="table-responsive"><table class="table table-bordered table-striped align-middle">
                        <thead><tr><th>Circuito</th><th>Colonia</th><th>Lugar/tandeo</th><th>Fecha</th><th>Horario</th></tr></thead>
                        <tbody>
                            @forelse ($filasPreview as $fila)
                                <tr><td>{{ $fila['circuito'] }}</td><td>{{ $fila['zona'] }}</td><td>{{ $fila['calle'] }}</td><td>{{ $fila['fecha'] }}</td><td>{{ $fila['horario'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No hay tandeos pendientes para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                @endif

                <div id="aviso-descarga-reporte" class="alert alert-success mt-3 d-none" role="status"></div>
                <div id="error-descarga-reporte" class="alert alert-danger mt-3 d-none" role="alert"></div>
            </div>
        </section>
    @endif
</div>
@endsection
