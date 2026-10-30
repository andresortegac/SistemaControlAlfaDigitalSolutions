<?php

namespace App\Console\Commands;

use App\Models\Recordatorio;
use Illuminate\Console\Command;

class ReintentarRecordatorio extends Command
{
    protected $signature = 'cobros:reintentar {recordatorio} {--confirmar : Confirmo que comprobé con el proveedor que no se envió}';

    protected $description = 'Habilitar otro intento después de revisar el resultado con el proveedor';

    public function handle(): int
    {
        if (! $this->option('confirmar')) {
            $this->error('Comprueba primero si el proveedor envió el mensaje. Usa --confirmar solo si no se envió.');

            return self::FAILURE;
        }
        $registro = Recordatorio::findOrFail($this->argument('recordatorio'));
        if (! in_array($registro->estado, ['revisar', 'error']) || $registro->cuota->pagada_at) {
            $this->error('Este recordatorio no admite otro intento.');

            return self::FAILURE;
        }
        $registro->update(['estado' => 'pendiente', 'intentos' => 0, 'reintentar_at' => null, 'detalle' => 'Nuevo intento autorizado tras revisión del proveedor.']);
        $this->info('Se procesará durante la siguiente revisión automática.');

        return self::SUCCESS;
    }
}
