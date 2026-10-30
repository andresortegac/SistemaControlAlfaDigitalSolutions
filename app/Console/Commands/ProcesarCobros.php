<?php

namespace App\Console\Commands;

use App\Services\EnvioRecordatorios;
use Illuminate\Console\Command;

class ProcesarCobros extends Command
{
    protected $signature = 'cobros:procesar {--enviar : Enviar mediante los canales conectados y activados}';

    protected $description = 'Revisar cuotas y preparar o enviar recordatorios de cobro';

    public function handle(EnvioRecordatorios $servicio): int
    {
        $total = $servicio->procesar((bool) $this->option('enviar'));
        $this->info("Revisión finalizada. Recordatorios aceptados por los proveedores: {$total}.");
        foreach (['whatsapp', 'email'] as $canal) {
            if ($motivo = $servicio->impedimento($canal)) {
                $this->warn($motivo);
            }
        }

        return self::SUCCESS;
    }
}
