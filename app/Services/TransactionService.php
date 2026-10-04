<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TransactionService
{
    /**
     * Sem descrição, o lançamento leva o nome da categoria (o fluxo de 2 toques só pede o valor).
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Transaction
    {
        return Transaction::create($this->comDescricao($dados))->load('category');
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Transaction $lancamento, array $dados): Transaction
    {
        $lancamento->update($this->comDescricao($dados));

        return $lancamento->load('category');
    }

    public function excluir(Transaction $lancamento): void
    {
        $lancamento->delete();
    }

    /**
     * Lançamentos do mês agrupados por dia (mais recente primeiro) e os totais do mês inteiro.
     * O filtro só afeta a lista; os totais contam só o que já foi pago e ignoram o filtro.
     *
     * @return array{porDia: Collection<string, Collection<int, Transaction>>, totais: array{entradas: string, saidas: string}}
     */
    public function extrato(CarbonInterface $mes, string $filtro = 'todos'): array
    {
        $lista = Transaction::query()->inMonth($mes)->with('category');

        match ($filtro) {
            'ganhos' => $lista->income(),
            'gastos' => $lista->expense(),
            default => null,
        };

        $somas = Transaction::query()->inMonth($mes)->paid()
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        return [
            'porDia' => $lista->orderByDesc('date')->orderByDesc('id')->get()
                ->groupBy(fn (Transaction $t) => $t->date->format('Y-m-d')),
            'totais' => [
                'entradas' => $this->decimal($somas[TransactionType::Income->value] ?? 0),
                'saidas' => $this->decimal($somas[TransactionType::Expense->value] ?? 0),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function comDescricao(array $dados): array
    {
        if (blank($dados['description'] ?? null)) {
            $dados['description'] = FinanceCategory::query()->whereKey($dados['finance_category_id'])->value('name');
        }

        return $dados;
    }

    /** O SUM volta como float no SQLite; arredondar evita ruído nos centavos. */
    private function decimal(string|int|float $valor): string
    {
        return (string) BigDecimal::of((string) $valor)->toScale(2, RoundingMode::HalfUp);
    }
}
