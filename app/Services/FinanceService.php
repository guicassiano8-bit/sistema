<?php

namespace App\Services;

use App\Enums\FinanceTab;
use App\Enums\Indexer;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\FinanceCategory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinanceService
{
    public function __construct(
        private TransactionService $transactions,
        private InvestmentService $investments,
    ) {}

    /** Cai em Resumo quando ?aba= está ausente ou é desconhecida, em vez de dar erro. */
    public function abaDaRequisicao(?string $aba): FinanceTab
    {
        return FinanceTab::tryFrom((string) $aba) ?? FinanceTab::Summary;
    }

    /**
     * Dados que o index e os modais precisam em qualquer aba, mais valores vazios
     * para as abas. Cada parte seguinte do módulo troca o vazio pelo dado real.
     *
     * @return array<string, mixed>
     */
    public function dadosDaTela(FinanceTab $aba, ?string $mes = null, ?string $filtro = null): array
    {
        $mes = $this->mesEmFoco($mes);
        $filtro = in_array($filtro, ['todos', 'ganhos', 'gastos'], true) ? $filtro : 'todos';

        return [
            'aba' => $aba->value,
            'categorias' => $this->categoriasParaSelect(),
            'investimentos' => Asset::query()->active()->with('assetType')->orderBy('name')->get(),
            'contas' => Account::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'indexadores' => Indexer::options(),

            // resumo
            'resumo' => ['patrimonio' => 0, 'delta_pct' => 0, 'ganhos_mes' => 0, 'gastos_mes' => 0, 'passivo_mes' => 0],
            'ultimos' => collect(),

            // extrato (Parte 2)
            'mes' => $mes,
            'filtro' => $filtro,
            ...($aba === FinanceTab::Statement
                ? $this->transactions->extrato($mes, $filtro)
                : ['porDia' => collect(), 'totais' => ['entradas' => 0, 'saidas' => 0]]),

            // ativos (Parte 4)
            'posicoes' => $aba === FinanceTab::Assets ? $this->investments->carteira() : collect(),

            // relatórios
            'serie12m' => [],
            'fluxo6m' => ['labels' => [], 'ganhos' => [], 'gastos' => []],
            'porCategoria' => [],
        ];
    }

    /**
     * Categorias ativas no formato dos modais: ['gasto' => [id => nome], 'ganho' => [id => nome]].
     *
     * @return array{gasto: array<int, string>, ganho: array<int, string>}
     */
    public function categoriasParaSelect(): array
    {
        /** @var Collection<int, FinanceCategory> $categorias */
        $categorias = FinanceCategory::query()->active()->orderBy('name')->get();

        $nomes = fn (TransactionType $tipo): array => $categorias
            ->where('type', $tipo)->pluck('name', 'id')->all();

        return [
            'gasto' => $nomes(TransactionType::Expense),
            'ganho' => $nomes(TransactionType::Income),
        ];
    }

    /** ?mes=2026-09 → 1º dia daquele mês; inválido ou ausente → mês atual. */
    private function mesEmFoco(?string $mes): Carbon
    {
        if ($mes !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return Carbon::createFromFormat('!Y-m', $mes);
        }

        return today()->startOfMonth();
    }
}
