<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeEntryStoreRequest;
use App\Models\IncomeEntry;
use App\Services\InvestmentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;

/** Rendimentos dos investimentos (dividendos, juros): o "ganho passivo" do Tesouro. */
class IncomeEntryController extends Controller
{
    public function __construct(private InvestmentService $investments) {}

    public function store(IncomeEntryStoreRequest $request): RedirectResponse
    {
        $rendimento = $this->investments->registrarRendimento($request->validated());

        return back()->with('sys_toast', [
            'type' => 'success',
            'title' => 'Rendimento registrado',
            'message' => $rendimento->asset->name,
            'value' => '+'.Money::format($rendimento->amount),
        ]);
    }

    public function destroy(IncomeEntry $rendimento): RedirectResponse
    {
        $rendimento->delete();

        return back()->with('sys_toast', ['type' => 'info', 'title' => 'Rendimento excluído']);
    }
}
