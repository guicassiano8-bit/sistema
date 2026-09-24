{{--
  Stat compacto (atributo)
  <x-sys.stat label="Gastos" icon="trending-down" tone="danger"><x-sys.money :value="-3180" signed :decimals="0" /></x-sys.stat>
  tone: default | gain | danger | gold | sys — pinta o ícone e o rótulo; o valor fica no slot.
--}}
@props(['label', 'icon' => null, 'tone' => 'default', 'hint' => null])

@php
$tones = [
    'default' => 'text-sys-400',
    'sys'     => 'text-sys-glow',
    'gain'    => 'text-gain-text',
    'danger'  => 'text-danger-text',
    'gold'    => 'text-gold',
];
@endphp

<div {{ $attributes->class(['chamfer sys-window flex min-w-0 flex-col gap-1 p-3 [--ch:8px]']) }}>
    <p class="sys-label flex items-center gap-1.5 {{ $tones[$tone] ?? $tones['default'] }}">
        @if ($icon)<x-sys.icon :name="$icon" size="size-3.5" />@endif {{ $label }}
    </p>
    <p class="truncate font-display text-lg font-semibold text-ink">{{ $slot }}</p>
    @if ($hint)<p class="truncate text-xs text-ink-muted">{{ $hint }}</p>@endif
</div>
