<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Services\AccountBalanceService;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    public function index(): View
    {
        return view('tesouro.contas.index', $this->accounts->listar() + ['tipos' => $this->tipos()]);
    }

    public function store(AccountStoreRequest $request): RedirectResponse
    {
        $conta = $this->accounts->criar($request->validated());

        return redirect()->route('tesouro.contas.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Conta cadastrada', 'message' => $conta->name]);
    }

    public function edit(Account $conta, AccountBalanceService $saldos): View
    {
        return view('tesouro.contas.edit', [
            'conta' => $conta,
            'saldo' => $saldos->saldoDaConta($conta),
            'tipos' => $this->tipos(),
            'temMovimentacoes' => $this->accounts->temMovimentacoes($conta),
        ]);
    }

    public function update(AccountUpdateRequest $request, Account $conta): RedirectResponse
    {
        $this->accounts->atualizar($conta, $request->validated());

        return redirect()->route('tesouro.contas.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Conta atualizada', 'message' => $conta->name]);
    }

    public function destroy(Account $conta): RedirectResponse
    {
        $apagada = $this->accounts->excluir($conta);

        return redirect()->route('tesouro.contas.index')
            ->with('sys_toast', ['type' => 'info', 'title' => $apagada ? 'Conta excluída' : 'Conta desativada', 'message' => $conta->name]);
    }

    /**
     * @return array<string, string>
     */
    private function tipos(): array
    {
        return collect(AccountType::options())->only(AccountStoreRequest::typesAllowed())->all();
    }
}
