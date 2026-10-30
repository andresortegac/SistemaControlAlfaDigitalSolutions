@extends('layouts.app')

@section('title', 'Seguimiento de Uso')

@section('content')
<div class="mb-4">
    <span class="section-eyebrow">ACTIVIDAD / LICENCIAS</span><h3 class="mb-2">La actividad de tus licencias</h3>
    <p class="text-muted mb-0">Monitorea el uso de las licencias por parte de los usuarios</p>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-activity fs-4 text-primary"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['total_registros'] }}</h4>
                    <small class="text-muted">Total Registros</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-box-arrow-in-right fs-4 text-success"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['activos_hoy'] }}</h4>
                    <small class="text-muted">Logins Hoy</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-person-check fs-4 text-info"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ max(0, $stats['sesiones_activas']) }}</h4>
                    <small class="text-muted">Sesiones Activas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-shield-exclamation fs-4 text-danger"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['bloqueos'] }}</h4>
                    <small class="text-muted">Bloqueos</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('uso.index') }}" method="GET" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por usuario, email, dispositivo o cliente..." value="{{ request('busqueda') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="accion" class="form-select">
                    <option value="todas">Todas las acciones</option>
                    @foreach($acciones as $key => $label)
                        <option value="{{ $key }}" {{ request('accion') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="todos">Todos</option>
                    @foreach($estados as $key => $label)
                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Registro de Actividad -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bi bi-clock-history text-primary me-2"></i>
            Registro de Actividad
        </h5>
    </div>
    <div class="card-body">
        @forelse($registros as $registro)
            <div class="d-flex align-items-start p-3 bg-light rounded mb-2">
                <div class="flex-shrink-0">
                    @switch($registro->accion)
                        @case('login')
                            <span class="badge bg-primary"><i class="bi bi-box-arrow-in-right"></i></span>
                            @break
                        @case('logout')
                            <span class="badge bg-warning"><i class="bi bi-box-arrow-right"></i></span>
                            @break
                        @case('activacion')
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i></span>
                            @break
                        @case('desactivacion')
                            <span class="badge bg-secondary"><i class="bi bi-x-circle"></i></span>
                            @break
                        @case('bloqueo')
                            <span class="badge bg-danger"><i class="bi bi-shield-exclamation"></i></span>
                            @break
                    @endswitch
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">{{ $registro->usuario_nombre }}</h6>
                            <small class="text-muted">{{ $registro->usuario_email }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $registro->accion == 'login' ? 'primary' : ($registro->accion == 'logout' ? 'warning' : ($registro->accion == 'activacion' ? 'success' : ($registro->accion == 'bloqueo' ? 'danger' : 'secondary'))) }}">
                                {{ $registro->accion_label }}
                            </span>
                            <span class="badge bg-{{ $registro->estado == 'activo' ? 'success' : ($registro->estado == 'bloqueado' ? 'danger' : 'secondary') }}">
                                {{ $registro->estado_label }}
                            </span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <small class="text-muted"><i class="bi bi-key me-1"></i>{{ $registro->licencia->producto->nombre ?? 'N/A' }}</small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $registro->licencia->cliente->nombre ?? 'N/A' }}</small>
                        </div>
                        @if($registro->dispositivo)
                            <div class="col-md-4">
                                <small class="text-muted"><i class="bi bi-laptop me-1"></i>{{ $registro->dispositivo }}</small>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <small class="text-muted"><i class="bi bi-calendar me-1"></i>{{ $registro->fecha_acceso->format('d M Y H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="bi bi-activity display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No se encontraron registros</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Uso por Licencia -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bi bi-key text-primary me-2"></i>
            Uso por Licencia
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($usoPorLicencia as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-key text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $item['licencia']->producto->nombre }}</h6>
                                    <small class="text-muted">{{ $item['licencia']->cliente->nombre }}</small>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <h6 class="mb-0">{{ $item['total_accesos'] }}</h6>
                                    <small class="text-muted">Accesos</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="mb-0">{{ $item['usuarios_activos'] }}</h6>
                                    <small class="text-muted">Usuarios</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="mb-0">{{ $item['ultimo_acceso'] ? $item['ultimo_acceso']->diffForHumans() : 'Nunca' }}</h6>
                                    <small class="text-muted">Último</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Paginación -->
<div class="mt-4">
    {{ $registros->links() }}
</div>
@endsection
