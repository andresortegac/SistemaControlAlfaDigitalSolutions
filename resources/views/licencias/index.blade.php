@extends('layouts.app')

@section('title', 'Licencias')

@section('content')
<div class="workspace-intro">
    <div>
        <span class="section-eyebrow">OPERACIÓN / LICENCIAS</span><h3>El control de cada licencia</h3><p>Consulta contratos, estados y vencimientos en un solo lugar.</p>
    </div>
    <a href="{{ route('licencias.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nueva Licencia
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('licencias.index') }}" method="GET" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por clave, cliente o producto..." value="{{ request('busqueda') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="tipo" class="form-select">
                    <option value="todos">Todos los tipos</option>
                    @foreach($tipos as $key => $label)
                        <option value="{{ $key }}" {{ request('tipo') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="todos">Todos los estados</option>
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

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-check-circle fs-4 text-success"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['activas'] }}</h4>
                    <small class="text-muted">Activas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-key fs-4 text-primary"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['compras'] }}</h4>
                    <small class="text-muted">Compras</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-arrow-repeat fs-4 text-info"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['alquileres'] }}</h4>
                    <small class="text-muted">Alquileres</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ $stats['por_vencer'] }}</h4>
                    <small class="text-muted">Por vencer</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    @forelse($licencias as $licencia)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-key text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $licencia->producto->nombre }}</h6>
                                <small class="text-muted">{{ $licencia->cliente->nombre }}</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('licencias.edit', $licencia) }}"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                                <li>
                                    <form action="{{ route('licencias.toggle-estado', $licencia) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-{{ $licencia->estado == 'activa' ? 'pause-circle' : 'play-circle' }} me-2"></i>
                                            {{ $licencia->estado == 'activa' ? 'Suspender' : 'Activar' }}
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <form action="{{ route('licencias.destroy', $licencia) }}" method="POST" class="d-inline" data-confirm="Estas seguro de eliminar esta licencia?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-light p-2 rounded mb-3 d-flex justify-content-between align-items-center">
                        <code>{{ $licencia->clave_licencia }}</code>
                        <button type="button" class="btn btn-sm btn-link js-copy-trigger" data-copy-text="{{ $licencia->clave_licencia }}">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block"><i class="bi bi-calendar me-1"></i>Inicio</small>
                            <span>{{ $licencia->fecha_inicio->format('d M Y') }}</span>
                        </div>
                        @if($licencia->fecha_vencimiento)
                            <div class="col-6">
                                <small class="text-muted d-block"><i class="bi bi-calendar-event me-1"></i>Vence</small>
                                <span class="{{ $licencia->por_vencer ? 'text-warning fw-bold' : '' }}">
                                    {{ $licencia->fecha_vencimiento->format('d M Y') }}
                                    @if($licencia->dias_restantes !== null)
                                        <small>({{ $licencia->dias_restantes }} dias)</small>
                                    @endif
                                </span>
                            </div>
                        @endif
                        <div class="col-6">
                            <small class="text-muted d-block"><i class="bi bi-people me-1"></i>Usuarios</small>
                            <span>{{ $licencia->cantidad_usuarios }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block"><i class="bi bi-cash me-1"></i>Total</small>
                            <span class="fw-bold">{{ $licencia->precio_total_formateado }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <span class="badge badge-{{ $licencia->tipo }}">{{ $licencia->tipo_label }}</span>
                            <span class="badge badge-{{ $licencia->estado }}">{{ $licencia->estado_label }}</span>
                        </div>
                        @if($licencia->renovacion_automatica)
                            <span class="badge bg-info"><i class="bi bi-arrow-repeat me-1"></i>Auto</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-key display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No se encontraron licencias</h5>
                <p class="text-muted">Intenta ajustar los filtros o crea una nueva licencia</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $licencias->links() }}
</div>
@endsection
