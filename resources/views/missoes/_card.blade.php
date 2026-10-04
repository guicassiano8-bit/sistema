{{--
  Converte um Model Task no <x-sys.mission-card>. Use: @include('missoes._card', ['m' => $missao])
  Ouro e dificuldade (rank) vêm da própria Task; rank é só visual.
  Missão "Fazer Compras": lista os itens do Inventário daquele dia (relação shoppingItems, sempre com eager load).
--}}
<x-sys.mission-card
    :title="$m->title"
    :xp="$m->points"
    :gold="$m->gold"
    :rank="$m->rank?->value"
    :time="$m->is_overdue ? $m->scheduled_date->translatedFormat('d/m') : ($m->start_time ? substr($m->start_time, 0, 5) : null)"
    :recurring="$m->recurringTask?->frequency?->label()"
    :done="$m->is_done"
    :overdue="$m->is_overdue"
    :toggle-url="route('missoes.toggle', $m)"
    :transfer-url="route('missoes.transferir', $m)"
    :edit-url="route('missoes.edit', $m)"
    :delete-url="route('missoes.destroy', $m)"
    :items-label="$m->shoppingItems->count().' '.($m->shoppingItems->count() === 1 ? 'item' : 'itens')">
    @if ($m->shoppingItems->isNotEmpty())
        <x-slot:items>
            @foreach ($m->shoppingItems->sortBy(fn ($item) => $item->category->sortOrder()) as $item)
                <x-sys.inventory-item :name="$item->name" :rarity="$item->category->value" :qty="$item->quantity" :unit="$item->unit"
                                      :note="$item->notes" :bought="$item->status === \App\Enums\ShoppingStatus::Purchased"
                                      :toggle-url="route('inventario.toggle', $item)" :edit-url="route('inventario.edit', $item)" />
            @endforeach
        </x-slot:items>
    @endif
</x-sys.mission-card>
