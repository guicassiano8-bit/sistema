<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactions) {}

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $lancamento = $this->transactions->criar($request->validated());

        // volta para a aba de onde o modal foi aberto
        return back()->with('sys_toast', $this->toast($lancamento, 'registrado'));
    }

    public function edit(Transaction $lancamento): View
    {
        return view('tesouro.lancamentos.edit', [
            'lancamento' => $lancamento,
            'categorias' => FinanceCategory::query()->active()->ofType($lancamento->type)->orderBy('name')->pluck('name', 'id')->all(),
            'contas' => Account::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'formasDePagamento' => PaymentMethod::options(),
        ]);
    }

    public function update(TransactionUpdateRequest $request, Transaction $lancamento): RedirectResponse
    {
        $lancamento = $this->transactions->atualizar($lancamento, $request->validated());

        return $this->paraOExtrato($lancamento)->with('sys_toast', $this->toast($lancamento, 'atualizado'));
    }

    public function destroy(Transaction $lancamento): RedirectResponse
    {
        $this->transactions->excluir($lancamento);

        return $this->paraOExtrato($lancamento)->with('sys_toast', [
            'type' => 'info',
            'title' => $lancamento->type === TransactionType::Expense ? 'Gasto excluído' : 'Ganho excluído',
            'message' => $lancamento->description,
        ]);
    }

    private function paraOExtrato(Transaction $lancamento): RedirectResponse
    {
        return redirect()->route('tesouro.index', ['aba' => 'extrato', 'mes' => $lancamento->date->format('Y-m')]);
    }

    /**
     * @return array<string, string>
     */
    private function toast(Transaction $lancamento, string $acao): array
    {
        $gasto = $lancamento->type === TransactionType::Expense;

        return [
            'type' => 'success',
            'title' => ($gasto ? 'Gasto ' : 'Ganho ').$acao,
            'message' => $lancamento->description,
            'value' => ($gasto ? '−' : '+').Money::format($lancamento->amount),
        ];
    }
}
