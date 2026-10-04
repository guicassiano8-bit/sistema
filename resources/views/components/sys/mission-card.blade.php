{{--
  Card de missão — marcar = 1 toque; transferir para amanhã = 2 toques (⋮ → Amanhã)

  <x-sys.mission-card
      title="Estudar Laravel — 1h"
      :xp="100" :gold="20" time="19:00" rank="C"
      recurring="Diária"
      :done="$m->is_done" :overdue="$m->is_overdue"
      :toggle-url="route('missoes.toggle', $m)"
      :transfer-url="route('missoes.transferir', $m)"
      :edit-url="route('missoes.edit', $m)"
      :delete-url="route('missoes.destroy', $m)" />

  Estados: pendente · concluída (riscada, check teal) · atrasada (borda vermelha + tag)
  Rotas esperadas:  PATCH toggle  ·  PATCH transferir (campo "data" Y-m-d)  ·  DELETE destroy
  Sem JS o form funciona normal; com JS (sistema.js) vira fetch + toast + XP animado.
  Slot opcional <x-slot:menu> substitui os itens do menu ⋮.
  Slot opcional <x-slot:items> (com o prop items-label) mostra uma lista recolhível abaixo do card.
--}}
@props([
    'title',
    'xp' => 0,
    'gold' => null,
    'time' => null,
    'rank' => null,
    'recurring' => null,
    'note' => null,
    'done' => false,
    'overdue' => false,
    'toggleUrl' => '#',
    'transferUrl' => null,
    'editUrl' => null,
    'deleteUrl' => null,
    'itemsLabel' => 'Itens',
])

@php
$tomorrow = now()->addDay()->format('Y-m-d');
@endphp

