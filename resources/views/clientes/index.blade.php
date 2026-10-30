@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="workspace-intro">
    <div>
        <span class="section-eyebrow">DIRECTORIO / CLIENTES</span><h3>Relaciones que hacen crecer tu negocio</h3><p>Toda la información de tus clientes, organizada para atenderlos mejor.</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Cliente
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('clientes.index') }}" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, email, empresa o telefono..." value="{{ request('busqueda') }}">
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
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-people fs-4 text-primary"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ \App\Models\Cliente::count() }}</h4>
                    <small class="text-muted">Total Clientes</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-building fs-4 text-success"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ \App\Models\Cliente::distinct('empresa')->count() }}</h4>
                    <small class="text-muted">Empresas</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-geo-alt fs-4 text-info"></i>
                </div>
                <div>
                    <h4 class="mb-0">{{ \App\Models\Cliente::whereNotNull('ciudad')->distinct('ciudad')->count() }}</h4>
                    <small class="text-muted">Ciudades</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    @forelse($clientes as $cliente)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center cliente-avatar">
                                {{ $cliente->iniciales }}
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0">{{ $cliente->nombre }}</h6>
                                <small class="text-muted">{{ $cliente->empresa }}</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('clientes.edit', $cliente) }}"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                                <li>
                                    <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="d-inline" data-confirm="Estas seguro de eliminar este cliente?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope text-muted me-2"></i>
                            <small class="text-truncate">{{ $cliente->email }}</small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone text-muted me-2"></i>
                            <small>{{ $cliente->telefono }}</small>
                        </div>
                        @if($cliente->ciudad)
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt text-muted me-2"></i>
                                <small>{{ $cliente->ciudad }}{{ $cliente->pais ? ', '.$cliente->pais : '' }}</small>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="badge bg-{{ $cliente->tipo == 'comprador' ? 'primary' : ($cliente->tipo == 'arrendatario' ? 'success' : 'info') }}">
                            {{ $cliente->tipo_label }}
                        </span>
                        <div class="text-end">
                            <small class="text-muted d-block">Licencias</small>
                            <strong>{{ $cliente->licencias->count() }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No se encontraron clientes</h5>
                <p class="text-muted">Intenta ajustar los filtros o agrega un nuevo cliente</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $clientes->links() }}
</div>
@endsection
