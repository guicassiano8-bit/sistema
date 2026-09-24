{{--
  Container de gráfico — agnóstico: aceita <canvas> (Chart.js), <svg> próprio ou qualquer coisa no slot.
  A biblioteca e o tema dos gráficos entram na tela Tesouro (Etapa 3).

  <x-sys.chart label="Relatório" title="Patrimônio" value="R$ 48.920,15" delta="+2,4%" :delta-positive="true"
               description="Evolução do patrimônio nos últimos 12 meses">
      <x-slot:controls>
          <x-sys.segmented label="Período" :items="$periodos" />
      </x-slot:controls>
      <canvas data-chart="patrimonio" data-series='@json($serie)'></canvas>
      <x-slot:legend>
          <x-sys.chart-legend :items="[['label' => 'CDB', 'color' => '#3B82F6'], ['label' => 'FII', 'color' => '#A855F7']]" />
      </x-slot:legend>
      <x-slot:table>   (versão acessível: tabela sr-only com os mesmos dados)
          <table>...</table>
      </x-slot:table>
  </x-sys.chart>

  state: ready | loading (skeleton) | empty (mensagem + slot $empty)
  height: altura da área do gráfico (padrão h-48 no mobile, h-64 ≥ lg)
--}}
@props([
    'title',
    'label' => null,
    'value' => null,
    'delta' => null,
    'deltaPositive' => true,
    'description' => null,
    'state' => 'ready',
    'height' => 'h-48 lg:h-64',
])

@php $cid = 'chart-' . \Illuminate\Support\Str::random(6); @endphp

<x-sys.window :label="$label" padding="md" :attributes="$attributes->merge(['aria-describedby' => $description ? $cid.'-desc' : null])">
    <div class="-mt-1 mb-4 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h2 class="font-display text-xl font-semibold text-ink">{{ $title }}</h2>
            @if ($value)
                <p class="mt-1 flex items-baseline gap-2">
                    <span class="font-display text-3xl font-semibold tabular text-ink">{{ $value }}</span>
                    @if ($delta)
                        <span @class(['inline-flex items-center gap-1 text-sm font-medium tabular', 'text-gain-text' => $deltaPositive, 'text-danger-text' => ! $deltaPositive])>
                            <x-sys.icon :name="$deltaPositive ? 'trending-up' : 'trending-down'" size="size-4" /> {{ $delta }}
                        </span>
                    @endif
                </p>
            @endif
        </div>
        @isset($controls)<div class="w-full sm:w-auto">{{ $controls }}</div>@endisset
    </div>

    @if ($description)<p id="{{ $cid }}-desc" class="sr-only">{{ $description }}</p>@endif

    <div class="relative {{ $height }}" data-chart-area>
        @if ($state === 'loading')
            <div class="sys-skeleton absolute inset-0" aria-hidden="true"></div>
            <span class="sr-only">Carregando gráfico</span>
        @elseif ($state === 'empty')
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 border border-dashed border-line text-center">
                <x-sys.icon name="chart" size="size-8" class="text-ink-faint" />
                <p class="text-sm text-ink-soft">{{ $empty ?? 'Sem dados neste período.' }}</p>
            </div>
        @else
            {{-- grade de referência sutil atrás do gráfico --}}
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(96,165,250,0.08)_1px,transparent_1px)] bg-[length:100%_25%]" aria-hidden="true"></div>
            <div class="relative h-full">{{ $slot }}</div>
        @endif
    </div>

    @isset($legend)<div class="mt-3">{{ $legend }}</div>@endisset
    @isset($table)<div class="sr-only">{{ $table }}</div>@endisset
</x-sys.window>
