import './bootstrap';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

import { Calendar } from 'fullcalendar';
import dayGridPlugin from 'fullcalendar/daygrid';
import timeGridPlugin from 'fullcalendar/timegrid';
import interactionPlugin from 'fullcalendar/interaction';
import esLocale from 'fullcalendar/locales/es';
import themePlugin from 'fullcalendar/themes/monarch';

import 'fullcalendar/skeleton.css';
import 'fullcalendar/themes/monarch/theme.css';
import 'fullcalendar/themes/monarch/palettes/blue.css';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="date"]').forEach(function (campo) {
        flatpickr(campo, {
            locale: Spanish,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: (campo.className ? campo.className + ' ' : '') + 'fecha-calendario-espanol',
            allowInput: true,
        });
    });

    document.querySelectorAll('input[type="time"]').forEach(function (campo) {
        flatpickr(campo, {
            locale: Spanish,
            enableTime: true,
            noCalendar: true,
            time_24hr: true,
            dateFormat: 'H:i',
            altInput: true,
            altFormat: 'H:i',
            altInputClass: (campo.className ? campo.className + ' ' : '') + 'hora-facil',
            allowInput: true,
            minuteIncrement: 1,
            disableMobile: true,
        });
    });
});



document.addEventListener('DOMContentLoaded', function () {
    const espacioCalendario = document.getElementById('calendario');

    if (!espacioCalendario) return;

    const filtroColonia = document.getElementById('filtro-colonia');
    const puedeMover = espacioCalendario.dataset.puedeMover === '1';
    const dialogoDetalle = document.getElementById('detalle-tandeo');
    const dialogoMovimiento = document.getElementById('confirmar-movimiento');
    const botonConfirmar = document.getElementById('confirmar-movimiento-boton');
    const avisoEmpalme = document.getElementById('error-empalme');
    let cambioPendiente = null;

    function formatoLocal(fecha) {
        const dosDigitos = numero => String(numero).padStart(2, '0');
        return fecha.getFullYear() + '-'
            + dosDigitos(fecha.getMonth() + 1) + '-'
            + dosDigitos(fecha.getDate()) + 'T'
            + dosDigitos(fecha.getHours()) + ':'
            + dosDigitos(fecha.getMinutes());
    }

    function hora(fecha) {
        return new Intl.DateTimeFormat('es-MX', {
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).format(fecha);
    }

    function fechaHora(fecha) {
        return new Intl.DateTimeFormat('es-MX', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).format(fecha);
    }

    function rango(inicio, fin) {
        return fechaHora(inicio) + ' – ' + fechaHora(fin)
            + (inicio.toDateString() !== fin.toDateString() ? ' (termina al día siguiente)' : '');
    }

    function abrirConfirmacion(info) {
        const nuevoInicio = info.event.start;
        const nuevoFin = info.event.end;
        const eventoOriginal = info.oldEvent;
        const datos = info.event.extendedProps;

        cambioPendiente = {
            id: info.event.id,
            inicio: formatoLocal(nuevoInicio),
        };

        info.revert();

        document.getElementById('cambio-circuito').textContent = datos.circuito;
        document.getElementById('cambio-colonia').textContent = datos.colonia;
        document.getElementById('cambio-calles').textContent = datos.calles;
        document.getElementById('cambio-actual').textContent = rango(eventoOriginal.start, eventoOriginal.end);
        document.getElementById('cambio-propuesto').textContent = rango(nuevoInicio, nuevoFin);
        avisoEmpalme.hidden = true;
        avisoEmpalme.textContent = '';
        dialogoMovimiento.showModal();
    }

    function mostrarError(mensaje) {
        avisoEmpalme.textContent = mensaje;
        avisoEmpalme.hidden = false;
    }

    async function guardarMovimiento() {
        if (!cambioPendiente) return;

        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Guardando...';
        avisoEmpalme.hidden = true;

        try {
            const respuesta = await fetch(espacioCalendario.dataset.moverUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': espacioCalendario.dataset.csrf,
                },
                body: JSON.stringify({
                    tandeo_id: cambioPendiente.id,
                    inicio_nuevo: cambioPendiente.inicio,
                }),
            });
            const datos = await respuesta.json();

            if (!respuesta.ok) {
                const errorValidacion = datos.errors ? Object.values(datos.errors).flat()[0] : null;
                mostrarError(errorValidacion || datos.message || 'No se pudo guardar el cambio.');
                return;
            }

            dialogoMovimiento.close();
            cambioPendiente = null;
            calendario.refetchEvents();
            alert(datos.mensaje);
        } catch (error) {
            mostrarError('No se pudo conectar con el sistema. Revisa tu conexión e inténtalo de nuevo.');
        } finally {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Confirmar cambio';
        }
    }

    const calendario = new Calendar(espacioCalendario, {
        plugins: [themePlugin, dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },
        editable: puedeMover,
        eventDurationEditable: false,
        events: {
            url: espacioCalendario.dataset.eventosUrl,
            extraParams: function () {
                return { zona_id: filtroColonia ? filtroColonia.value : '' };
            },
            failure: function () {
                alert('No se pudieron cargar los tandeos. Actualiza la página e inténtalo de nuevo.');
            },
        },
        eventDrop: abrirConfirmacion,
        eventContent: function (info) {
            const datos = info.event.extendedProps;
            const tarjeta = document.createElement('div');
            tarjeta.className = 'cal-evento';
            tarjeta.title = datos.calles;

            const cabecera = document.createElement('span');
            cabecera.className = 'cal-evento-cabecera';
            cabecera.textContent = datos.circuito + ' · ' + datos.colonia;

            const calle = document.createElement('span');
            calle.className = 'cal-evento-calle';
            calle.textContent = datos.calles;

            const horario = document.createElement('span');
            horario.className = 'cal-evento-horario';
            horario.textContent = hora(info.event.start) + ' – ' + hora(info.event.end)
                + (info.event.start.toDateString() !== info.event.end.toDateString() ? ' (+1 día)' : '');

            tarjeta.append(cabecera, calle, horario);
            return { domNodes: [tarjeta] };
        },
        eventClick: function (info) {
            const datos = info.event.extendedProps;
            document.getElementById('detalle-circuito').textContent = datos.circuito;
            document.getElementById('detalle-colonia').textContent = datos.colonia;
            document.getElementById('detalle-calles').textContent = datos.calles;
            document.getElementById('detalle-fecha').textContent = fechaHora(info.event.start);
            document.getElementById('detalle-horario').textContent = hora(info.event.start) + ' – ' + hora(info.event.end)
                + (info.event.start.toDateString() !== info.event.end.toDateString() ? ' (+1 día)' : '');
            document.getElementById('detalle-estado').textContent = datos.estadoTexto;
            document.getElementById('detalle-tandeadores').textContent = datos.tandeadores;
            dialogoDetalle.showModal();
        },
    });

    calendario.render();

    document.getElementById('cerrar-detalle-tandeo').addEventListener('click', function () {
        dialogoDetalle.close();
    });

    document.getElementById('cancelar-movimiento').addEventListener('click', function () {
        dialogoMovimiento.close();
        cambioPendiente = null;
    });

    botonConfirmar.addEventListener('click', guardarMovimiento);

    dialogoMovimiento.addEventListener('close', function () {
        cambioPendiente = null;
    });

    if (filtroColonia) {
        filtroColonia.addEventListener('change', function () {
            calendario.refetchEvents();
        });
    }

    const limpiarFiltroCalendario = document.getElementById('limpiar-filtro-calendario');
    if (limpiarFiltroCalendario && filtroColonia) {
        limpiarFiltroCalendario.addEventListener('click', function () {
            filtroColonia.value = '';
            filtroColonia.dispatchEvent(new Event('change'));
        });
    }
});