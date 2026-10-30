<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <script>
        try {
            const savedTheme = localStorage.getItem('alfa-theme');
            document.documentElement.dataset.bsTheme = savedTheme === 'light' ? 'light' : 'dark';
        } catch (_) {}
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Alfa Digital Solutions</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/alfa-logo.png') }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell page-{{ str_replace('.', '-', request()->route()?->getName() ?? 'app') }}">
    <!-- Sidebar -->
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <button class="sidebar-backdrop" type="button" aria-label="Cerrar navegación" hidden></button>
    <aside class="sidebar" id="navigation">
        <div class="sidebar-header">
            <a class="brand brand-official" href="{{ route('dashboard') }}" aria-label="Alfa Digital Solutions SAS — Ir al inicio">
                <div class="brand-image-panel brand-image-full"><img src="{{ asset('images/brand/alfa-logo-full.png') }}" alt="Alfa Digital Solutions S.A.S." width="1024" height="1024" fetchpriority="high"></div>
            </a>
        </div>

        <nav class="nav-menu" aria-label="Navegación principal">
            <p class="nav-section-label">GESTIÓN DEL NEGOCIO</p>
            <div class="nav-item">
                <a href="{{ route('cobros.index') }}" class="nav-link {{ request()->routeIs('cobros.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i><span>Mensualidades y avisos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Clientes</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Productos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('licencias.index') }}" class="nav-link {{ request()->routeIs('licencias.*') ? 'active' : '' }}">
                    <i class="bi bi-key-fill"></i>
                    <span>Licencias</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('uso.index') }}" class="nav-link {{ request()->routeIs('uso.*') ? 'active' : '' }}">
                    <i class="bi bi-activity"></i>
                    <span>Seguimiento de Uso</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('reportes.index') }}" class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span>Reportes</span>
                </a>
            </div>
        </nav>
        <div class="sidebar-note"><i class="bi bi-shield-check" aria-hidden="true"></i><div>Tu operación, en un solo lugar<small>Clientes · licencias · cobros</small></div></div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-title">
                <button class="menu-toggle" type="button" aria-label="Abrir navegación" aria-controls="navigation" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
                <span class="header-eyebrow">PANEL DE ADMINISTRACIÓN</span>
                <h2>@yield('title', 'Dashboard')</h2>
            </div>
            <div class="header-actions">
                <button type="button" class="theme-toggle" aria-label="Cambiar tema" title="Cambiar tema"><i class="bi bi-moon-stars" aria-hidden="true"></i></button>
                <span class="header-date"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ now()->timezone('America/Bogota')->format('d/m/Y') }}</span>
                <a href="{{ route('cobros.index') }}" class="btn-notification" aria-label="Avisos de mensualidades">
                    <i class="bi bi-bell"></i>
                    <span class="badge">{{ \App\Models\Cuota::porRecordar()->count() }}</span>
                </a>
                <div class="user-profile">
                    <div class="user-info">
                        <p>Administrador</p>
                        <span>{{ config('cobros.correo') }}</span>
                    </div>
                    <div class="user-avatar">AD</div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content" id="contenido" tabindex="-1">
            @if(session('success'))
                <div data-flash-alert class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                </div>
            @endif

            @if(session('error'))
                <div data-flash-alert class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                </div>
            @endif

            @yield('content')
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-brand"><img src="{{ asset('images/brand/alfa-logo.png') }}" alt="Alfa" width="595" height="234" loading="lazy"><span>&copy; {{ date('Y') }} Alfa Digital Solutions SAS. Todos los derechos reservados.</span></div>
        </footer>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Swal) return;
            document.querySelectorAll('[data-flash-alert]').forEach((alert) => alert.remove());
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: { popup: 'alfa-toast' },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                },
            });

            @if(session('success'))
                Toast.fire({ icon: 'success', title: @json(session('success')) });
            @endif

            @if(session('error'))
                Toast.fire({ icon: 'error', title: @json(session('error')) });
            @endif

            @if($errors->any())
                const errors = @json($errors->all());
                const errorList = document.createElement('ul');
                errorList.className = 'text-start mb-0 ps-3';
                errors.forEach((error) => {
                    const item = document.createElement('li');
                    item.textContent = error;
                    errorList.appendChild(item);
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Revisa la información',
                    html: errorList,
                    confirmButtonText: 'Entendido',
                    customClass: { popup: 'alfa-swal' },
                });
            @endif

        });
    </script>
    @stack('scripts')
</body>
</html>
