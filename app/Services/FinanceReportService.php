<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\IncomeEntry;
use App\Models\PortfolioSnapshot;
use App\Models\Transaction;
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

    /**
     * Dados dos gráficos da aba Relatórios. Valores em float: servem só para desenhar e escrever.
     *
     * @return array{serie12m: array<int, array{label: string, value: float}>, fluxo6m: array{labels: array<int, string>, ganhos: array<int, float>, gastos: array<int, float>}, porCategoria: array<int, array{label: string, value: float}>}
     */
    public function relatorios(CarbonInterface $hoje): array
    {
        return [
            'serie12m' => $this->patrimonioPorMes($hoje, 12),
            'fluxo6m' => $this->fluxoPorMes($hoje, 6),
            'porCategoria' => $this->gastosPorCategoria($hoje),
        ];
    }

    /**
     * Patrimônio no fim de cada mês (o mês atual vai até hoje). Meses que o `finance:snapshot` já
     * fotografou usam o valor guardado dos investimentos; os demais são recalculados.
     * Meses sem nada antes do primeiro dado são cortados; sem nenhum dado, volta vazio.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function patrimonioPorMes(CarbonInterface $hoje, int $meses): array
    {
        $pontos = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = $hoje->copy()->startOfMonth()->subMonths($i);
            $atual = $i === 0;
            $fim = $atual ? $hoje->copy() : $mes->copy()->endOfMonth();

            $investido = $atual ? $this->investments->valorTotal() : $this->investidoNoFim($mes, $fim);
            $contas = $atual ? $this->balances->saldoTotal() : $this->balances->saldoTotal($fim);

            $pontos[] = ['label' => $this->rotulo($mes, true), 'value' => $contas->plus($investido)];
        }

        while ($pontos !== [] && $pontos[0]['value']->isZero()) {
            array_shift($pontos);
        }

        return array_map(fn (array $p) => ['label' => $p['label'], 'value' => $p['value']->toFloat()], $pontos);
    }

    private function investidoNoFim(CarbonInterface $mes, CarbonInterface $fim): BigDecimal
    {
        $fotografias = PortfolioSnapshot::query()->where('reference_month', $mes->copy()->startOfMonth());

        return $fotografias->exists()
            ? $this->parse($fotografias->sum('market_value'))
            : $this->investments->valorTotal($fim);
    }

    /**
     * @return array{labels: array<int, string>, ganhos: array<int, float>, gastos: array<int, float>}
     */
    private function fluxoPorMes(CarbonInterface $hoje, int $meses): array
    {
        $fluxo = ['labels' => [], 'ganhos' => [], 'gastos' => []];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = $hoje->copy()->startOfMonth()->subMonths($i);
            ['entradas' => $ganhos, 'saidas' => $gastos] = $this->transactions->totaisDoMes($mes);

            $fluxo['labels'][] = $this->rotulo($mes, false);
            $fluxo['ganhos'][] = (float) $ganhos;
            $fluxo['gastos'][] = (float) $gastos;
        }

        // sem nenhum lançamento nos meses, o gráfico fica no estado vazio
        return array_sum($fluxo['ganhos']) + array_sum($fluxo['gastos']) > 0 ? $fluxo : ['labels' => [], 'ganhos' => [], 'gastos' => []];
    }

    /**
     * Gastos pagos do mês por categoria, do maior ao menor. Passando de 7, o resto vira "Demais categorias".
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function gastosPorCategoria(CarbonInterface $mes): array
    {
        $totais = Transaction::query()->expense()->paid()->inMonth($mes)
            ->selectRaw('finance_category_id, SUM(amount) as total')
            ->groupBy('finance_category_id')->get()
            ->map(fn (Transaction $t) => ['id' => $t->finance_category_id, 'total' => $this->parse($t->total)])
            ->sortByDesc(fn (array $c) => $c['total']->toFloat())->values();

        $nomes = FinanceCategory::query()->whereIn('id', $totais->pluck('id'))->pluck('name', 'id');
        $itens = $totais->map(fn (array $c) => ['label' => $nomes[$c['id']], 'value' => $c['total']->toFloat()]);

        if ($itens->count() <= 7) {
            return $itens->all();
        }

        $resto = $totais->slice(7)->reduce(fn (BigDecimal $soma, array $c) => $soma->plus($c['total']), BigDecimal::zero());

        return $itens->take(7)->push(['label' => 'Demais categorias', 'value' => $resto->toFloat()])->all();
    }

    /** Abreviações fixas em pt-BR (não dependem do idioma configurado). */
    private function rotulo(CarbonInterface $mes, bool $comAno): string
    {
        $abreviacao = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'][$mes->month - 1];

        return $comAno ? $abreviacao.'/'.$mes->format('y') : $abreviacao;
    }

    private function parse(string|int|float|null $valor): BigDecimal
    {
        return BigDecimal::of((string) ($valor ?? 0))->toScale(2, RoundingMode::HalfUp);
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