{{-- Estado visual 100% guiado por data-state → o JS só troca o atributo --}}
<article data-mission data-state="{{ $done ? 'done' : 'pending' }}"
         @if ($overdue) data-overdue @endif
         data-xp="{{ $xp }}" data-gold="{{ $gold ?? 0 }}" data-title="{{ $title }}"
         {{ $attributes->class([
             'chamfer group flex flex-wrap items-center gap-1 py-1 pl-1 pr-0 transition-opacity duration-160 has-[details[open]]:z-20',
             '[--ch:8px] [--ch-bg:#0B1220]',
             'data-[state=pending]:data-[overdue]:[--ch-border:rgba(244,63,94,0.55)]',
             'data-[state=done]:[--ch-border:rgba(96,165,250,0.18)] data-[state=done]:opacity-80',
         ]) }}>

    {{-- 1 · CHECK (alvo de 44px, visual de 24px) --}}
    <form method="POST" action="{{ $toggleUrl }}" data-mission-toggle class="shrink-0">
        @csrf
        @method('PATCH')
        <button type="submit" aria-pressed="{{ $done ? 'true' : 'false' }}"
                aria-label="Concluir missão: {{ $title }}"
                class="flex size-tap items-center justify-center">
            <span class="chamfer chamfer-all flex size-6 items-center justify-center [--ch:4px] transition-transform duration-90 active:scale-95
                         [--ch-border:rgba(96,165,250,0.55)] [--ch-bg:#111A2E] text-transparent hover:[--ch-border:#60A5FA]
                         group-data-[state=done]:[--ch-border:#2DD4BF] group-data-[state=done]:[--ch-bg:rgba(45,212,191,0.15)] group-data-[state=done]:text-success-text">
                <x-sys.icon name="check" size="size-4" :stroke="3" class="sys-check-icon" />
            </span>
        </button>
    </form>

    {{-- 2 · CONTEÚDO --}}
    <div class="min-w-0 flex-1 py-1.5">
        <p class="truncate text-base text-ink transition-colors duration-160 group-data-[state=done]:text-ink-muted"><span class="sys-strike">{{ $title }}</span></p>

        @if ($recurring || $time || $overdue || $rank || $note)
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-ink-soft">
                @if ($overdue)
                    <span class="font-display font-semibold uppercase tracking-wider text-danger-text group-data-[state=done]:hidden">Atrasada</span>
                @endif
                @if ($rank)
                    <x-sys.rank-badge :rank="$rank" size="sm" class="-my-1 scale-[0.83] origin-left" />
                @endif
                @if ($recurring)
                    <span class="inline-flex items-center gap-1 text-sys-400">
                        <x-sys.icon name="repeat" size="size-3.5" /> {{ $recurring }}
                    </span>
                @endif
                @if ($time)
                    <span class="inline-flex items-center gap-1 tabular"><x-sys.icon name="clock" size="size-3.5" /> {{ $time }}</span>
                @endif
                @if ($note)
                    <span class="truncate">{{ $note }}</span>
                @endif
            </p>
        @endif
    </div>

    {{-- 3 · RECOMPENSA --}}
    <div class="shrink-0 text-right font-display leading-tight">
        <p class="text-sm font-semibold tabular text-sys-glow group-data-[state=done]:text-ink-muted">+{{ $xp }}<span class="ml-0.5 text-2xs">XP</span></p>
        @if ($gold)
            <p class="text-2xs font-semibold tabular text-gold group-data-[state=done]:text-ink-muted">+{{ $gold }} OURO</p>
        @endif
    </div>

    {{-- 4 · MENU ⋮ --}}
    <details class="sys-menu relative shrink-0" data-close-outside>
        <summary aria-label="Opções: {{ $title }}" title="Opções"
                 class="flex size-tap cursor-pointer items-center justify-center text-ink-soft hover:text-ink">
            <x-sys.icon name="more" />
        </summary>
        <div class="sys-menu-panel chamfer sys-window-raised absolute right-1 top-full z-30 mt-1 w-56 p-1 shadow-elev [--ch:8px] [--ch-border:rgba(96,165,250,0.4)]">
            @isset($menu)
                {{ $menu }}
            @else
                @php $item = 'flex w-full min-h-tap items-center gap-3 px-3 text-left text-sm text-ink hover:bg-surface-hover focus-visible:bg-surface-hover'; @endphp
                @if ($transferUrl)
                    <div class="group-data-[state=done]:hidden">
                    <form method="POST" action="{{ $transferUrl }}" data-sys-form>
                        @csrf @method('PATCH')
                        <input type="hidden" name="data" value="{{ $tomorrow }}">
                        <button type="submit" class="{{ $item }}"><x-sys.icon name="arrow-right" size="size-4" class="text-sys-400" /> Transferir para amanhã</button>
                    </form>
                    <button type="button" class="{{ $item }}" data-modal-open="modal-transferir" data-transfer-url="{{ $transferUrl }}" data-transfer-title="{{ $title }}">
                        <x-sys.icon name="calendar" size="size-4" class="text-sys-400" /> Escolher outro dia…
                    </button>
                    </div>
                @endif
                @if ($editUrl)
                    <a href="{{ $editUrl }}" class="{{ $item }}"><x-sys.icon name="pencil" size="size-4" class="text-sys-400" /> Editar</a>
                @endif
                @if ($deleteUrl)
                    <form method="POST" action="{{ $deleteUrl }}" data-confirm="Excluir a missão “{{ $title }}”?">
                        @csrf @method('DELETE')
                        <button type="submit" class="{{ $item }} !text-danger-text"><x-sys.icon name="trash" size="size-4" /> Excluir</button>
                    </form>
                @endif
            @endisset
        </div>
    </details>

    {{-- 5 · ITENS (opcional) — lista recolhida em largura total, abaixo da linha da missão --}}
    @isset($items)
        <details class="group/itens basis-full pb-1 pl-1 pr-2">
            <summary class="flex min-h-tap cursor-pointer list-none items-center gap-2 pl-1 font-display text-xs font-semibold uppercase tracking-[0.14em] text-ink-soft [&::-webkit-details-marker]:hidden">
                <x-sys.icon name="chevron-right" size="size-4" class="transition-transform duration-160 group-open/itens:rotate-90" />
                {{ $itemsLabel }}
            </summary>
            <div class="mt-1 flex flex-col gap-2">
                {{ $items }}
            </div>
        </details>
    @endisset
</article>
