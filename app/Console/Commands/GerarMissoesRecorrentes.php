<?php

namespace App\Console\Commands;

use App\Services\RecurrenceService;
use Illuminate\Console\Command;

class GerarMissoesRecorrentes extends Command
{
    protected $signature = 'missoes:gerar-recorrentes {--dias=30 : Quantos dias à frente gerar}';

    protected $description = 'Cria as próximas ocorrências das missões recorrentes';

    public function handle(RecurrenceService $recorrencia): int
    {
        $dias = max(1, (int) $this->option('dias'));

        $criadas = $recorrencia->gerar(today()->addDays($dias));

        $this->info("{$criadas} missão(ões) criada(s).");

        return self::SUCCESS;
    }
}
