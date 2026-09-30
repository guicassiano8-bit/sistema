{{--
  EDITAR RECOMPENSA
  GET /recompensas/{recompensa}/edit (name: recompensas.edit)
  Dados: $recompensa (Reward)
  Campos: name, description, cost, rank, is_repeatable (1|0), is_active
--}}
@php
use App\Enums\TaskRank;

$val = fn ($campo, $atual) => old($campo, $atual);
@endphp

<x-layouts.app title="Editar recompensa">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar para a Loja" :href="route('recompensas.index')" />
        <x-sys.page-header label="Editar" :title="$recompensa->name" class="min-w-0 flex-1" />
    </div>

    <form id="form-recompensa" method="POST" action="{{ route('recompensas.update', $recompensa) }}" data-sys-form
          class="flex flex-col gap-5 lg:max-w-2xl">
        @csrf @method('PUT')

        <x-sys.window label="Recompensa">
            <div class="flex flex-col gap-4">
                <x-sys.input name="name" label="Recompensa" :value="$val('name', $recompensa->name)" required autofocus maxlength="255" />
                <x-sys.input name="description" label="Descrição" :value="$val('description', $recompensa->description)" hint="Opcional" />
                <x-sys.input name="cost" label="Custo" type="number" min="1" step="10" suffix="OURO" inputmode="numeric" :value="$val('cost', $recompensa->cost)" required />
                <x-sys.segmented label="Resgate" name="is_repeatable" :value="$recompensa->is_repeatable ? '1' : '0'" :options="['1' => 'Repetível', '0' => 'Única vez']" />
                <x-sys.segmented label="Peso (rank)" name="rank" :value="$recompensa->rank->value" :options="array_combine(TaskRank::values(), TaskRank::values())" />
                <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                    <input type="checkbox" name="is_active" value="1" class="size-5 accent-sys-500" @checked(old('is_active', $recompensa->is_active))>
                    Ativa na Loja
                </label>
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-2xl">
        <form method="POST" action="{{ route('recompensas.destroy', $recompensa) }}" data-confirm="Excluir a recompensa “{{ $recompensa->name }}”? Se já foi resgatada, ela só será desativada.">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir recompensa" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-recompensa" size="lg" icon="check" class="flex-1 glow">Salvar recompensa</x-sys.button>
    </div>
</x-layouts.app>
