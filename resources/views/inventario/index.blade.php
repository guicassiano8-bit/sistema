{{--
  INVENTÁRIO (lista de compras) · GET /inventario (name: inventario.index)
  Hierarquia: 1. adicionar (fixo no topo) · 2. lendários · 3. demais por raridade · 4. adquiridos

  Dados:
    $pendentes  Collection<ShoppingItem> não comprados, já ordenados por raridade
    $comprados  Collection<ShoppingItem> comprados (mais recentes primeiro)
  category: urgent | important | daily | not_important (enum ShoppingCategory)
  Rotas: inventario.store (POST name, category) · inventario.toggle · inventario.edit · inventario.limpar (DELETE comprados)
--}}
@php
$raridades = [
    'urgent'        => ['rotulo' => 'Lendário',    'curto' => 'Lendário', 'cor' => 'text-rank-s-text', 'ponto' => 'bg-rank-s-text shadow-glow-s'],
    'important'     => ['rotulo' => 'Raro',        'curto' => 'Raro',     'cor' => 'text-rarity-rare', 'ponto' => 'bg-rarity-rare'],
    'daily'         => ['rotulo' => 'Comum',       'curto' => 'Comum',    'cor' => 'text-ink-soft',    'ponto' => 'bg-rarity-common'],
    'not_important' => ['rotulo' => 'Descartável', 'curto' => 'Descart.', 'cor' => 'text-ink-muted',   'ponto' => 'bg-rarity-junk'],
];
$categoriaAtual = old('category', session('ultima_categoria', 'daily'));
@endphp

<x-layouts.app title="Inventário">
    <x-sys.page-header label="Lista de compras" title="Inventário">
        <x-slot:actions>
            <span class="font-display text-sm font-semibold tabular text-ink-soft">{{ $pendentes->count() }} {{ $pendentes->count() === 1 ? 'item' : 'itens' }}</span>
        </x-slot:actions>
    </x-sys.page-header>

    {{-- 1 · ADICIONAR — fixo logo abaixo do HUD. Digitar + Enter = adicionado. --}}
    <div class="sticky top-[calc(4.75rem+env(safe-area-inset-top))] z-20 -mx-gutter md:-mx-6 md:px-6 lg:top-0 lg:-mx-8 lg:px-8 bg-void/[0.97] px-gutter pb-2 pt-1">
        <form method="POST" action="{{ route('inventario.store') }}" data-sys-form
              class="chamfer sys-window-raised flex flex-col gap-2 p-2 [--ch:10px] [--ch-border:rgba(96,165,250,0.4)]">
            @csrf
            <div class="flex items-center gap-2">
                <label for="f-novo-item" class="sr-only">Novo item</label>
                <input id="f-novo-item" name="name" type="text" required autocomplete="off" enterkeyhint="done"
                       @if (request('acao') === 'item') autofocus @endif
                       placeholder="Adicionar item…" value="{{ old('name') }}"
                       class="min-h-tap min-w-0 flex-1 rounded-sm border-0 bg-surface px-3 text-base text-ink outline-none ring-1 ring-line placeholder:text-ink-faint focus:ring-2 focus:ring-sys-glow">
                <x-sys.icon-button icon="plus" label="Adicionar ao inventário" type="submit" variant="primary" />
            </div>
            <fieldset>
                <legend class="sr-only">Raridade</legend>
                <div class="grid grid-cols-4 gap-1">
                    @foreach ($raridades as $valor => $r)
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="{{ $valor }}" class="peer sr-only" @checked($categoriaAtual === $valor)>
                            <span class="flex min-h-tap items-center justify-center gap-1.5 rounded-sm border border-transparent px-1 font-display text-2xs font-semibold uppercase tracking-wider {{ $r['cor'] }}
                                         peer-checked:border-line-strong peer-checked:bg-surface-hover
                                         peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-sys-glow">
                                <span class="size-1.5 rotate-45 {{ $r['ponto'] }}" aria-hidden="true"></span>{{ $r['curto'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @if ($errors->inventario->has('name'))<p class="px-1 text-sm text-danger-text">{{ $errors->inventario->first('name') }}</p>@endif
        </form>
    </div>

    {{-- 2–3 · LISTAS POR RARIDADE --}}
    @if ($pendentes->isEmpty())
        <p class="py-8 text-center text-sm text-ink-soft">Inventário vazio. Nada para comprar.</p>
    @endif

    @foreach ($raridades as $valor => $r)
        @php $lista = $pendentes->filter(fn ($item) => $item->category->value === $valor); @endphp
        @if ($lista->isNotEmpty())
            <section aria-labelledby="inv-{{ $valor }}" class="flex flex-col gap-2">
                <h2 id="inv-{{ $valor }}" class="flex items-center gap-2 font-display text-xs font-semibold uppercase tracking-[0.14em] {{ $r['cor'] }}">
                    <span class="size-1.5 rotate-45 {{ $r['ponto'] }}" aria-hidden="true"></span>
                    {{ $r['rotulo'] }} <span class="text-ink-muted">· {{ $lista->count() }}</span>
                </h2>
                @foreach ($lista as $item)
                    <x-sys.inventory-item :name="$item->name" :rarity="$item->category->value" :qty="$item->quantity" :unit="$item->unit"
                                          :note="collect([$item->scheduled_date?->format('d/m'), $item->notes])->filter()->implode(' · ')" :bought="false"
                                          :toggle-url="route('inventario.toggle', $item)" :edit-url="route('inventario.edit', $item)" />
                @endforeach
            </section>
        @endif
    @endforeach

    {{-- 4 · ADQUIRIDOS (recolhido) --}}
    @if ($comprados->isNotEmpty())
        <details class="group/adq">
            <summary class="flex min-h-tap cursor-pointer list-none items-center gap-2 font-display text-xs font-semibold uppercase tracking-[0.14em] text-ink-soft [&::-webkit-details-marker]:hidden">
                <x-sys.icon name="chevron-right" size="size-4" class="transition-transform duration-160 group-open/adq:rotate-90" />
                Adquiridos <span class="text-ink-muted">· {{ $comprados->count() }}</span>
            </summary>
            <div class="mt-2 flex flex-col gap-2">
                @foreach ($comprados as $item)
                    <x-sys.inventory-item :name="$item->name" :rarity="$item->category->value" :qty="$item->quantity" :unit="$item->unit"
                                          :bought="true" :toggle-url="route('inventario.toggle', $item)" />
                @endforeach
                <form method="POST" action="{{ route('inventario.limpar') }}" data-confirm="Remover os {{ $comprados->count() }} itens adquiridos da lista?" data-confirm-ok="Remover" class="self-end">
                    @csrf @method('DELETE')
                    <x-sys.button variant="ghost" type="submit" icon="trash">Limpar adquiridos</x-sys.button>
                </form>
            </div>
        </details>
    @endif
</x-layouts.app>
