{{--
  Botão

  <x-sys.button>Aceitar missão</x-sys.button>
  <x-sys.button variant="secondary" icon="calendar">Transferir</x-sys.button>
  <x-sys.button variant="danger" icon="trash" type="submit">Excluir</x-sys.button>
  <x-sys.button variant="gold" icon="gift" :block="true">Trocar por 300 Ouro</x-sys.button>
  <x-sys.button href="{{ route('missoes.index') }}" variant="ghost" icon-right="arrow-right">Ver todas</x-sys.button>
  <x-sys.button :loading="true">Salvando</x-sys.button>

  variant: primary | secondary | ghost | danger | gold
  size: sm (36px, só desktop/denso) | md (44px) | lg (52px, CTA de formulário)
  href → vira <a>; disabled em <a> vira aria-disabled.
  data-loading é ligado automaticamente em forms com [data-sys-form] (ver sistema.js).
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
    'block' => false,
    'loading' => false,
    'disabled' => false,
])

@php
// cor de fundo / borda / borda-hover / pressed / texto
$variants = [
    'primary'   => '[--btn-bg:#2563EB] [--btn-border:#3B82F6] [--btn-border-hover:#93C5FD] [--btn-press:#1D4ED8] text-white hover:glow',
    'secondary' => '[--btn-bg:#0B1220] [--btn-border:rgba(96,165,250,0.5)] [--btn-border-hover:#60A5FA] [--btn-press:#16213A] text-sys-300',
    'ghost'     => '[--btn-bg:transparent] [--btn-border:transparent] [--btn-border-hover:rgba(96,165,250,0.35)] [--btn-press:#16213A] text-sys-400',
    'danger'    => '[--btn-bg:rgba(244,63,94,0.12)] [--btn-border:rgba(244,63,94,0.6)] [--btn-border-hover:#FB7185] [--btn-press:rgba(244,63,94,0.22)] text-danger-text',
    'gold'      => '[--btn-bg:rgba(251,191,36,0.12)] [--btn-border:rgba(251,191,36,0.6)] [--btn-border-hover:#FBBF24] [--btn-press:rgba(251,191,36,0.22)] text-gold',
];
$sizes = [
    'sm' => 'min-h-9 px-3 text-xs',
    'md' => 'min-h-tap px-4 text-sm',
    'lg' => 'min-h-[3.25rem] px-5 text-base',
];
$classes = [
    'sys-btn chamfer chamfer-all',
    $variants[$variant] ?? $variants['primary'],
    $sizes[$size] ?? $sizes['md'],
    'w-full' => $block,
];
$iconSize = $size === 'lg' ? 'size-5' : 'size-4';
@endphp

@if ($href)
    <a href="{{ $disabled ? '#' : $href }}"
       @if ($disabled) aria-disabled="true" tabindex="-1" @endif
       {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" @disabled($disabled)
            @if ($loading) data-loading aria-busy="true" @endif
            {{ $attributes->class($classes) }}>
@endif
        @if ($loading)<span class="sys-spinner" aria-hidden="true"></span>@endif
        @if ($icon)<x-sys.icon :name="$icon" :size="$iconSize" />@endif
        <span>{{ $slot }}</span>
        @if ($iconRight)<x-sys.icon :name="$iconRight" :size="$iconSize" />@endif
@if ($href)
    </a>
@else
    </button>
@endif
