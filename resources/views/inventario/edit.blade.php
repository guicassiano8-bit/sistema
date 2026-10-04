{{-- EDITAR ITEM · GET /inventario/{item}/editar (name: inventario.edit) · PUT inventario.update · DELETE inventario.destroy --}}
<x-layouts.app title="Editar item">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar ao inventário" :href="route('inventario.index')" />
        <x-sys.page-header label="Inventário" :title="$item->name" class="min-w-0 flex-1" />
    </div>

    <form id="form-item" method="POST" action="{{ route('inventario.update', $item) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Item">
            <div class="flex flex-col gap-4">
                <x-sys.input name="name" label="Nome" :value="$item->name" :error="$errors->inventario->first('name')" required />
                <x-sys.select name="category" label="Raridade" :value="$item->category->value" :options="[
                    'urgent' => 'Lendário · Urgente', 'important' => 'Raro · Importante',
                    'daily' => 'Comum · Dia a dia', 'not_important' => 'Descartável · Não importante',
                ]" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="quantity" label="Quantidade" type="number" min="1" step="1" inputmode="numeric" :value="$item->quantity" :error="$errors->inventario->first('quantity')" />
                    <x-sys.input name="unit" label="Unidade" placeholder="un, kg, L" :value="$item->unit" :error="$errors->inventario->first('unit')" />
                </div>
                <x-sys.input name="notes" label="Observação" placeholder="Marca, loja…" :value="$item->notes" :error="$errors->inventario->first('notes')" />
                <x-sys.input name="scheduled_date" label="Data da compra (opcional)" type="date" :value="$item->scheduled_date?->format('Y-m-d')"
                             hint="Com data, o item entra na missão “Fazer Compras” desse dia." :error="$errors->inventario->first('scheduled_date')" />
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('inventario.destroy', $item) }}" data-confirm="Excluir “{{ $item->name }}” do inventário?">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir item" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-item" size="lg" icon="check" class="flex-1">Salvar</x-sys.button>
    </div>
</x-layouts.app>
