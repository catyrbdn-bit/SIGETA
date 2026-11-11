{{-- vista de app en el modulo layouts --}}
{{-- archivo del sistema: archivo de codigo del sistema --}}
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'SIGETA')</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('estilos')

    {{-- estilos visuales de la pantalla --}}
    <style>
        html,
        body {
            width: 100%;
            min-width: 320px;
            overflow-x: hidden;
        }

        .container-fluid > .row {
            min-height: 100vh;
            align-items: stretch;
        }

        .menu-lateral {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 16.666667%;
            height: 100vh;
            height: 100dvh;
            max-height: none;
            min-height: 0;
            box-sizing: border-box;
            overflow: hidden;
            background: #5b9bd5;
            color: #ffffff;
            z-index: 1020;
        }

        .menu-lateral .texto-menu-secundario { color: #eaf4ff; }
        .menu-lateral .btn-outline-primary,
        .menu-lateral .btn-outline-secondary {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.7);
        }
        .menu-lateral .btn-outline-primary:hover,
        .menu-lateral .btn-outline-secondary:hover {
            color: #24527a;
            background: #ffffff;
            border-color: #ffffff;
        }
                .menu-lateral .boton-cerrar-sesion {
            display: block;
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ffffff;
            border-radius: 8px;
            color: #24527a;
            background: #ffffff;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
        }
        .menu-lateral .boton-cerrar-sesion:hover {
            color: #173b5c;
            background: #eaf4ff;
        }

        .menu-navegacion {
            display: grid;
            grid-template-columns: 1fr;
            grid-auto-rows: max-content;
            align-content: start;
            gap: 8px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.75) transparent;
        }

        .menu-navegacion > a {
            display: flex;
            align-items: center;
            min-height: 44px;
            padding: 8px 12px;
            line-height: 1.25;
        }
        .icono-menu { display: inline-flex; flex: 0 0 24px; align-items: center; justify-content: center; margin-right: 8px; line-height: 1; }
        .icono-menu svg { width: 18px; height: 18px; fill: currentColor; }
        .icono-boton { display: inline-flex; width: 16px; height: 16px; margin-right: 5px; vertical-align: -3px; }
        .icono-boton svg { width: 16px; height: 16px; fill: currentColor; }

        .menu-navegacion::-webkit-scrollbar {
            width: 6px;
        }

        .menu-navegacion::-webkit-scrollbar-thumb {
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.75);
        }

        .contenido-principal {
            flex: 0 0 83.333333%;
            width: 83.333333%;
            margin-left: 16.666667%;
            min-width: 0;
            max-width: 100%;
        }

        .contenido-principal > * { max-width: 100%; }
        .contenido-principal h1,
        .contenido-principal .titulo-vista {
            margin: 0 0 1.25rem;
            color: #243447;
            font-size: 2rem;
            font-weight: 600;
            line-height: 1.2;
        }
        .contenido-principal .contenido-vista {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
            background: transparent;
            font-family: inherit;
        }
        .contenido-principal label,
        .contenido-principal .form-label { font-weight: 600; }
        .contenido-principal .btn,
        .contenido-principal .boton { border-radius: 6px; font-weight: 600; }
        .contenido-principal .form-control,
        .contenido-principal .form-select { border-color: #b9c9d8; border-radius: 6px; }
        .contenido-principal .btn-primary,
        .contenido-principal .boton:not(.boton-secundario):not(.boton-peligro) {
            border-color: #1f4e79;
            color: #fff;
            background: #1f4e79;
        }
        .contenido-principal .btn-primary:hover,
        .contenido-principal .boton:not(.boton-secundario):not(.boton-peligro):hover {
            border-color: #173b5c;
            background: #173b5c;
        }
        .contenido-principal .btn-outline-primary { color: #1f4e79; border-color: #1f4e79; }
        .contenido-principal .btn-outline-primary:hover { color: #fff; background: #1f4e79; border-color: #1f4e79; }
        .contenido-principal .boton-secundario { color: #243447; background: #e1e7ed; }
        .contenido-principal .boton-peligro,
        .contenido-principal .btn-danger { color: #fff; background: #a83232; border-color: #a83232; }
        .encabezado-vista { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .encabezado-vista h1 { margin: 0; }
        .encabezado-panel { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .acciones-panel-encabezado { display: flex; flex-wrap: wrap; gap: 8px; }
        .acciones-panel-encabezado .btn { min-width: 160px; min-height: 40px; }
        .filtros-panel-principal { padding: 14px 16px; border: 1px solid #d7e0e8; border-radius: 8px; background: #fff; }
        .encabezado-tabla-panel { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
        .encabezado-tabla-panel h2 { flex: 1 1 320px; margin: 0 !important; }
        .botones-incidencia-panel { display: flex; flex: 0 1 auto; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 0; }
        .grupo-eventos-panel { display: flex; flex-wrap: nowrap; justify-content: flex-end; gap: 8px; }
        .botones-incidencia-panel form { flex: 0 0 auto; max-width: none; }
        .botones-incidencia-panel .btn { width: 100%; min-height: 42px; }
        .btn-evento-apagon,
        .btn-evento-laguna,
        .btn-evento-pozo { color: #fff; border: 1px solid transparent; }
        .btn-evento-apagon { background: #6f42c1; border-color: #6f42c1; }
        .btn-evento-apagon:hover { color: #fff; background: #59359a; border-color: #59359a; }
        .btn-evento-laguna { background: #00897b; border-color: #00897b; }
        .btn-evento-laguna:hover { color: #fff; background: #006b60; border-color: #006b60; }
        .btn-evento-pozo { background: #c2185b; border-color: #c2185b; }
        .btn-evento-pozo:hover { color: #fff; background: #9c1249; border-color: #9c1249; }
        .acciones-tandeo-panel { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .acciones-tandeo-panel .btn { display: flex; align-items: center; justify-content: center; min-width: 0; min-height: 40px; padding: 6px 8px; white-space: normal; text-align: center; }
        .marca-sigeta { display: flex; align-items: center; gap: 10px; color: #fff; font-size: 1.55rem; font-weight: 800; letter-spacing: .12em; }
        .marca-gota { display: inline-block; width: 17px; height: 17px; border-radius: 70% 30% 65% 35%; background: #fff; transform: rotate(45deg); }
        .contenido-principal table thead th { text-align: center; vertical-align: middle; }
        .contenido-principal .container { width: 100%; max-width: 100%; }
        .contenido-principal img,
        .contenido-principal video,
        .contenido-principal canvas {
            max-width: 100%;
            height: auto;
        }

        .estado-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .estado-programado { background: #dbeafe; color: #174ea6; }
        .estado-en_curso { background: #fff3cd; color: #735c00; }
        .estado-pendiente { background: #e2e3e5; color: #41464b; }
        .estado-cumplido { background: #d1e7dd; color: #0f5132; }
        .estado-atraso { background: #ffe2c6; color: #8a3b00; }
        .estado-no_cumplido { background: #f8d7da; color: #842029; }
        .estado-activo { background: #d1e7dd; color: #0f5132; }
        .estado-inactivo { background: #e2e3e5; color: #41464b; }

        select.estado-programado,
        select.estado-en_curso,
        select.estado-pendiente,
        select.estado-cumplido,
        select.estado-atraso,
        select.estado-no_cumplido {
            border-width: 2px;
            font-weight: 600;
        }

        select.estado-programado { background-color: #dbeafe; color: #174ea6; }
        select.estado-en_curso { background-color: #fff3cd; color: #735c00; }
        select.estado-pendiente { background-color: #e2e3e5; color: #41464b; }
        select.estado-cumplido { background-color: #d1e7dd; color: #0f5132; }
        select.estado-atraso { background-color: #ffe2c6; color: #8a3b00; }
        select.estado-no_cumplido { background-color: #f8d7da; color: #842029; }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .menu-lateral { width: 25%; }
            .contenido-principal {
                flex-basis: 75%;
                width: 75%;
                margin-left: 25%;
            }
        }

        @media (max-width: 767.98px) {
            .menu-lateral {
                position: relative;
                inset: auto;
                width: 100%;
                height: auto;
                max-height: none;
                min-height: 0;
                overflow: visible;
            }

            .contenido-principal {
                flex: 0 0 100%;
                width: 100%;
                margin-left: 0;
            }

            .menu-navegacion {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                flex: initial;
                overflow: visible;
                scrollbar-width: auto;
            }
            .contenido-principal { padding: 16px !important; }
            .contenido-principal .contenido-vista { margin: 0 !important; }
            .contenido-principal #buscar { width: 100%; max-width: 100%; }
            .contenido-principal table {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
            }
            .contenido-principal .d-flex { flex-wrap: wrap; }
            .contenido-principal .d-flex > * { min-width: 0; max-width: 100%; }
            .acciones-panel-encabezado { width: 100%; }
            .acciones-panel-encabezado .btn { flex: 1 1 145px; }
            .botones-incidencia-panel form { max-width: none; }
            .grupo-eventos-panel { width: 100%; flex-wrap: wrap; justify-content: flex-start; }
            .acciones-tandeo-panel { grid-template-columns: 1fr; }
        }

        @media (max-width: 420px) {
            .menu-navegacion { grid-template-columns: 1fr; }
            .contenido-principal { padding: 12px !important; }
        }
</style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none" aria-hidden="true">
        <symbol id="icono-panel" viewBox="0 0 16 16">
            <path d="M8 1 1 6.5V15h5V9h4v6h5V6.5L8 1z"/>
        </symbol>
        <symbol id="icono-colonias" viewBox="0 0 16 16">
            <path d="M8 0a5 5 0 0 0-5 5c0 3.8 5 11 5 11s5-7.2 5-11a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
        </symbol>
        <symbol id="icono-circuitos" viewBox="0 0 16 16">
            <path d="M1 2.5 5.5 1v13L1 15.5v-13zm5.5-1 4-1v13l-4 1v-13zm5 0L15 0v13l-3.5 1.5v-13z"/>
        </symbol>
        <symbol id="icono-programar" viewBox="0 0 16 16">
            <path d="M4 0h1v2h6V0h1v2h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h2V0zm10 6H2v9h12V6zM7.5 8v2h-2v1h2v2h1v-2h2v-1h-2V8h-1z"/>
        </symbol>
        <symbol id="icono-reportes" viewBox="0 0 16 16">
            <path d="M1 14h14v1H0V1h1v13zm2-2V8h2v4H3zm4 0V4h2v8H7zm4 0V6h2v6h-2z"/>
        </symbol>
        <symbol id="icono-calendario" viewBox="0 0 16 16">
            <path d="M3 0h1v2h8V0h1v2h2v14H1V2h2V0zm11 6H2v9h12V6zM4 8h2v2H4V8zm3 0h2v2H7V8zm3 0h2v2h-2V8z"/>
        </symbol>
        <symbol id="icono-usuarios" viewBox="0 0 16 16">
            <path d="M6 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm5-.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM0 14c0-3 2-5 6-5s6 2 6 5v1H0v-1zm11-4c3 0 5 1.6 5 4v1h-3v-1c0-1.7-.7-3-2-4z"/>
        </symbol>
        <symbol id="icono-historial" viewBox="0 0 16 16">
            <path d="M2 2h1v1H2V2zm3 0h10v1H5V2zM2 7h1v1H2V7zm3 0h10v1H5V7zM2 12h1v1H2v-1zm3 0h10v1H5v-1z"/>
        </symbol>
        <symbol id="icono-respaldo" viewBox="0 0 16 16">
            <path d="M8 0C4.1 0 1 1.1 1 2.5v11C1 14.9 4.1 16 8 16s7-1.1 7-2.5v-11C15 1.1 11.9 0 8 0zm0 1c3.6 0 6 .9 6 1.5S11.6 4 8 4 2 3.1 2 2.5 4.4 1 8 1zm0 14c-3.6 0-6-.9-6-1.5V12c1.2.8 3.5 1.2 6 1.2s4.8-.4 6-1.2v1.5c0 .6-2.4 1.5-6 1.5zm0-3c-3.6 0-6-.9-6-1.5V9c1.2.8 3.5 1.2 6 1.2s4.8-.4 6-1.2v1.5c0 .6-2.4 1.5-6 1.5zm0-3c-3.6 0-6-.9-6-1.5V6c1.2.8 3.5 1.2 6 1.2s4.8-.4 6-1.2v1.5c0 .6-2.4 1.5-6 1.5z"/>
        </symbol>
        <symbol id="icono-salir" viewBox="0 0 16 16">
            <path d="M2 1h7v2H4v10h5v2H2V1zm8 3 5 4-5 4V9H6V7h4V4z"/>
        </symbol>
        <symbol id="icono-apagon" viewBox="0 0 16 16">
            <path d="M9.5 0 3 9h4l-.5 7L13 7H9l.5-7z"/>
        </symbol>
        <symbol id="icono-laguna" viewBox="0 0 16 16">
            <path d="M8 0S3 5.7 3 9a5 5 0 0 0 10 0C13 5.7 8 0 8 0zM1 13c1.4 0 1.4 1 2.8 1s1.4-1 2.8-1 1.4 1 2.8 1 1.4-1 2.8-1 1.4 1 2.8 1v1c-1.4 0-1.4-1-2.8-1s-1.4 1-2.8 1-1.4-1-2.8-1-1.4 1-2.8 1S2.4 14 1 14v-1z"/>
        </symbol>
        <symbol id="icono-pozo" viewBox="0 0 16 16">
            <path d="M1 1h14v1H1V1zm2 1h1v5h8V2h1v6H3V2zm4 6h2v4H7V8zm-3 4h8v1H4v-1zm-2 2h12v1H2v-1z"/>
        </symbol>
        <symbol id="icono-editar" viewBox="0 0 16 16">
            <path d="M12.9 1.1a1.5 1.5 0 0 1 2.1 2.1L6 12.2l-3.4.9.9-3.4 9.4-8.6zM2 14h12v1H2v-1z"/>
        </symbol>
        <symbol id="icono-eliminar" viewBox="0 0 16 16">
            <path d="M6 1h4l1 1h3v1H2V2h3l1-1zM3 4h10l-.7 11H3.7L3 4zm3 2v7h1V6H6zm3 0v7h1V6H9z"/>
        </symbol>
        <symbol id="icono-completar" viewBox="0 0 16 16">
            <path d="M6.2 11.5 2.7 8l1.1-1.1 2.4 2.4 6-6L13.3 4l-7.1 7.5z"/>
        </symbol>
        <symbol id="icono-restaurar" viewBox="0 0 16 16">
            <path d="M8 2a6 6 0 1 1-5.7 4H.7L3.5 3l2.8 3H3.8A4.5 4.5 0 1 0 8 3.5V2zm-.7 2.5h1.4v3l2.1 1.3-.7 1.1-2.8-1.8V4.5z"/>
        </symbol>
        <symbol id="icono-crear" viewBox="0 0 16 16">
            <path d="M7 1h2v6h6v2H9v6H7V9H1V7h6V1z"/>
        </symbol>
    </svg>
    <div class="container-fluid">
        <div class="row">
            @auth
                <aside class="menu-lateral col-12 col-md-3 col-lg-2 border-end p-3 d-flex flex-column">
                    <div class="mb-4">
                        <h1 class="marca-sigeta mb-3"><span class="" aria-hidden="true"></span>SIGETA</h1>
                        <p class="mb-1"><strong>{{ Auth::user()->nombre }}</strong></p>
                        <p class="small mb-1 texto-menu-secundario">Usuario: {{ Auth::user()->usuario }}</p>
                    </div>

                    <nav class="menu-navegacion">
                        @if (Auth::user()->rol === 'tandeador')
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-panel"/></svg></span>Panel principal</a>
                            <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-calendario"/></svg></span>Mis tandeos asignados</a>
                            <a href="{{ route('calendario.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-calendario"/></svg></span>Calendario</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-panel"/></svg></span>Panel principal</a>
                            <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-colonias"/></svg></span>Colonias</a>
                            <a href="{{ route('circuitos.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-circuitos"/></svg></span>Circuitos</a>
                            <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-programar"/></svg></span>Programar un tandeo</a>
                            <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-reportes"/></svg></span>Reportes</a>
                            <a href="{{ route('calendario.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-calendario"/></svg></span>Calendario</a>

                            @if (Auth::user()->rol === 'administrador')
                                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-usuarios"/></svg></span>Usuarios</a>
                                <a href="{{ route('historial.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-historial"/></svg></span>Historial de actividad</a>
                                <a href="{{ route('respaldos.index') }}" class="btn btn-outline-secondary text-start"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-respaldo"/></svg></span>Respaldos y restauración</a>
                            @endif

                        @endif
                    </nav>

                    <form action="{{ route('cerrarSesion') }}" method="POST" class="mt-auto pt-4">
                        @csrf
                        <button type="submit" class="btn boton-cerrar-sesion"><span class="icono-menu" aria-hidden="true"><svg><use href="#icono-salir"/></svg></span>Cerrar sesión</button>
                    </form>
                </aside>
            @endauth

            <main class="col py-4 px-4 contenido-principal">
                @yield('content')
            </main>
        </div>
    </div>
    <dialog id="aviso-sigeta" class="aviso-sigeta" aria-labelledby="aviso-sigeta-titulo">
        <h2 id="aviso-sigeta-titulo">Confirmar acción</h2>
        <p data-aviso-sigeta-mensaje></p>
        <div class="acciones-aviso-sigeta">
            <button type="button" class="btn btn-outline-secondary" data-aviso-sigeta-cancelar>Cancelar</button>
            <button type="button" class="btn btn-primary" data-aviso-sigeta-aceptar>Continuar</button>
        </div>
    </dialog>
    <script>
        const prefijoFiltrosSigeta = 'sigeta_filtros_' + @json(Auth::id() ?? 'invitado') + '_';
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.alert-success, .alert-warning, .alert-info, .alert-danger, [role="status"], [role="alert"]').forEach(function (aviso) {
                if (aviso.classList.contains('d-none') || aviso.hidden) return;

                setTimeout(function () {
                    aviso.style.transition = 'opacity 0.4s ease';
                    aviso.style.opacity = '0';
                    setTimeout(function () { aviso.remove(); }, 400);
                }, 5000);
            });

            document.querySelectorAll('[data-filtros-persistentes]').forEach(function (grupo) {
                const nombre = grupo.dataset.filtrosPersistentes;
                const clave = prefijoFiltrosSigeta + nombre;
                const controles = Array.from(grupo.querySelectorAll('input[name], select[name]'));
                let guardados = {};

                try { guardados = JSON.parse(localStorage.getItem(clave) || '{}'); } catch (error) { guardados = {}; }

                const direccion = new URL(window.location.href);
                let faltaAplicar = false;

                controles.forEach(function (control) {
                    if (control.dataset.fechaReferencia === 'hoy') {
                        if (direccion.searchParams.has(control.name)) {
                            control.value = direccion.searchParams.get(control.name);
                        }
                        return;
                    }

                    if (direccion.searchParams.has(control.name)) {
                        control.value = direccion.searchParams.get(control.name);
                    } else if (Object.prototype.hasOwnProperty.call(guardados, control.name)) {
                        control.value = guardados[control.name];
                        if (control.value !== '' && grupo.tagName === 'FORM' && grupo.method.toLowerCase() === 'get') {
                            faltaAplicar = true;
                        }
                    }
                });

                function guardarFiltros() {
                    const datos = {};
                    controles.forEach(function (control) {
                        if (control.dataset.fechaReferencia === 'hoy') return;
                        datos[control.name] = control.value;
                    });
                    localStorage.setItem(clave, JSON.stringify(datos));
                }

                controles.forEach(function (control) {
                    control.addEventListener('input', guardarFiltros);
                    control.addEventListener('change', guardarFiltros);
                    control.dispatchEvent(new Event('input', { bubbles: true }));
                    control.dispatchEvent(new Event('change', { bubbles: true }));
                });

                if (faltaAplicar && Object.keys(guardados).length > 0) {
                    const destino = new URL(grupo.action || window.location.href, window.location.href);
                    Object.entries(guardados).forEach(function ([campo, valor]) {
                        const control = controles.find(function (elemento) { return elemento.name === campo; });
                        if (control && control.dataset.fechaReferencia === 'hoy') return;
                        if (valor !== '') destino.searchParams.set(campo, valor);
                    });
                    controles.forEach(function (control) {
                        if (control.dataset.fechaReferencia === 'hoy' && direccion.searchParams.has(control.name)) {
                            destino.searchParams.set(control.name, direccion.searchParams.get(control.name));
                        }
                    });
                    window.location.replace(destino.toString());
                }
            });

            document.querySelectorAll('[data-quitar-filtros]').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    localStorage.removeItem(prefijoFiltrosSigeta + boton.dataset.quitarFiltros);
                });
            });
        });
    </script>
</body>
</html>

