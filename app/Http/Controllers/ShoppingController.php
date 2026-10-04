<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShoppingItemStoreRequest;
use App\Http\Requests\ShoppingItemUpdateRequest;
use App\Models\ShoppingItem;
use App\Services\ShoppingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShoppingController extends Controller
{
    public function __construct(private ShoppingService $shopping) {}

    public function index(): View
    {
        return view('inventario.index', $this->shopping->listar());
    }

    public function store(ShoppingItemStoreRequest $request): RedirectResponse
    {
        $item = $this->shopping->criar($request->validated());

        // a próxima adição já abre na mesma raridade
        return redirect()->route('inventario.index')
            ->with('ultima_categoria', $item->category->value)
            ->with('sys_toast', ['type' => 'success', 'title' => 'Item adicionado', 'message' => $item->name]);
    }

    public function edit(ShoppingItem $item): View
    {
        return view('inventario.edit', ['item' => $item]);
    }

    public function update(ShoppingItemUpdateRequest $request, ShoppingItem $item): RedirectResponse
    {
        $missao = $this->shopping->atualizar($item, $request->validated(), $request->user());

        return redirect()->route('inventario.index')
            ->with('sys_toast', $this->toastDaMissao($missao) ?? ['type' => 'success', 'title' => 'Item atualizado', 'message' => $item->name]);
    }

    public function destroy(Request $request, ShoppingItem $item): RedirectResponse
    {
        $missao = $this->shopping->excluir($item, $request->user());

        return redirect()->route('inventario.index')
            ->with('sys_toast', $this->toastDaMissao($missao) ?? ['type' => 'info', 'title' => 'Item excluído', 'message' => $item->name]);
    }

    public function toggle(Request $request, ShoppingItem $item): JsonResponse|RedirectResponse
    {
        ['item' => $item, 'resultado' => $missao] = $this->shopping->alternar($item, $request->user());
        $done = $item->purchased_at !== null;

        // a missão "Fazer Compras" foi concluída/estornada junto com os itens
        $toastMissao = $this->toastDaMissao($missao);

        if ($request->expectsJson()) {
            return response()->json(['done' => $done] + ($missao === null ? [] : [
                'mission' => ['done' => $missao['done']],
                'player' => $missao['jogador']->toArray() + ['rank_changed' => $missao['rank_changed']],
                'leveled_up' => $missao['leveled_up'],
                'toast' => $toastMissao,
            ]));
        }

        return back()->with('sys_toast', $toastMissao ?? [
            'type' => 'success',
            'title' => $done ? 'Item adquirido' : 'Item de volta à lista',
            'message' => $item->name,
        ]);
    }

    /**
     * Toast da conclusão da missão "Fazer Compras"; nulo se a missão não foi concluída agora.
     *
     * @param  array{done: bool}|null  $missao
     * @return array<string, string>|null
     */
    private function toastDaMissao(?array $missao): ?array
    {
        if (! ($missao['done'] ?? false)) {
            return null;
        }

        return [
            'type' => 'success',
            'title' => 'Missão concluída',
            'message' => ShoppingService::MISSION_TITLE,
            'value' => '+'.ShoppingService::MISSION_XP.' XP',
        ];
    }

    public function limpar(): RedirectResponse
    {
        $removidos = $this->shopping->limparComprados();

        return redirect()->route('inventario.index')
            ->with('sys_toast', ['type' => 'info', 'title' => 'Adquiridos removidos', 'message' => $removidos.' '.($removidos === 1 ? 'item' : 'itens')]);
    }
}
