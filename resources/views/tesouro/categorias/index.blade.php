{{--
  CATEGORIAS · GET /tesouro/categorias (name: tesouro.categorias.index)
  Dados: $gastos e $ganhos Collection<FinanceCategory> (ativas primeiro, com transactions_count)
  Rotas: tesouro.categorias.store (POST, bag "categoria") · tesouro.categorias.edit
--}}
@php
$bag = $errors->categoria;
$secoes = ['Gastos' => $gastos, 'Ganhos' => $ganhos];
@endphp

<x-layouts.app title="Categorias">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar ao Tesouro" :href="route('tesouro.index')" />
        <x-sys.page-header label="Tesouro" title="Categorias" class="min-w-0 flex-1">
            <x-slot:actions>
                <x-sys.icon-button icon="plus" label="Nova categoria" variant="outline" data-modal-open="modal-categoria" />
            </x-slot:actions>
        </x-sys.page-header>
    </div>

    @foreach ($secoes as $titulo => $categorias)
        <section aria-labelledby="cat-{{ $titulo }}" class="flex flex-col gap-2">
            <h2 id="cat-{{ $titulo }}" class="sys-label">{{ $titulo }}</h2>
            <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
                @forelse ($categorias as $categoria)
                    <li>
                        <a href="{{ route('tesouro.categorias.edit', $categoria) }}" class="flex min-h-tap items-center gap-3 px-4 py-2 hover:bg-sys-400/5">
                            <span class="size-3 shrink-0 rounded-full" style="background: {{ $categoria->color ?? '#64748B' }}" aria-hidden="true"></span>
                            <span @class(['min-w-0 flex-1 truncate text-base', 'text-ink' => $categoria->is_active, 'text-ink-muted' => ! $categoria->is_active])>
                                {{ $categoria->name }}@unless ($categoria->is_active) <span class="text-xs"> · desativada</span>@endunless
                            </span>
                            <span class="text-xs tabular text-ink-muted">{{ $categoria->transactions_count }} {{ $categoria->transactions_count === 1 ? 'lançamento' : 'lançamentos' }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-ink-soft">Nenhuma categoria.</li>
                @endforelse
            </x-sys.window>
        </section>
    @endforeach

    <x-slot:modals>
        <x-sys.modal id="modal-categoria" label="Categorias" title="Nova categoria" :open="$bag->any()">
            <form id="form-categoria" method="POST" action="{{ route('tesouro.categorias.store') }}" data-sys-form class="flex flex-col gap-4">
                @csrf
                <x-sys.segmented label="Tipo" name="type" value="expense" :options="['expense' => 'Gasto', 'income' => 'Ganho']" />
                <x-sys.input name="name" label="Nome" placeholder="Ex.: Pets" required autofocus autocomplete="off" :error="$bag->first('name')" />
                <x-sys.input name="color" label="Cor" type="color" value="#64748B" :error="$bag->first('color')" />
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-categoria" icon="check">Cadastrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>
    </x-slot:modals>
</x-layouts.app>
