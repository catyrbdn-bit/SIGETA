<!DOCTYPE html>
<html lang="es">
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

    <style>
        html,
        body {
            width: 100%;
            min-width: 320px;
            overflow-x: hidden;
        }

        .menu-lateral {
            position: sticky;
            top: 0;
            height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            background: #f5f7fa;
        }

        .menu-navegacion {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .contenido-principal {
            min-width: 0;
            width: 100%;
            max-width: 100%;
        }

        .contenido-principal > * {
            max-width: 100%;
        }

        .contenido-principal .container {
            width: 100%;
            max-width: 100%;
        }

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
                overflow: visible;
            }

            .menu-navegacion {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .contenido-principal {
                padding: 16px !important;
            }

            .contenido-principal .contenido-vista {
                margin: 0 !important;
            }

            .contenido-principal #buscar {
                width: 100%;
                max-width: 100%;
            }

            .contenido-principal table {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
            }

            .contenido-principal .d-flex {
                flex-wrap: wrap;
            }

            .contenido-principal .d-flex > * {
                min-width: 0;
                max-width: 100%;
            }
        }

        @media (max-width: 420px) {
            .menu-navegacion {
                grid-template-columns: 1fr;
            }

            .contenido-principal {
                padding: 12px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            @auth
                <aside class="menu-lateral col-12 col-md-3 col-lg-2 border-end p-3 d-flex flex-column">
                    <div class="mb-4">
                        <h1 class="h4 mb-3">SIGETA</h1>
                        <p class="mb-1"><strong>{{ Auth::user()->nombre }}</strong></p>
                        <p class="small text-muted mb-1">Usuario: {{ Auth::user()->usuario }}</p>
                        <p class="small text-muted mb-0">Rol: {{ Auth::user()->rol }}</p>
                    </div>

                    <nav class="menu-navegacion">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary text-start">Panel principal</a>
                        <a href="{{ route('cumplimiento.index') }}" class="btn btn-outline-secondary text-start">Registrar cumplimiento de un tandeo</a>
                        <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start">Programar un tandeo</a>
                        <a href="{{ route('circuitos.index') }}" class="btn btn-outline-secondary text-start"> Ver Circuitos</a>
                        <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary text-start">Ver Colonias</a>


                        <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start">Calendario</a>
                    </nav>

                    <form action="{{ route('cerrarSesion') }}" method="POST" class="mt-auto pt-4">
                        @csrf
                        <button type="submit" class="btn btn-link px-0">Cerrar sesión</button>
                    </form>
                </aside>
            @endauth

            <main class="col py-4 px-4 contenido-principal">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>