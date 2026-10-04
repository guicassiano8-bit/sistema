<?php

namespace App\Services;

use App\Enums\ShoppingCategory;
use App\Enums\ShoppingStatus;
use App\Models\ShoppingItem;
use App\Models\Task;
use App\Models\User;
use App\Support\Jogador;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShoppingService
{
    /** Missão avulsa criada para o dia em que há itens a comprar. */
    public const MISSION_TITLE = 'Fazer Compras';

    public const MISSION_XP = 10;

    public function __construct(private TasksService $tasks) {}

    /**
     * Pendentes ordenados por raridade (urgente primeiro) e comprados do mais recente ao mais antigo.
     *
     * @return array{pendentes: Collection<int, ShoppingItem>, comprados: Collection<int, ShoppingItem>}
     */
    public function listar(): array
    {
        $ordem = array_flip(array_map(fn (ShoppingCategory $c) => $c->value, ShoppingCategory::ordered()));

        return [
            'pendentes' => ShoppingItem::query()->pending()->orderBy('name')->get()
                ->sortBy(fn (ShoppingItem $item) => $ordem[$item->category->value])->values(),
            'comprados' => ShoppingItem::query()->where('status', ShoppingStatus::Purchased)
                ->orderByDesc('purchased_at')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): ShoppingItem
    {
        return DB::transaction(function () use ($dados) {
            $item = ShoppingItem::create($dados);
            $this->sincronizarMissao($item);

            return $item;
        });
    }

    /**
     * Se o item saiu de uma missão (data limpa ou trocada) e sobraram só itens comprados,
     * a missão antiga é concluída; o resultado dessa conclusão vem no retorno.
     *
     * @param  array<string, mixed>  $dados
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null
     */
    public function atualizar(ShoppingItem $item, array $dados, User $user): ?array
    {
        return DB::transaction(function () use ($item, $dados, $user) {
            $missaoAnteriorId = $item->task_id;

            $item->update($dados);

            return $this->sincronizarMissao($item, $missaoAnteriorId, $user);
        });
    }

    /**
     * Excluir o último item pendente de uma missão conclui a missão.
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null
     */
    public function excluir(ShoppingItem $item, User $user): ?array
    {
        return DB::transaction(function () use ($item, $user) {
            $item->delete();

            return $item->task_id === null ? null : $this->reavaliarMissao($item->task_id, $user);
        });
    }

    /**
     * Comprado ↔ pendente. O item em si não gera XP, ouro nem financeiro; mas, se ele
     * pertence à missão "Fazer Compras", a conclusão da missão acompanha os itens.
     *
     * @return array{item: ShoppingItem, resultado: array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null}
     */
    public function alternar(ShoppingItem $item, User $user): array
    {
        return DB::transaction(function () use ($item, $user) {
            $comprar = $item->status === ShoppingStatus::Pending;

            $item->update([
                'status' => $comprar ? ShoppingStatus::Purchased : ShoppingStatus::Pending,
                'purchased_at' => $comprar ? now() : null,
            ]);

            $missao = $item->task_id === null ? null : Task::query()->find($item->task_id);

            return [
                'item' => $item,
                'resultado' => $missao === null ? null : $this->tasks->sincronizarConclusaoComItens($missao, $user),
            ];
        });
    }

    /** @return int quantidade de itens removidos */
    public function limparComprados(): int
    {
        return DB::transaction(function () {
            $comprados = ShoppingItem::query()->where('status', ShoppingStatus::Purchased);
            $missoes = (clone $comprados)->whereNotNull('task_id')->distinct()->pluck('task_id');

            $removidos = $comprados->delete();
            $missoes->each(fn (int $id) => $this->removerMissaoSeVazia($id));

            return $removidos;
        });
    }

    /** Itens "lendários" ainda por comprar: alimenta o badge da navegação. */
    public function urgentesPendentes(): int
    {
        return ShoppingItem::query()->pending()->inCategory(ShoppingCategory::Urgent)->count();
    }

    /**
     * Mantém o vínculo item → missão "Fazer Compras" coerente com a data do item:
     * com data, usa (ou cria) a missão do dia; sem data ou com data trocada, solta
     * o vínculo e reavalia a missão antiga (apaga se vazia, conclui se só restam itens comprados).
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null
     */
    private function sincronizarMissao(ShoppingItem $item, ?int $missaoAnteriorId = null, ?User $user = null): ?array
    {
        $data = $item->scheduled_date;
        $atual = $item->task_id === null ? null : Task::query()->find($item->task_id);

        // mesma data e missão ainda existe (mesmo concluída): nada a fazer
        if ($data !== null && $atual?->scheduled_date->isSameDay($data)) {
            return null;
        }

        $missao = $data === null ? null : $this->missaoDoDia($data);
        $item->update(['task_id' => $missao?->id]);

        if ($missaoAnteriorId !== null && $user !== null && $missaoAnteriorId !== $missao?->id) {
            return $this->reavaliarMissao($missaoAnteriorId, $user);
        }

        return null;
    }

    /**
     * Depois que um item deixa a missão: sem itens, ela é apagada (se pendente);
     * com itens, a conclusão é recalculada (só comprados → conclui).
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null
     */
    private function reavaliarMissao(int $missaoId, User $user): ?array
    {
        $missao = Task::query()->find($missaoId);

        if ($missao === null) {
            return null;
        }

        if (! $missao->shoppingItems()->exists()) {
            $this->removerMissaoSeVazia($missaoId);

            return null;
        }

        return $this->tasks->sincronizarConclusaoComItens($missao, $user);
    }

    /** Uma única missão pendente por dia, compartilhada por todos os itens daquela data. */
    private function missaoDoDia(Carbon $data): Task
    {
        return Task::query()
            ->pending()
            ->whereNull('recurring_task_id')
            ->where('title', self::MISSION_TITLE)
            ->whereDate('scheduled_date', $data)
            ->first()
            ?? $this->tasks->criar([
                'titulo' => self::MISSION_TITLE,
                'xp' => self::MISSION_XP,
                'ouro' => 0,
                'data' => $data->toDateString(),
            ]);
    }

    /** Missão concluída fica: ela já valeu XP e faz parte do histórico. */
    private function removerMissaoSeVazia(int $missaoId): void
    {
        $missao = Task::query()->pending()->find($missaoId);

        if ($missao !== null && ! $missao->shoppingItems()->exists()) {
            $missao->delete();
        }
    }
}
