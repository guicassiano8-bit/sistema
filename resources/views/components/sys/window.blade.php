{{--
  Janela do Sistema (card base de todo o app)

  <x-sys.window label="Missões diárias" title="Hoje" variant="active">
      <x-slot:actions><x-sys.icon-button icon="plus" label="Nova missão" /></x-slot:actions>
      ...conteúdo...
      <x-slot:footer>...</x-slot:footer>
  </x-sys.window>

  variant: default | raised | active | legendary | danger | success
  padding: none | sm | md | lg        as: section | article | div | aside
  interactive: true → hover/pressed (use em cards clicáveis com <a> dentro cobrindo o card)
--}}
@props([
    'label' => null,
    'title' => null,
    'variant' => 'default',
    'padding' => 'md',
    'scan' => true,
    'interactive' => false,
    'as' => 'section',
])

@php
$variants = [
    'default'   => 'sys-window',
    'raised'    => 'sys-window-raised',
    'active'    => 'sys-window border-active glow',
    'legendary' => '[--ch-border:#A855F7] [--ch-bg:#140F24] glow-s',
    'danger'    => 'sys-window [--ch-border:rgba(244,63,94,0.55)]',
    'success'   => 'sys-window [--ch-border:rgba(45,212,191,0.55)]',
];
$paddings = ['none' => '', 'sm' => 'p-3', 'md' => 'p-4 lg:p-5', 'lg' => 'p-5 lg:p-6'];
$headingId = $title ? 'win-' . \Illuminate\Support\Str::random(6) : null;
@endphp

<{{ $as }}
    @if ($headingId) aria-labelledby="{{ $headingId }}" @endif
    {{ $attributes->class([
        'chamfer block',
        $variants[$variant] ?? $variants['default'],
        'scanlines' => $scan,
        'is-interactive' => $interactive,
        $paddings[$padding] ?? $paddings['md'],
    ]) }}>

    @if ($label || $title || isset($actions))
        <header class="mb-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($label)
                    <p @class(['sys-label', 'text-rank-s-text' => $variant === 'legendary', 'text-danger-text' => $variant === 'danger'])>[ {{ $label }} ]</p>
                @endif
                @if ($title)
                    <h2 id="{{ $headingId }}" class="truncate font-display text-xl font-semibold text-ink">{{ $title }}</h2>
                @endif
            </div>
            @isset($actions)
                <div class="-mr-2 -mt-2 flex shrink-0 items-center">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}

    @isset($footer)
        <footer class="mt-4 border-t border-line-subtle pt-3">{{ $footer }}</footer>
    @endisset
</{{ $as }}>
