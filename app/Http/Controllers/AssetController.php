<?php

namespace App\Http\Controllers;

use App\Enums\Indexer;
use App\Enums\InvestmentTransactionType;
use App\Http\Requests\AssetStoreRequest;
use App\Http\Requests\AssetUpdateRequest;
use App\Http\Requests\AssetValueRequest;
use App\Http\Requests\InvestmentMovementRequest;
use App\Models\Account;
use App\Models\Asset;
use App\Services\InvestmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Investimentos (CDB, FII): cadastro, valor atual e movimentos. Na interface, aba "Ativos" do Tesouro. */
class AssetController extends Controller
{
    public function __construct(private InvestmentService $investments) {}

    public function store(AssetStoreRequest $request): RedirectResponse
    {
        $asset = $this->investments->criar($request->validated());

        return redirect()->route('tesouro.index', ['aba' => 'ativos'])
            ->with('sys_toast', ['type' => 'success', 'title' => 'Investimento registrado', 'message' => $asset->name]);
    }

    public function edit(Asset $investimento): View
    {
        $investimento->load(['assetType', 'latestQuote', 'latestBalanceUpdate', 'transactions', 'incomeEntries']);
        $comCotas = $investimento->assetType->is_market_traded;

        return view('tesouro.investimentos.edit', [
            'investimento' => $investimento,
            'posicao' => $this->investments->posicao($investimento),
            'movimentos' => $investimento->transactions->sortByDesc(fn ($t) => $t->date->format('Y-m-d').str_pad((string) $t->id, 12, '0', STR_PAD_LEFT))->take(15),
            'rendimentos' => $investimento->incomeEntries->sortByDesc(fn ($e) => $e->payment_date->format('Y-m-d').str_pad((string) $e->id, 12, '0', STR_PAD_LEFT))->take(10),
            'tiposDeMovimento' => collect($comCotas
                ? [InvestmentTransactionType::Buy, InvestmentTransactionType::Sell]
                : [InvestmentTransactionType::Deposit, InvestmentTransactionType::Withdrawal])
                ->mapWithKeys(fn (InvestmentTransactionType $t) => [$t->value => $t->label()])->all(),
            'indexadores' => Indexer::options(),
            'contas' => Account::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function update(AssetUpdateRequest $request, Asset $investimento): RedirectResponse
    {
        $this->investments->atualizar($investimento, $request->validated());

        return redirect()->route('tesouro.index', ['aba' => 'ativos'])
            ->with('sys_toast', ['type' => 'success', 'title' => 'Investimento atualizado', 'message' => $investimento->name]);
    }

    public function destroy(Asset $investimento): RedirectResponse
    {
        $apagado = $this->investments->excluir($investimento);

        return redirect()->route('tesouro.index', ['aba' => 'ativos'])
            ->with('sys_toast', ['type' => 'info', 'title' => $apagado ? 'Investimento excluído' : 'Investimento encerrado', 'message' => $investimento->name]);
    }

    public function movimento(InvestmentMovementRequest $request, Asset $investimento): RedirectResponse
    {
        $movimento = $this->investments->registrarMovimento($investimento, $request->validated());

        return back()->with('sys_toast', ['type' => 'success', 'title' => $movimento->type->label().' registrado', 'message' => $investimento->name]);
    }

    public function valor(AssetValueRequest $request, Asset $investimento): RedirectResponse
    {
        $this->investments->atualizarValor($investimento, $request->validated());

        return back()->with('sys_toast', ['type' => 'success', 'title' => 'Valor atualizado', 'message' => $investimento->name]);
    }
}
