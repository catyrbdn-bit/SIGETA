// archivo con interacciones de las pantallas del sistema
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

// Inicia las funciones de la pantalla cuando termina de cargar.
// descripcion: inicia las funciones de la pantalla cuando termina de cargar.
// entradas: aviso del navegador de que la página terminó de cargar
// salida: deja listos los controles de la pantalla
document.addEventListener('DOMContentLoaded', function () {
    // Configura cada campo de fecha u hora de la pantalla.
    // descripcion: configura cada campo de fecha u hora de la pantalla.
    // entradas: campo de fecha u hora que se configura
    // salida: deja el campo listo para la captura
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

    // Configura cada campo de fecha u hora de la pantalla.
    // descripcion: configura cada campo de fecha u hora de la pantalla.
    // entradas: campo de fecha u hora que se configura
    // salida: deja el campo listo para la captura
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



// Inicia las funciones de la pantalla cuando termina de cargar.
// descripcion: inicia las funciones de la pantalla cuando termina de cargar.
// entradas: aviso del navegador de que la página terminó de cargar
// salida: deja listos los controles de la pantalla
document.addEventListener('DOMContentLoaded', function () {
    const espacioCalendario = document.getElementById('calendario');

    if (!espacioCalendario) return;

    const filtroColonia = document.getElementById('filtro-colonia');
    const puedeMover = espacioCalendario.dataset.puedeMover === '1';
    const dialogoDetalle = document.getElementById('detalle-tandeo');
    const dialogoMovimiento = document.getElementById('confirmar-movimiento');
    const botonConfirmar = document.getElementById('confirmar-movimiento-boton');
    const avisoEmpalme = document.getElementById('error-empalme');
    const campoHoraCambio = document.getElementById('cambio-hora');
    let cambioPendiente = null;

    // prepara los datos para formatolocal
    // Convierte la fecha al formato que necesita el calendario.
    // descripcion: convierte la fecha al formato que necesita el calendario.
    // entradas: no requiere datos externos
    // salida: devuelve la fecha como año, mes y día
    function formatoLocal(fecha) {
        // Ajusta el número a dos dígitos para mostrarlo en la fecha u hora.
        // descripcion: ajusta el número para mostrarlo con dos dígitos.
        // entradas: número que se debe completar
        // salida: devuelve el número con dos dígitos
        const dosDigitos = numero => String(numero).padStart(2, '0');
        return fecha.getFullYear() + '-'
            + dosDigitos(fecha.getMonth() + 1) + '-'
            + dosDigitos(fecha.getDate()) + 'T'
            + dosDigitos(fecha.getHours()) + ':'
            + dosDigitos(fecha.getMinutes());
    }

    // prepara los datos para hora
    // Obtiene la hora en formato de 24 horas.
    // descripcion: obtiene la hora en formato de 24 horas.
    // entradas: no requiere datos externos
    // salida: devuelve la hora y los minutos como texto
    function hora(fecha) {
        return new Intl.DateTimeFormat('es-MX', {
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).format(fecha);
    }

    // prepara los datos para fechahora
    // Acomoda la fecha y la hora para mostrarlas juntas.
    // descripcion: acomoda la fecha y la hora para mostrarlas juntas.
    // entradas: no requiere datos externos
    // salida: devuelve la fecha y hora como texto
    function fechaHora(fecha) {
        return new Intl.DateTimeFormat('es-MX', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).format(fecha);
    }

    // prepara los datos para fechacorta
    // Acomoda la fecha en un formato breve para el calendario.
    // descripcion: acomoda la fecha en un formato breve para el calendario.
    // entradas: no requiere datos externos
    // salida: devuelve la fecha corta como texto
    function fechaCorta(fecha) {
        const dias = ['dom.', 'lun.', 'mar.', 'mié.', 'jue.', 'vie.', 'sáb.'];
        const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return dias[fecha.getDay()] + ' ' + fecha.getDate() + '/' + meses[fecha.getMonth()] + '/' + fecha.getFullYear();
    }

    // prepara los datos para rango
    // Acomoda la hora inicial y final en un solo texto.
    // descripcion: acomoda la hora inicial y final en un solo texto.
    // entradas: no requiere datos externos
    // salida: devuelve el rango de horario como texto
    function rango(inicio, fin) {
        return fechaCorta(inicio) + ' · ' + hora(inicio) + '–' + hora(fin)
            + (inicio.toDateString() !== fin.toDateString() ? ' (+1 día)' : '');
    }

    // prepara los datos para actualizarhorapropuesta
    // Actualiza la hora final al cambiar el horario propuesto.
    // descripcion: actualiza la hora final al cambiar el horario propuesto.
    // entradas: no requiere datos externos
    // salida: muestra el horario final calculado
    function actualizarHoraPropuesta() {
        if (!cambioPendiente || !campoHoraCambio.value) return;

        const [horas, minutos] = campoHoraCambio.value.split(':').map(Number);
        const [anio, mes, dia] = cambioPendiente.inicio.slice(0, 10).split('-').map(Number);
        const inicio = new Date(anio, mes - 1, dia, horas, minutos);
        const fin = new Date(inicio.getTime() + cambioPendiente.duracionMinutos * 60000);

        cambioPendiente.inicio = formatoLocal(inicio);
        document.getElementById('cambio-propuesto').textContent = rango(inicio, fin);
        avisoEmpalme.hidden = true;
        avisoEmpalme.textContent = '';
    }

    // prepara los datos para abrirconfirmacion
    // Muestra los datos del tandeo y el horario que se propone guardar.
    // descripcion: muestra los datos del tandeo y el horario que se propone guardar.
    // entradas: no requiere datos externos
    // salida: abre la ventana de confirmación del cambio
    function abrirConfirmacion(info) {
        const nuevoInicio = info.event.start;
        const nuevoFin = info.event.end;
        const eventoOriginal = info.oldEvent;
        const datos = info.event.extendedProps;

        cambioPendiente = {
            id: info.event.id,
            inicio: formatoLocal(nuevoInicio),
            duracionMinutos: Math.round((nuevoFin - nuevoInicio) / 60000),
        };
        if (campoHoraCambio._flatpickr) {
            campoHoraCambio._flatpickr.setDate(hora(nuevoInicio), false, 'H:i');
        } else {
            campoHoraCambio.value = hora(nuevoInicio);
        }

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

    // prepara los datos para mostrarerror
    // Muestra el aviso con el error que devolvió el sistema.
    // descripcion: muestra el aviso con el error que devolvió el sistema.
    // entradas: no requiere datos externos
    // salida: presenta el mensaje de error al usuario
    function mostrarError(mensaje) {
        avisoEmpalme.textContent = mensaje;
        avisoEmpalme.hidden = false;
    }

    // prepara los datos para guardarmovimiento
    // Envía al sistema la fecha y hora nuevas del tandeo.
    // descripcion: envía al sistema la fecha y hora nuevas del tandeo.
    // entradas: no requiere datos externos
    // salida: guarda el cambio o muestra el aviso de empalme
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
        nextDayThreshold: '23:59:59',
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
            // Prepara los filtros para consultar el calendario.
            // descripcion: prepara los filtros para consultar el calendario.
            // entradas: filtros elegidos en la pantalla
            // salida: devuelve los filtros actuales para buscar tandeos
            extraParams: function () {
                return { zona_id: filtroColonia ? filtroColonia.value : '' };
            },
            // Muestra un aviso si no se pueden cargar los tandeos.
            // descripcion: muestra un aviso si no se pueden cargar los tandeos.
            // entradas: error ocurrido al consultar el calendario
            // salida: informa al usuario que ocurrió un problema
            failure: function () {
                alert('No se pudieron cargar los tandeos. Actualiza la página e inténtalo de nuevo.');
            },
        },
        eventDrop: abrirConfirmacion,
        // Acomoda el nombre, las calles y el horario del tandeo.
        // descripcion: acomoda el nombre, las calles y el horario del tandeo.
        // entradas: datos del tandeo que aparece en el calendario
        // salida: devuelve el contenido que se muestra en el evento
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
        // Abre el detalle del tandeo que seleccionó el usuario.
        // descripcion: abre el detalle del tandeo que seleccionó el usuario.
        // entradas: tandeo seleccionado en el calendario
        // salida: muestra los datos del tandeo seleccionado
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

    // Atiende la acción de click en el control correspondiente.
    // descripcion: atiende la acción de click en el control correspondiente.
    // entradas: acción del usuario en el control
    // salida: actualiza la pantalla o ejecuta la acción relacionada
    document.getElementById('cerrar-detalle-tandeo').addEventListener('click', function () {
        dialogoDetalle.close();
    });

    // Atiende la acción de click en el control correspondiente.
    // descripcion: atiende la acción de click en el control correspondiente.
    // entradas: acción del usuario en el control
    // salida: actualiza la pantalla o ejecuta la acción relacionada
    document.getElementById('cancelar-movimiento').addEventListener('click', function () {
        dialogoMovimiento.close();
        cambioPendiente = null;
    });

    botonConfirmar.addEventListener('click', guardarMovimiento);
    campoHoraCambio.addEventListener('input', actualizarHoraPropuesta);
    campoHoraCambio.addEventListener('change', actualizarHoraPropuesta);

    // Atiende la acción de close en el control correspondiente.
    // descripcion: atiende la acción de close en el control correspondiente.
    // entradas: acción del usuario en el control
    // salida: actualiza la pantalla o ejecuta la acción relacionada
    dialogoMovimiento.addEventListener('close', function () {
        cambioPendiente = null;
    });

    if (filtroColonia) {
        // Atiende la acción de change en el control correspondiente.
        // descripcion: atiende la acción de change en el control correspondiente.
        // entradas: acción del usuario en el control
        // salida: actualiza la pantalla o ejecuta la acción relacionada
        filtroColonia.addEventListener('change', function () {
            calendario.refetchEvents();
        });
    }

    const limpiarFiltroCalendario = document.getElementById('limpiar-filtro-calendario');
    if (limpiarFiltroCalendario && filtroColonia) {
        // Atiende la acción de click en el control correspondiente.
        // descripcion: atiende la acción de click en el control correspondiente.
        // entradas: acción del usuario en el control
        // salida: actualiza la pantalla o ejecuta la acción relacionada
        limpiarFiltroCalendario.addEventListener('click', function () {
            filtroColonia.value = '';
            filtroColonia.dispatchEvent(new Event('change'));
        });
    }
});

// descarga los reportes y avisa cuando el archivo se genero
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.descargar-reporte').forEach(function (boton) {
        boton.addEventListener('click', async function (evento) {
            evento.preventDefault();

            const aviso = document.getElementById('aviso-descarga-reporte');
            const error = document.getElementById('error-descarga-reporte');
            const textoOriginal = boton.textContent;
            aviso.classList.add('d-none');
            error.classList.add('d-none');
            boton.disabled = true;
            boton.textContent = 'Generando archivo...';

            try {
                const respuesta = await fetch(boton.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const tipoArchivo = respuesta.headers.get('Content-Type') || '';
                if (!respuesta.ok || (!tipoArchivo.includes('pdf') && !tipoArchivo.includes('spreadsheet'))) {
                    throw new Error('No se pudo generar el archivo. Revisa los filtros e inténtalo de nuevo.');
                }

                const archivo = await respuesta.blob();
                const enlaceDescarga = document.createElement('a');
                const urlArchivo = URL.createObjectURL(archivo);
                const disposicion = respuesta.headers.get('Content-Disposition') || '';
                const nombreArchivo = disposicion.match(/filename="?([^";]+)"?/i)?.[1]
                    || boton.dataset.nombre
                    || 'reporte';

                enlaceDescarga.href = urlArchivo;
                enlaceDescarga.download = nombreArchivo;
                document.body.appendChild(enlaceDescarga);
                enlaceDescarga.click();
                enlaceDescarga.remove();
                setTimeout(function () { URL.revokeObjectURL(urlArchivo); }, 1000);

                aviso.textContent = 'El archivo ' + nombreArchivo + ' se generó y la descarga comenzó. Revisa la carpeta Descargas o la ubicación configurada en tu navegador.';
                aviso.classList.remove('d-none');
            } catch (fallo) {
                error.textContent = fallo.message || 'Ocurrió un error al generar el reporte.';
                error.classList.remove('d-none');
            } finally {
                boton.disabled = false;
                boton.textContent = textoOriginal;
            }
        });
    });
});
