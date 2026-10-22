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

    if (!espacioCalendario) {
        return;
    }

    const filtroColonia = document.getElementById('filtro-colonia');
    const puedeMover = espacioCalendario.dataset.puedeMover === '1';

    function formatoLocal(fecha) {
        const dosDigitos = numero => String(numero).padStart(2, '0');
        return fecha.getFullYear() + '-'
            + dosDigitos(fecha.getMonth() + 1) + '-'
            + dosDigitos(fecha.getDate()) + 'T'
            + dosDigitos(fecha.getHours()) + ':'
            + dosDigitos(fecha.getMinutes());
    }

    function enviarARevision(evento, inicioNuevo) {
        const formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = espacioCalendario.dataset.previaUrl;

        const campos = {
            _token: espacioCalendario.dataset.csrf,
            tandeo_id: evento.id,
            circuito_id: evento.extendedProps.circuitoId,
            inicio_nuevo: inicioNuevo,
        };

        Object.keys(campos).forEach(function (nombre) {
            const campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = campos[nombre];
            formulario.appendChild(campo);
        });

        document.body.appendChild(formulario);
        formulario.submit();
    }

    const calendario = new Calendar(espacioCalendario, {
        plugins: [
            themePlugin,
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin,
        ],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
        },
        editable: puedeMover,
        eventDurationEditable: false,
        events: {
            url: espacioCalendario.dataset.eventosUrl,
            extraParams: function () {
                return {
                    zona_id: filtroColonia ? filtroColonia.value : '',
                };
            },
            failure: function () {
                alert('No se pudieron cargar los tandeos. Actualiza la página e inténtalo de nuevo.');
            },
        },
        eventDrop: function (info) {
            const inicioNuevo = formatoLocal(info.event.start);

            fetch(espacioCalendario.dataset.moverUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': espacioCalendario.dataset.csrf,
                },
                body: JSON.stringify({
                    tandeo_id: info.event.id,
                    inicio_nuevo: inicioNuevo,
                }),
            })
                .then(async function (respuesta) {
                    const datos = await respuesta.json();

                    if (!respuesta.ok) {
                        const mensaje = datos.message || 'No se pudo mover el tandeo.';
                        throw new Error(mensaje);
                    }

                    if (datos.requiere_previa) {
                        info.revert();
                        enviarARevision(info.event, inicioNuevo);
                        return;
                    }

                    calendario.refetchEvents();
                    alert(datos.mensaje);
                })
                .catch(function (error) {
                    info.revert();
                    alert(error.message || 'Ocurrió un error al mover el tandeo.');
                });
        },
        eventClick: function (info) {
            const datos = info.event.extendedProps;
            alert(
                'Circuito: ' + datos.circuito + '\n'
                + 'Colonia: ' + datos.colonia + '\n'
                + 'Calles: ' + datos.calles + '\n'
                + 'Tandeador: ' + datos.tandeadores
            );
        },
    });

    calendario.render();

    if (filtroColonia) {
        filtroColonia.addEventListener('change', function () {
            calendario.refetchEvents();
        });
    }
});