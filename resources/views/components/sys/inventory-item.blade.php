{{--
  Item do Inventário (lista de compras) — marcar como comprado = 1 toque

  <x-sys.inventory-item name="Remédio da farmácia" rarity="urgente" :qty="1"
      :bought="$item->comprado"
      :toggle-url="route('inventario.toggle', $item)"
      :edit-url="route('inventario.edit', $item)" />

  rarity (valor do enum ShoppingCategory, equivalente pt-BR ou nome visual):
    urgent | urgente | legendary          → LENDÁRIO    roxo + glow + ponto pulsante
    important | importante | rare         → RARO        azul
    daily | dia_a_dia | common            → COMUM       cinza
    not_important | nao_importante | junk → DESCARTÁVEL apagado
  Estado "comprado": riscado, 60% de opacidade, vai para o fim da lista (ordene no controller).
--}}
@props([
    'name',
    'rarity' => 'common',
    'qty' => null,
    'unit' => null,
    'note' => null,
    'bought' => false,
    'toggleUrl' => '#',
    'editUrl' => null,
])

@php
$alias = [
    'urgent' => 'legendary', 'important' => 'rare', 'daily' => 'common', 'not_important' => 'junk',
    'urgente' => 'legendary', 'importante' => 'rare', 'dia_a_dia' => 'common', 'nao_importante' => 'junk',
];
$r = $alias[$rarity] ?? $rarity;
$map = [
    'legendary' => ['tag' => 'Lendário',    'text' => 'text-rank-s-text', 'card' => '[--ch-border:#A855F7] [--ch-bg:#140F24] glow-s',                 'box' => '[--ch-border:#C084FC]'],
    'rare'      => ['tag' => 'Raro',        'text' => 'text-rarity-rare', 'card' => '[--ch-border:rgba(96,165,250,0.6)] [--ch-bg:#0B1220]',             'box' => '[--ch-border:#60A5FA]'],
    'common'    => ['tag' => 'Comum',       'text' => 'text-ink-soft',    'card' => '[--ch-border:rgba(148,163,184,0.35)] [--ch-bg:#0B1220]',           'box' => '[--ch-border:rgba(148,163,184,0.6)]'],
    'junk'      => ['tag' => 'Descartável', 'text' => 'text-ink-muted',   'card' => '[--ch-border:rgba(71,85,105,0.5)] [--ch-bg:rgba(11,18,32,0.6)]',   'box' => '[--ch-border:#475569]'],
];
$m = $map[$r] ?? $map['common'];
@endphp

<article data-inventory-item data-state="{{ $bought ? 'done' : 'pending' }}" data-rarity="{{ $r }}"
         {{ $attributes->class([
             'chamfer group flex items-center gap-1 py-1 pl-1 pr-2 [--ch:8px] transition-opacity duration-160',
             $m['card'],
             'data-[state=done]:opacity-60 data-[state=done]:[filter:none]',
         ]) }}>

    <form method="POST" action="{{ $toggleUrl }}" data-item-toggle class="shrink-0">
        @csrf @method('PATCH')
        <button type="submit" aria-pressed="{{ $bought ? 'true' : 'false' }}"
                aria-label="Comprado: {{ $name }}"
                class="flex size-tap items-center justify-center">
            <span class="chamfer chamfer-all flex size-6 items-center justify-center [--ch:4px] [--ch-bg:#111A2E] text-transparent {{ $m['box'] }}
                         group-data-[state=done]:[--ch-border:#2DD4BF] group-data-[state=done]:[--ch-bg:rgba(45,212,191,0.15)] group-data-[state=done]:text-success-text">
                <x-sys.icon name="check" size="size-4" :stroke="3" class="sys-check-icon" />
            </span>
        </button>
    </form>

    <div class="min-w-0 flex-1 py-1.5">
        <p class="flex items-center gap-2">
            <span class="truncate text-base text-ink transition-colors duration-160 group-data-[state=done]:text-ink-muted"><span class="sys-strike">{{ $name }}</span></span>
            @if ($qty)
                <span class="shrink-0 font-display text-sm font-semibold tabular text-ink-soft">×{{ $qty }}{{ $unit ? ' ' . $unit : '' }}</span>
            @endif
        </p>
        <p class="mt-0.5 flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 font-display font-semibold uppercase tracking-wider {{ $m['text'] }}">
                @if ($r === 'legendary')
                    <span class="sys-pulse size-1.5 bg-rank-s-text shadow-glow-s group-data-[state=done]:hidden" aria-hidden="true"></span>
                @else
                    <span class="size-1.5 rotate-45 bg-current" aria-hidden="true"></span>
                @endif
                {{ $m['tag'] }}
            </span>
            @if ($note)<span class="truncate text-ink-muted">· {{ $note }}</span>@endif
        </p>
    </div>

    @if ($editUrl)
        <x-sys.icon-button icon="pencil" :label="'Editar ' . $name" :href="$editUrl" class="!text-ink-muted" />
    @endif
</article>
