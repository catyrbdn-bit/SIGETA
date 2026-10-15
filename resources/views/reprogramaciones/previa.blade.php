@extends('layouts.app')

@section('titulo', 'Vista previa - SIGETA')

@section('estilos')
<style>
.contenido-vista { font-family: Arial, sans-serif; color: #222; margin: 24px; }
        h2 { margin: 0; }
        .sub { color: #666; margin: 4px 0 16px; }

        #buscar { width: 320px; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 14px; }

        table { border-collapse: collapse; width: 100%; }
        th { text-align: left; font-size: 12px; letter-spacing: .5px; color: #777; padding: 10px 8px; border-bottom: 2px solid #ddd; text-transform: uppercase; }
        td { padding: 12px 8px; border-bottom: 1px solid #eee; vertical-align: middle; }

        .circuito { font-weight: bold; }
        .zona { font-size: 12px; color: #777; margin-top: 2px; }
        .hora { white-space: nowrap; }
        .dia { font-size: 11px; color: #888; margin-bottom: 2px; }
        .fin { font-size: 12px; color: #777; margin-top: 4px; }

        input[type=datetime-local], input[type=number], input[type=text] { padding: 6px; border: 1px solid #bbb; border-radius: 6px; }
        input.atraso { width: 70px; text-align: center; }
        input.motivo { width: 100%; min-width: 200px; box-sizing: border-box; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 6px; }
        .badge.manual { background: #fde68a; color: #92400e; }

        .aviso { color: #b36b00; }
        button.principal { background: #1f4e79; color: #fff; border: 0; padding: 9px 18px; border-radius: 6px; cursor: pointer; }
        button.secundario { background: #fff; border: 1px solid #aaa; padding: 9px 18px; border-radius: 6px; cursor: pointer; }
        .acciones-finales { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .acciones-derecha { display: flex; gap: 10px; }
    
</style>
@endsection

@section('content')
<div class="contenido-vista">
@php
        $hora = fn ($i, $f) => $i->format('H:i') . ' – ' . $f->format('H:i')
            . ($f->isSameDay($i) ? '' : ' (+1 día)');

        $dia = fn ($c) => ucfirst($c->copy()->locale('es')->translatedFormat('D d/m'));

        $fechaTitulo = ucfirst($desde->copy()->locale('es')->translatedFormat('l, j \d\e F \d\e Y'));

        $filas = collect($resultado['movimientos'])
            ->sortBy(fn ($m) => $m['inicio_anterior']->timestamp);
    @endphp

    <h2>{{ $modo === 'manual' ? 'Vista previa de los cambios al tandeo' : 'Vista previa del apagón general' }}</h2>
    <p class="sub">{{ $fechaTitulo }} &middot; {{ $desde->format('H:i') }}</p>

    @if ($errors->any())
        <ul style="color:red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @foreach ($resultado['advertencias'] as $aviso)
        <p class="aviso">⚠ {{ $aviso }}</p>
    @endforeach

    @if ($filas->count())

        <input type="text" id="buscar" placeholder="Buscar circuito o colonia…">

        <form action="{{ $modo === 'manual' ? route('reprogramaciones.manual.store') : route('reprogramaciones.apagon.store') }}" method="POST">
            @csrf
            <input type="hidden" name="desde" value="{{ $desde->format('Y-m-d\TH:i') }}">
            @if ($modo === 'manual')
                <input type="hidden" name="tandeo_id" value="{{ $tandeoId }}">
                <input type="hidden" name="circuito_id" value="{{ $circuitoId }}">
                <input type="hidden" name="tandeador_id" value="{{ $tandeadorId }}">
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Circuito / Colonia</th>
                        <th>Horario actual</th>
                        <th>Horario nuevo</th>
                        <th style="text-align:center;">Atraso (h)</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $m)
                        @php
                            $id = $m['tandeo']->id;
                            $propuesto = $m['inicio_nuevo']->format('Y-m-d\TH:i');
                            $motivoBase = $m['es_cadena'] ? 'Recorrido por empalme' : ($modo === 'manual' ? 'Reprogramación manual' : 'Afectado por el apagón');
                        @endphp

                        <tr class="dato"
                            data-base="{{ $m['inicio_anterior']->format('Y-m-d\TH:i') }}"
                            data-dur="{{ $m['inicio_anterior']->diffInMinutes($m['fin_anterior']) }}">

                            <td>
                                <div class="circuito">
                                    {{ $m['tandeo']->circuito->nombre }}
                                    @if ($m['manual'])
                                        <span class="badge manual">Ajustado</span>
                                    @endif
                                </div>
                                <div class="zona">
                                    {{ $m['tandeo']->circuito->zona->nombre ?? 'Sin colonia' }}
                                    &middot;
                                    {{ $m['tandeo']->circuito->puntoAbastecimiento->nombre ?? 'Sin punto fijo' }}
                                </div>
                            </td>

                            <td class="hora">
                                <div class="dia">{{ $dia($m['inicio_anterior']) }}</div>
                                {{ $hora($m['inicio_anterior'], $m['fin_anterior']) }}
                            </td>

                            <td class="hora">
                                <input type="datetime-local" class="nuevo" name="nuevo[{{ $id }}]" value="{{ $propuesto }}">
                                <div class="fin">
                                    Termina: {{ $m['fin_nuevo']->format('H:i') }}{{ $m['fin_nuevo']->isSameDay($m['inicio_nuevo']) ? '' : ' (+1 día)' }}
                                </div>
                                <input type="hidden" name="propuesto[{{ $id }}]" value="{{ $propuesto }}">
                                <input type="hidden" name="manual_prev[{{ $id }}]" value="{{ $m['manual'] ? 1 : 0 }}">
                            </td>

                            <td class="num" style="text-align:center;">
                                <input type="number" class="atraso" step="any"
                                       value="{{ round($m['inicio_anterior']->diffInMinutes($m['inicio_nuevo']) / 60, 2) }}">
                            </td>

                            <td>
                                <input type="text" class="motivo" name="motivo[{{ $id }}]"
                                       value="{{ $m['motivo'] ?? $motivoBase }}" maxlength="255">
                                <input type="hidden" name="motivo_base[{{ $id }}]" value="{{ $motivoBase }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="acciones-finales">
                <button type="button" class="secundario" onclick="window.location.href='{{ route('cumplimiento.index') }}'">Cancelar</button>
                <div class="acciones-derecha">
                    <button type="submit" class="secundario" formaction="{{ $modo === 'manual' ? route('reprogramaciones.manual.previa') : route('reprogramaciones.apagon.previa') }}">Recalcular</button>
                    <button type="submit" class="principal">Confirmar y guardar</button>
                </div>
            </div>
        </form>

        <script>
            const pad = n => String(n).padStart(2, '0');
            const aLocal = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            const hhmm = d => pad(d.getHours()) + ':' + pad(d.getMinutes());

            document.querySelectorAll('tr.dato').forEach(function (fila) {
                const base = new Date(fila.dataset.base);
                const dur = parseInt(fila.dataset.dur, 10);
                const nuevo = fila.querySelector('.nuevo');
                const atraso = fila.querySelector('.atraso');
                const fin = fila.querySelector('.fin');

                function pintarFin() {
                    if (!nuevo.value) return;
                    const ini = new Date(nuevo.value);
                    const f = new Date(ini.getTime() + dur * 60000);
                    fin.textContent = 'Termina: ' + hhmm(f)
                        + (f.toDateString() === ini.toDateString() ? '' : ' (+1 día)');
                }

                atraso.addEventListener('input', function () {
                    if (atraso.value === '') return;
                    nuevo.value = aLocal(new Date(base.getTime() + parseFloat(atraso.value) * 3600000));
                    pintarFin();
                });

                nuevo.addEventListener('input', function () {
                    if (!nuevo.value) return;
                    atraso.value = Math.round((new Date(nuevo.value) - base) / 3600000 * 100) / 100;
                    pintarFin();
                });
            });

            document.getElementById('buscar').addEventListener('input', function () {
                const texto = this.value.toLowerCase();
                document.querySelectorAll('tbody tr.dato').forEach(function (fila) {
                    fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
                });
            });
        </script>
    @else
        <p>{{ $modo === 'manual' ? 'No hay cambios para mostrar.' : 'No hay tandeos afectados por el apagón.' }}</p>

        <button type="button" class="secundario" onclick="if (history.length > 1 && document.referrer && new URL(document.referrer).origin === location.origin) { history.back(); } else { window.location.href='{{ route('cumplimiento.index') }}'; }">Volver</button>
    @endif
</div>
@endsection
