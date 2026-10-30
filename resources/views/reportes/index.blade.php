@extends('layouts.app')

@section('title', 'Reportes Financieros')

@section('content')
<div class="workspace-intro">
    <div>
        <span class="section-eyebrow">ANÁLISIS / FINANZAS</span><h3>Los números detrás de tu negocio</h3><p>Analiza tus ingresos y exporta las transacciones del año seleccionado.</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('reportes.index') }}" method="GET" class="d-flex gap-2">
            <select aria-label="Seleccionar año del reporte" name="anio" class="form-select reportes-year-select">
                @foreach($aniosDisponibles as $anio)
                    <option value="{{ $anio }}" {{ $anioSeleccionado == $anio ? 'selected' : '' }}>{{ $anio }}</option>
                @endforeach
            </select>
            <button type="submit" aria-label="Aplicar año seleccionado" class="btn btn-outline-primary">
                <i class="bi bi-filter"></i>
            </button>
        </form>
        <a href="{{ route('reportes.exportar', ['anio' => $anioSeleccionado]) }}" class="btn btn-outline-success">
            <i class="bi bi-download me-2"></i>Exportar CSV
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Ingresos</span>
                <div class="stat-icon primary">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['total_ingresos'], 0, ',', '.') }}</div>
            <div class="stat-label">{{ $stats['cantidad_transacciones'] }} transacciones</div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card success">
            <div class="stat-header">
                <span class="stat-title">Ventas</span>
                <div class="stat-icon success">
                    <i class="bi bi-graph-up"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['total_ventas'], 0, ',', '.') }}</div>
            <div class="stat-label">{{ $stats['total_ingresos'] > 0 ? round(($stats['total_ventas'] / $stats['total_ingresos']) * 100) : 0 }}% del total</div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-header">
                <span class="stat-title">Alquileres</span>
                <div class="stat-icon warning">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['total_alquileres'], 0, ',', '.') }}</div>
            <div class="stat-label">{{ $stats['total_ingresos'] > 0 ? round(($stats['total_alquileres'] / $stats['total_ingresos']) * 100) : 0 }}% del total</div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-header">
                <span class="stat-title">Promedio</span>
                <div class="stat-icon stat-icon-accent">
                    <i class="bi bi-bar-chart"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['promedio_transaccion'], 0, ',', '.') }}</div>
            <div class="stat-label">Por transaccion</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-graph-up text-primary me-2"></i>
                    Ingresos Mensuales {{ $anioSeleccionado }}
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="ingresosMensualesChart"
                    height="280"
                    data-labels='@json(array_column($ingresosPorMes, "mes"))'
                    data-values='@json(array_column($ingresosPorMes, "monto"))'
                ></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-pie-chart text-success me-2"></i>
                    Distribucion por Tipo
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="distribucionChart"
                    height="200"
                    data-labels='@json(array_column($ingresosPorTipo, "name"))'
                    data-values='@json(array_column($ingresosPorTipo, "value"))'
                ></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-trophy text-warning me-2"></i>
                    Top Clientes por Gasto
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="topClientesChart"
                    height="250"
                    data-labels='@json($topClientes->pluck("nombre")->map(fn($n) => explode(" ", $n)[0])->toArray())'
                    data-values='@json($topClientes->pluck("transacciones_sum_monto")->toArray())'
                ></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-box text-primary me-2"></i>
                    Top Productos por Ingresos
                </h5>
            </div>
            <div class="card-body">
                @foreach($topProductos as $index => $producto)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            @if($index == 0)
                                <span class="badge bg-warning text-dark fs-6">1</span>
                            @elseif($index == 1)
                                <span class="badge bg-secondary fs-6">2</span>
                            @elseif($index == 2)
                                <span class="badge bg-danger fs-6">3</span>
                            @else
                                <span class="badge bg-light text-dark fs-6">{{ $index + 1 }}</span>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">{{ $producto->nombre }}</h6>
                        </div>
                        <div class="text-end">
                            <strong>${{ number_format($producto->licencias_sum_precio_total ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="bi bi-file-text text-primary me-2"></i>
            Transacciones Recientes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Descripcion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaccionesRecientes as $transaccion)
                        <tr>
                            <td>{{ $transaccion->fecha->format('d M Y') }}</td>
                            <td>{{ $transaccion->cliente->nombre }}</td>
                            <td>
                                <span class="badge bg-{{ $transaccion->tipo == 'venta' ? 'primary' : ($transaccion->tipo == 'alquiler' ? 'success' : 'warning') }}">
                                    {{ $transaccion->tipo_label }}
                                </span>
                            </td>
                            <td class="text-success fw-bold">+${{ number_format($transaccion->monto, 0, ',', '.') }}</td>
                            <td>{{ $transaccion->descripcion }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
