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
        .menu-lateral {
            position: sticky;
            top: 0;
            height: 100vh;
            background: #f5f7fa;
        }

        @media (max-width: 767.98px) {
            .menu-lateral {
                position: static;
                height: auto;
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

                    <nav class="d-flex flex-column gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary text-start">Panel principal</a>
                        <a href="{{ route('cumplimiento.index') }}" class="btn btn-outline-primary text-start">Gestión de cumplimiento</a>
                        <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary text-start">Colonias</a>
                        <a href="{{ route('circuitos.index') }}" class="btn btn-outline-secondary text-start">Circuitos</a>
                        <a href="{{ route('tandeos.index') }}" class="btn btn-outline-secondary text-start">Tandeos programados</a>
                    </nav>

                    <form action="{{ route('cerrarSesion') }}" method="POST" class="mt-auto pt-4">
                        @csrf
                        <button type="submit" class="btn btn-link px-0">Cerrar sesión</button>
                    </form>
                </aside>
            @endauth

            <main class="col py-4 px-4">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>