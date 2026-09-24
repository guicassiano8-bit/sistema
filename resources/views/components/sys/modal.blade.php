{{--
  Modal = <dialog> nativo (foco preso, Esc fecha, volta o foco para quem abriu)
  Mobile: sobe de baixo como "bottom sheet". ≥ 640px: janela centralizada.

  <x-sys.button data-modal-open="modal-gasto" icon="minus">Lançar gasto</x-sys.button>

  <x-sys.modal id="modal-gasto" label="Tesouro" title="Novo gasto">
      <form id="form-gasto" method="POST" action="{{ route('tesouro.store') }}" data-sys-form class="space-y-4">
          @csrf
          <x-sys.input name="valor" label="Valor" prefix="R$" inputmode="decimal" autofocus />
      </form>
      <x-slot:footer>
          <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
          <x-sys.button type="submit" form="form-gasto">Registrar</x-sys.button>
      </x-slot:footer>
  </x-sys.modal>

  size: sm (24rem, confirmações) | md (32rem, formulários) | lg (42rem)
  variant: default | danger (confirmar exclusão) | legendary
  :open="true" abre ao carregar (ex.: quando há erro de validação do form dele).
--}}
@props([
    'id',
    'title',
    'label' => 'Sistema',
    'size' => 'md',
    'variant' => 'default',
    'open' => false,
])

@php
$widths = ['sm' => '24rem', 'md' => '32rem', 'lg' => '42rem'];
$borders = [
    'default'   => '[--ch-border:rgba(96,165,250,0.55)] glow',
    'danger'    => '[--ch-border:rgba(244,63,94,0.6)]',
    'legendary' => '[--ch-border:#A855F7] glow-s',
];
@endphp

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title" data-sys-modal
        @if ($open) data-open-on-load @endif
        style="--dialog-w: {{ $widths[$size] ?? $widths['md'] }}"
        {{ $attributes->class(['sys-dialog']) }}>
    <div @class([
        'sys-dialog-panel chamfer sys-window-raised scanlines flex max-h-[92dvh] flex-col [--ch:14px]',
        $borders[$variant] ?? $borders['default'],
    ])>
        <header class="flex items-start justify-between gap-3 border-b border-line-subtle py-3 pl-5 pr-2">
            <div class="min-w-0 pt-1">
                <p @class(['sys-label', 'text-danger-text' => $variant === 'danger', 'text-rank-s-text' => $variant === 'legendary'])>[ {{ $label }} ]</p>
                <h2 id="{{ $id }}-title" class="font-display text-xl font-semibold text-ink">{{ $title }}</h2>
            </div>
            <x-sys.icon-button icon="x" label="Fechar" data-modal-close />
        </header>

        <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="flex items-center justify-end gap-2 border-t border-line-subtle px-5 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] [&>*]:flex-1 sm:[&>*]:flex-none">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</dialog>
