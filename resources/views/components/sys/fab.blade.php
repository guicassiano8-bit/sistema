{{--
  FAB — ação principal da tela, 1 toque. Fica acima da bottom nav; some no desktop
  (lá a mesma ação vira botão no cabeçalho da página).

  Abre um modal:     <x-sys.fab label="Nova missão" modal="modal-missao" />
  Vai para rota:     <x-sys.fab label="Novo item" :href="route('inventario.create')" />
  Variante de gasto: <x-sys.fab label="Lançar gasto" icon="minus" variant="danger" modal="modal-gasto" />

  Por tela: Status/Missões → Nova missão · Inventário → Novo item · Tesouro → Lançar gasto · Loja → (sem FAB)
--}}
@props([
    'label',
    'icon' => 'plus',
    'modal' => null,
    'href' => null,
    'variant' => 'primary',
    'extended' => false,
])

@php
$variants = [
    'primary' => '[--btn-bg:#2563EB] [--btn-border:#93C5FD] [--btn-border-hover:#FFFFFF] [--btn-press:#1D4ED8] text-white glow',
    'danger'  => '[--btn-bg:#BE123C] [--btn-border:#FB7185] [--btn-border-hover:#FFFFFF] [--btn-press:#9F1239] text-white drop-shadow-[0_0_8px_rgba(244,63,94,0.45)]',
];
$classes = [
    'sys-btn chamfer chamfer-all fixed right-4 z-45 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] md:bottom-6 md:right-6 lg:hidden [--ch:12px]',
    $variants[$variant] ?? $variants['primary'],
    'h-tap-lg px-5' => $extended,
    'size-tap-lg !gap-0' => ! $extended,
];
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->class($classes) }}>
@else
    <button type="button" aria-label="{{ $label }}" @if ($modal) data-modal-open="{{ $modal }}" aria-haspopup="dialog" @endif {{ $attributes->class($classes) }}>
@endif
        <x-sys.icon :name="$icon" size="size-6" :stroke="2.5" />
        @if ($extended)<span>{{ $label }}</span>@endif
@if ($href)
    </a>
@else
    </button>
@endif
