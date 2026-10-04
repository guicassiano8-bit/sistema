{{--
  EDITAR CATEGORIA · GET /tesouro/categorias/{categoria}/edit (name: tesouro.categorias.edit) · PUT tesouro.categorias.update · DELETE tesouro.categorias.destroy
  Dados: $categoria (FinanceCategory com transactions_count). O tipo não muda.
--}}
@php $erros = $errors->categoria; @endphp

<x-layouts.app title="Editar categoria">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar às categorias" :href="route('tesouro.categorias.index')" />
        <x-sys.page-header :label="$categoria->type->label()" :title="$categoria->name" class="min-w-0 flex-1" />
    </div>

    <form id="form-categoria" method="POST" action="{{ route('tesouro.categorias.update', $categoria) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Categoria">
            <div class="flex flex-col gap-4">
                <x-sys.input name="name" label="Nome" :value="$categoria->name" required maxlength="255" :error="$erros->first('name')" />
                <x-sys.input name="color" label="Cor" type="color" :value="$categoria->color ?? '#64748B'" :error="$erros->first('color')" />
                <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                    <input type="checkbox" name="is_active" value="1" class="size-5 accent-sys-500" @checked(old('is_active', $categoria->is_active))>
                    Categoria ativa (aparece nos lançamentos)
                </label>
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('tesouro.categorias.destroy', $categoria) }}"
              data-confirm="{{ $categoria->transactions_count > 0 ? 'Esta categoria já tem lançamentos. Ela será apenas desativada.' : 'Excluir a categoria '.$categoria->name.'?' }}">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir categoria" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-categoria" size="lg" icon="check" class="flex-1">Salvar categoria</x-sys.button>
    </div>
</x-layouts.app>
