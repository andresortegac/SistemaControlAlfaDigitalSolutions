<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/cobros', [\App\Http\Controllers\CobroController::class, 'index'])->name('cobros.index');
Route::post('/cobros/licencias/{licencia}', [\App\Http\Controllers\CobroController::class, 'activar'])->name('cobros.activar');
Route::post('/cobros/{cuota}/confirmar', [\App\Http\Controllers\CobroController::class, 'confirmar'])->name('cobros.confirmar');

// Clientes
Route::resource('clientes', ClienteController::class);

// Productos
Route::resource('productos', ProductoController::class);
Route::patch('/productos/{producto}/toggle-activo', [ProductoController::class, 'toggleActivo'])->name('productos.toggle-activo');

// Licencias
Route::resource('licencias', LicenciaController::class);
Route::patch('/licencias/{licencia}/toggle-estado', [LicenciaController::class, 'toggleEstado'])->name('licencias.toggle-estado');

// Reportes
Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
Route::get('/reportes/exportar', [ReporteController::class, 'exportarCsv'])->name('reportes.exportar');

// Seguimiento de Uso
Route::get('/uso', [UsoController::class, 'index'])->name('uso.index');
