<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licencia_id')->constrained('licencias')->cascadeOnDelete();
            $table->date('fecha_ancla');
            $table->unsignedInteger('periodo')->default(0);
            $table->date('vencimiento');
            $table->decimal('monto', 15, 2);
            $table->timestamp('pagada_at')->nullable();
            $table->string('referencia_pago')->nullable();
            $table->foreignId('transaccion_id')->nullable()->constrained('transacciones')->nullOnDelete();
            $table->timestamps();
            $table->unique(['licencia_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
