<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinanceCategoryStoreRequest;
use App\Http\Requests\FinanceCategoryUpdateRequest;
use App\Models\FinanceCategory;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FinanceCategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index(): View
    {
        return view('tesouro.categorias.index', $this->categories->listar());
    }

    public function store(FinanceCategoryStoreRequest $request): RedirectResponse
    {
        $categoria = $this->categories->criar($request->validated());

        return redirect()->route('tesouro.categorias.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Categoria cadastrada', 'message' => $categoria->name]);
    }

    public function edit(FinanceCategory $categoria): View
    {
        return view('tesouro.categorias.edit', [
            'categoria' => $categoria->loadCount('transactions'),
        ]);
    }

    public function update(FinanceCategoryUpdateRequest $request, FinanceCategory $categoria): RedirectResponse
    {
        $this->categories->atualizar($categoria, $request->validated());

        return redirect()->route('tesouro.categorias.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Categoria atualizada', 'message' => $categoria->name]);
    }

    public function destroy(FinanceCategory $categoria): RedirectResponse
    {
        $apagada = $this->categories->excluir($categoria);

        return redirect()->route('tesouro.categorias.index')
            ->with('sys_toast', ['type' => 'info', 'title' => $apagada ? 'Categoria excluída' : 'Categoria desativada', 'message' => $categoria->name]);
    }
}
