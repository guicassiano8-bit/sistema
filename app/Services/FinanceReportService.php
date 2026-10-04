<?php

namespace App\Services;

use App\Models\IncomeEntry;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;

/** Números agregados das abas Resumo e Relatórios do Tesouro. */
class FinanceReportService
{
    public function __construct(
        private AccountBalanceService $balances,
        private InvestmentService $investments,
        private TransactionService $transactions,
    ) {}

    /**
     * Patrimônio (contas ativas + investimentos ativos) e o fluxo do mês de $hoje.
     * Dinheiro vai como string decimal; `delta_pct` é nulo quando não há mês anterior para comparar.
     *
     * @return array{patrimonio: string, contas: string, investido: string, delta_pct: float|null, ganhos_mes: string, gastos_mes: string, saldo_mes: string, pct_gasto: int, passivo_mes: string}
     */
    public function resumo(CarbonInterface $hoje): array
    {
        $contas = $this->balances->saldoTotal();
        $investido = $this->investments->valorTotal();
        $patrimonio = $contas->plus($investido);

        ['entradas' => $ganhos, 'saidas' => $gastos] = $this->transactions->totaisDoMes($hoje);
        $saldoMes = BigDecimal::of($ganhos)->minus($gastos);

        return [
            'patrimonio' => (string) $patrimonio,
            'contas' => (string) $contas,
            'investido' => (string) $investido,
            'delta_pct' => $this->variacao($patrimonio, $hoje->copy()->startOfMonth()->subDay()),
            'ganhos_mes' => $ganhos,
            'gastos_mes' => $gastos,
            'saldo_mes' => (string) $saldoMes,
            // sem ganhos, qualquer gasto já é "100% do que ganhou"; sem nada, 0%
            'pct_gasto' => BigDecimal::of($ganhos)->isPositive()
                ? (int) min(100, round(BigDecimal::of($gastos)->multipliedBy(100)->dividedBy($ganhos, 2, RoundingMode::HalfUp)->toFloat()))
                : (BigDecimal::of($gastos)->isPositive() ? 100 : 0),
            'passivo_mes' => $this->decimal(IncomeEntry::query()->paidInMonth($hoje)->sum('amount')),
        ];
    }

    /** Variação do patrimônio sobre o fim do mês anterior; nulo se naquele dia não havia patrimônio. */
    private function variacao(BigDecimal $atual, CarbonInterface $fimDoMesAnterior): ?float
    {
        $anterior = $this->balances->saldoTotal($fimDoMesAnterior)->plus($this->investments->valorTotal($fimDoMesAnterior));

        if (! $anterior->isPositive()) {
            return null;
        }

        return $atual->minus($anterior)->multipliedBy(100)->dividedBy($anterior, 2, RoundingMode::HalfUp)->toFloat();
    }

    /** O SUM volta como float no SQLite; arredondar evita ruído nos centavos. */
    private function decimal(string|int|float|null $valor): string
    {
        return (string) BigDecimal::of((string) ($valor ?? 0))->toScale(2, RoundingMode::HalfUp);
    }
}
