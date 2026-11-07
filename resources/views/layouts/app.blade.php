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
            position: sticky;
            top: 0;
            height: 100vh;
            max-height: 100vh;
            min-height: 100vh;
            overflow: hidden;
            background: #5b9bd5;
            color: #ffffff;
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
            gap: 8px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .menu-navegacion::-webkit-scrollbar {
            display: none;
        }

        .contenido-principal {
            min-width: 0;
            width: 100%;
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

        @media (max-width: 767.98px) {
            .menu-lateral {
                position: static;
                height: auto;
                max-height: none;
                min-height: 0;
                overflow: visible;
            }

            .menu-navegacion {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                flex: initial;
                overflow: visible;
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
        }

        @media (max-width: 420px) {
            .menu-navegacion { grid-template-columns: 1fr; }
            .contenido-principal { padding: 12px !important; }
        }
</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            @auth
                <aside class="menu-lateral col-12 col-md-3 col-lg-2 border-end p-3 d-flex flex-column">
                    <div class="mb-4">
                        <h1 class="marca-sigeta mb-3"><span class="marca-gota" aria-hidden="true"></span>SIGETA</h1>
                        <p class="mb-1"><strong>{{ Auth::user()->nombre }}</strong></p>
                        <p class="small mb-1 texto-menu-secundario">Usuario: {{ Auth::user()->usuario }}</p>
                    </div>

                    <nav class="menu-navegacion">
                        @if (Auth::user()->rol === 'tandeador')
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary text-start">Panel principal</a>
                            <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start">Mis tandeos asignados</a>
                            <a href="{{ route('calendario.index') }}" class="btn btn-outline-secondary text-start">Calendario</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary text-start">Panel principal</a>
                            <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary text-start">Colonias</a>
                            <a href="{{ route('circuitos.index') }}" class="btn btn-outline-secondary text-start">Circuitos</a>
                            <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start">Programar un tandeo</a>
                            <a href="{{ route('cumplimiento.index') }}" class="btn btn-outline-secondary text-start">Registrar cumplimiento</a>
                            <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary text-start">Reportes</a>
                            <a href="{{ route('calendario.index') }}" class="btn btn-outline-secondary text-start">Calendario</a>

                            @if (Auth::user()->rol === 'administrador')
                                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary text-start">Usuarios</a>
                                <a href="{{ route('historial.index') }}" class="btn btn-outline-secondary text-start">Historial de actividad</a>
                                <a href="{{ route('respaldos.index') }}" class="btn btn-outline-secondary text-start">Respaldos de base de datos</a>
                            @endif

                        @endif
                    </nav>

                    <form action="{{ route('cerrarSesion') }}" method="POST" class="mt-auto pt-4">
                        @csrf
                        <button type="submit" class="btn boton-cerrar-sesion">Cerrar sesión</button>
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
            document.querySelectorAll('[data-filtros-persistentes]').forEach(function (grupo) {
                const nombre = grupo.dataset.filtrosPersistentes;
                const clave = prefijoFiltrosSigeta + nombre;
                const controles = Array.from(grupo.querySelectorAll('input[name], select[name]'));
                let guardados = {};

                try { guardados = JSON.parse(localStorage.getItem(clave) || '{}'); } catch (error) { guardados = {}; }

                const direccion = new URL(window.location.href);
                let faltaAplicar = false;

                controles.forEach(function (control) {
                    if (direccion.searchParams.has(control.name)) {
                        control.value = direccion.searchParams.get(control.name);
                    } else if (Object.prototype.hasOwnProperty.call(guardados, control.name)) {
                        control.value = guardados[control.name];
                        if (grupo.tagName === 'FORM' && grupo.method.toLowerCase() === 'get') {
                            faltaAplicar = true;
                        }
                    }
                });

                function guardarFiltros() {
                    const datos = {};
                    controles.forEach(function (control) { datos[control.name] = control.value; });
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
                        if (valor !== '') destino.searchParams.set(campo, valor);
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

