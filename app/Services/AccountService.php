<?php

namespace App\Services;

use App\Models\Account;
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;

class AccountService
{
    public function __construct(private AccountBalanceService $saldos) {}

    /**
     * Ativas primeiro (por nome), depois as desativadas. O total soma só as ativas.
     *
     * @return array{contas: Collection<int, array{conta: Account, saldo: BigDecimal}>, total: BigDecimal}
     */
    public function listar(): array
    {
        $contas = Account::query()->orderByDesc('is_active')->orderBy('name')->get()
            ->map(fn (Account $conta) => ['conta' => $conta, 'saldo' => $this->saldos->saldoDaConta($conta)]);

        return [
            'contas' => $contas,
            'total' => $contas->filter(fn (array $c) => $c['conta']->is_active)
                ->reduce(fn (BigDecimal $soma, array $c) => $soma->plus($c['saldo']), BigDecimal::zero()->toScale(2)),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Account
    {
        return Account::create([
            'name' => $dados['name'],
            'type' => $dados['type'],
            'institution' => $dados['institution'] ?? null,
            'initial_balance' => $dados['initial_balance'] ?? 0,
            'is_active' => true,
        ]);
    }

    /**
     * Checkbox desmarcado não é enviado: ausente significa desativada.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Account $conta, array $dados): Account
    {
        $conta->update([
            'name' => $dados['name'],
            'type' => $dados['type'],
            'institution' => $dados['institution'] ?? null,
            'initial_balance' => $dados['initial_balance'] ?? 0,
            'is_active' => (bool) ($dados['is_active'] ?? false),
        ]);

        return $conta;
    }

    /**
     * Conta com histórico não pode sumir (o saldo de outras telas dependeria dela): é só desativada.
     *
     * @return bool true se apagou, false se apenas desativou
     */
    public function excluir(Account $conta): bool
    {
        if ($this->temMovimentacoes($conta)) {
            $conta->update(['is_active' => false]);

            return false;
        }

        $conta->delete();

        return true;
    }

    public function temMovimentacoes(Account $conta): bool
    {
        return $conta->transactions()->exists()
            || $conta->recurringTransactions()->exists()
            || $conta->outgoingTransfers()->exists()
            || $conta->incomingTransfers()->exists()
            || $conta->investmentTransactions()->exists();
    }
}
