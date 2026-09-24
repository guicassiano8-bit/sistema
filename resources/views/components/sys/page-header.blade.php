{{--
  Cabeçalho de página
  <x-sys.page-header label="Qua, 23 set" title="Status do jogador">
      <x-slot:actions><x-sys.icon-button icon="plus" label="Nova recompensa" variant="outline" data-modal-open="modal-recompensa" /></x-slot:actions>
  </x-sys.page-header>
--}}
@props(['title', 'label' => null])

<div {{ $attributes->class(['flex items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        @if ($label)<p class="sys-label">[ {{ $label }} ]</p>@endif
        <h1 class="truncate font-display text-2xl font-bold uppercase tracking-wider text-ink lg:text-3xl">{{ $title }}</h1>
    </div>
    @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
</div>
