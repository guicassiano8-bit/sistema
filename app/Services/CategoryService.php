<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Categorias por tipo, ativas primeiro, com a contagem de lançamentos de cada uma.
     *
     * @return array{gastos: Collection<int, FinanceCategory>, ganhos: Collection<int, FinanceCategory>}
     */
    public function listar(): array
    {
        $todas = FinanceCategory::query()->withCount('transactions')
            ->orderByDesc('is_active')->orderBy('name')->get();

        return [
            'gastos' => $todas->where('type', TransactionType::Expense)->values(),
            'ganhos' => $todas->where('type', TransactionType::Income)->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): FinanceCategory
    {
        return FinanceCategory::create([
            'type' => $dados['type'],
            'name' => $dados['name'],
            'color' => $dados['color'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(FinanceCategory $categoria, array $dados): FinanceCategory
    {
        $categoria->update([
            'name' => $dados['name'],
            'color' => $dados['color'] ?? null,
            'is_active' => (bool) ($dados['is_active'] ?? false),
        ]);

        return $categoria;
    }

    /**
     * Categoria usada em lançamentos ou recorrências é só desativada: apagar quebraria o histórico.
     *
     * @return bool true se apagou, false se apenas desativou
     */
    public function excluir(FinanceCategory $categoria): bool
    {
        if ($categoria->transactions()->exists() || $categoria->recurringTransactions()->exists()) {
            $categoria->update(['is_active' => false]);

            return false;
        }

        $categoria->delete();

        return true;
    }
}
