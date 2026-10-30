@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="workspace-intro">
    <div>
        <span class="section-eyebrow">CATÁLOGO / PRODUCTOS</span><h3>Tu portafolio de soluciones</h3><p>Administra el catálogo, los precios y la disponibilidad de tu software.</p>
    </div>
    <a href="{{ route('productos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Producto
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('productos.index') }}" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, descripcion o version..." value="{{ request('busqueda') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="categoria" class="form-select">
                    <option value="todas">Todas las categorias</option>
                    @foreach($categorias as $key => $label)
                        <option value="{{ $key }}" {{ request('categoria') == $key ? 'selected' : '' }}>{{ $label }}</option>
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

<nav class="category-tabs mb-4" aria-label="Filtrar productos por categoría">
    <a href="{{ route('productos.index', array_filter(['busqueda' => request('busqueda')])) }}" class="category-tab {{ !request('categoria') || request('categoria') === 'todas' ? 'is-active' : '' }}" @if(!request('categoria') || request('categoria') === 'todas') aria-current="page" @endif>Todos <span>{{ $conteos->sum() }}</span></a>
    @foreach($categorias as $key => $label)
        <a href="{{ route('productos.index', array_filter(['categoria' => $key, 'busqueda' => request('busqueda')])) }}" class="category-tab {{ request('categoria') === $key ? 'is-active' : '' }}" @if(request('categoria') === $key) aria-current="page" @endif>{{ $label }} <span>{{ $conteos[$key] ?? 0 }}</span></a>
    @endforeach
</nav>

<div class="row g-4">
    @forelse($productos as $producto)
        <div class="col-md-6 col-xl-4">
            <div class="card product-card h-100 {{ !$producto->activo ? 'product-inactive' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded p-2 me-3 producto-icon">
                                <i class="bi bi-{{ $producto->icono }}" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $producto->nombre }}</h6>
                                <small class="text-muted">v{{ $producto->version }}</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('productos.edit', $producto) }}"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                                <li>
                                    <form action="{{ route('productos.toggle-activo', $producto) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-{{ $producto->activo ? 'pause-circle' : 'play-circle' }} me-2"></i>
                                            {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="d-inline" data-confirm="Estas seguro de eliminar este producto?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <p class="text-muted mb-3 producto-descripcion">{{ Str::limit($producto->descripcion, 100) }}</p>

                    <div class="row g-2 mb-3 product-prices">
                        <div class="col-4 text-center p-2 bg-light rounded">
                            <small class="text-muted d-block">Compra</small>
                            <strong class="d-block">{{ $producto->getPrecioFormateado('compra') }}</strong>
                        </div>
                        <div class="col-4 text-center p-2 bg-light rounded">
                            <small class="text-muted d-block">Mensual</small>
                            <strong class="d-block">{{ $producto->getPrecioFormateado('mensual') }}</strong>
                        </div>
                        <div class="col-4 text-center p-2 bg-light rounded">
                            <small class="text-muted d-block">Anual</small>
                            <strong class="d-block">{{ $producto->getPrecioFormateado('anual') }}</strong>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="product-category">
                            {{ $producto->categoria_label }}
                        </span>
                        <div class="text-end">
                            <small class="text-muted d-block">Licencias</small>
                            <strong>{{ $producto->licencias_count }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-box display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No se encontraron productos</h5>
                <p class="text-muted">Intenta ajustar los filtros o agrega un nuevo producto</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $productos->links() }}
</div>
@endsection
