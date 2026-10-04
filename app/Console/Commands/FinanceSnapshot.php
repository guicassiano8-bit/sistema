<?php

namespace App\Console\Commands;

use App\Services\InvestmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinanceSnapshot extends Command
{
    protected $signature = 'finance:snapshot {--mes= : Mês a fotografar no formato AAAA-MM (padrão: o mês atual)}';

    protected $description = 'Grava a fotografia mensal da carteira (aplicado e valor de cada ativo)';

    public function handle(InvestmentService $investimentos): int
    {
        $opcao = $this->option('mes');

        if ($opcao !== null && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $opcao)) {
            $this->error('Use o formato AAAA-MM, por exemplo 2026-09.');

            return self::INVALID;
        }

        $mes = $opcao !== null ? Carbon::createFromFormat('!Y-m', $opcao) : today();

        $total = $investimentos->fotografar($mes);

        $this->info("{$total} ativo(s) fotografado(s) em {$mes->format('m/Y')}.");

        return self::SUCCESS;
    }
}
