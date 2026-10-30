<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_id')->constrained('cuotas')->cascadeOnDelete();
            $table->string('canal');
            $table->string('estado')->default('pendiente');
            $table->unsignedInteger('intentos')->default(0);
            $table->string('detalle')->nullable();
            $table->string('proveedor_id')->nullable();
            $table->timestamp('aceptado_at')->nullable();
            $table->timestamp('reintentar_at')->nullable();
            $table->timestamps();
            $table->unique(['cuota_id', 'canal']);
            $table->index(['estado', 'reintentar_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
