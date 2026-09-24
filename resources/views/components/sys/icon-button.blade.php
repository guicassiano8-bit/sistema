{{--
  Botão só com ícone — label é OBRIGATÓRIO (vira aria-label + tooltip)

  <x-sys.icon-button icon="more" label="Opções da missão" />
  <x-sys.icon-button icon="x" label="Fechar" data-modal-close />
  <x-sys.icon-button icon="chevron-left" label="Dia anterior" variant="outline" />
  <x-sys.icon-button icon="pencil" label="Editar" href="{{ route('missoes.edit', $m) }}" />

  variant: ghost | outline | primary      size: md (44px) | lg (56px)
  pressed: true|false → aria-pressed (para toggles, ex.: filtro ativo)
--}}
@props([
    'icon',
    'label',
    'variant' => 'ghost',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'pressed' => null,
])

@php
$variants = [
    'ghost'   => '[--btn-bg:transparent] [--btn-border:transparent] [--btn-border-hover:rgba(96,165,250,0.35)] [--btn-press:#16213A] text-ink-soft hover:text-ink',
    'outline' => '[--btn-bg:#0B1220] [--btn-border:rgba(96,165,250,0.35)] [--btn-border-hover:#60A5FA] [--btn-press:#16213A] text-sys-300',
    'primary' => '[--btn-bg:#2563EB] [--btn-border:#3B82F6] [--btn-border-hover:#93C5FD] [--btn-press:#1D4ED8] text-white',
];
$sizes = ['md' => 'size-tap [--ch:6px]', 'lg' => 'size-tap-lg [--ch:10px]'];
$classes = [
    'sys-btn chamfer chamfer-all shrink-0 !gap-0',
    $pressed === true
        ? '[--btn-bg:rgba(56,189,248,0.14)] [--btn-border:#38BDF8] [--btn-press:#16213A] text-sys-glow'
        : ($variants[$variant] ?? $variants['ghost']),
    $sizes[$size] ?? $sizes['md'],
];
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}"
            @if (! is_null($pressed)) aria-pressed="{{ $pressed ? 'true' : 'false' }}" @endif
            {{ $attributes->class($classes) }}>
@endif
        <x-sys.icon :name="$icon" :size="$size === 'lg' ? 'size-6' : 'size-5'" />
@if ($href)
    </a>
@else
    </button>
@endif
