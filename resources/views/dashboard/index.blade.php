@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="workspace-intro">
    <div><span class="section-eyebrow">RESUMEN DEL NEGOCIO</span><h3>Una visión clara de tu operación</h3><p>Consulta tus ingresos, administra las licencias y mantén tus cobros al día.</p></div>
    <div class="workspace-actions"><a class="btn btn-outline-primary" href="{{ route('cobros.index') }}">Revisar mensualidades</a><a class="btn btn-primary" href="{{ route('licencias.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva licencia</a></div>
</section>
<div class="operations-strip">
    <div class="operations-heading"><span class="status-dot"></span><strong>Agenda de hoy</strong><span>{{ now()->timezone('America/Bogota')->format('d/m/Y') }}</span></div>
    <a href="{{ route('cobros.index', ['estado' => 'vencidas']) }}"><span class="operation-number">{{ $stats['cuotas_vencidas'] }}</span><span>Cuotas vencidas<small>${{ number_format($stats['cartera_vencida'], 0, ',', '.') }} por recuperar</small></span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    <a href="{{ route('cobros.index') }}"><span class="operation-number">{{ $stats['avisos'] }}</span><span>Cuotas por revisar<small>Próximas a vencer y atrasadas</small></span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    <a href="{{ route('clientes.create') }}"><i class="bi bi-person-plus operation-icon" aria-hidden="true"></i><span>Amplía tu cartera<small>Registrar un nuevo cliente</small></span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Clientes</span>
                <div class="stat-icon primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_clientes']) }}</div>
            <div class="stat-label">Clientes activos registrados</div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card success">
            <div class="stat-header">
                <span class="stat-title">Licencias Activas</span>
                <div class="stat-icon success">
                    <i class="bi bi-key"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['licencias_activas']) }}</div>
            <div class="stat-label">
                @if($stats['licencias_por_vencer'] > 0)
                    <span class="text-warning fw-bold">{{ $stats['licencias_por_vencer'] }} por vencer</span>
                @else
                    Todas al día
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Ingresos del Mes</span>
                <div class="stat-icon primary">
                    <i class="bi bi-graph-up"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['ingresos_mes'], 0, ',', '.') }}</div>
            <div class="stat-label">Total acumulado este mes</div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-header">
                <span class="stat-title">Ingresos del año</span>
                <div class="stat-icon warning">
                    <i class="bi bi-cart"></i>
                </div>
            </div>
            <div class="stat-value">${{ number_format($stats['ingresos_anio'], 0, ',', '.') }}</div>
            <div class="stat-label">Total acumulado {{ date('Y') }}</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-graph-up text-primary me-2"></i>
                    Ingresos Mensuales
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="ingresosChart"
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
                    Distribucion de Licencias
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="licenciasChart"
                    height="200"
                    data-labels='@json(array_column($licenciasPorTipo, "name"))'
                    data-values='@json(array_column($licenciasPorTipo, "value"))'
                ></canvas>
                <div class="row mt-4 text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0">{{ $stats['compras_totales'] }}</h4>
                        <small class="text-muted">Compras</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0">{{ $stats['alquileres_activos'] }}</h4>
                        <small class="text-muted">Alquileres</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-box text-primary me-2"></i>
                    Productos Mas Vendidos
                </h5>
            </div>
            <div class="card-body">
                <canvas
                    id="productosChart"
                    height="200"
                    data-labels='@json($productosMasVendidos->pluck("nombre")->toArray())'
                    data-values='@json($productosMasVendidos->pluck("licencias_count")->toArray())'
                ></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-trophy text-warning me-2"></i>
                    Top Clientes
                </h5>
            </div>
            <div class="card-body">
                @foreach($clientesTop as $index => $cliente)
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
                            <h6 class="mb-0">{{ $cliente->nombre }}</h6>
                            <small class="text-muted">{{ $cliente->empresa }}</small>
                        </div>
                        <div class="text-end">
                            <strong>${{ number_format($cliente->transacciones_sum_monto ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-key text-primary me-2"></i>
                    Licencias Recientes
                </h5>
            </div>
            <div class="card-body">
                @foreach($licenciasRecientes as $licencia)
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-key text-primary"></i>
                            </div>
                            <div>
                                <code class="d-block">{{ $licencia->clave_licencia }}</code>
                                <small class="text-muted">{{ $licencia->fecha_creacion->format('d M Y') }}</small>
                            </div>
                        </div>
                        <span class="badge badge-{{ $licencia->tipo }}">
                            {{ $licencia->tipo_label }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-cash-stack text-success me-2"></i>
                    Transacciones Recientes
                </h5>
            </div>
            <div class="card-body">
                @foreach($transaccionesRecientes as $transaccion)
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-2">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-graph-up text-success"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-medium">{{ $transaccion->descripcion }}</p>
                                <small class="text-muted">{{ $transaccion->fecha->format('d M Y') }}</small>
                            </div>
                        </div>
                        <span class="text-success fw-bold">
                            +${{ number_format($transaccion->monto, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
