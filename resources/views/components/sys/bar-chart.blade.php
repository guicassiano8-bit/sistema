{{--
  Barras verticais agrupadas (HTML/CSS). Até 2–3 séries; eixo começa em zero.

  <x-sys.bar-chart
      :labels="['abr', 'mai', 'jun', 'jul', 'ago', 'set']"
      :series="[
          ['name' => 'Ganhos', 'color' => '#34D399', 'values' => [5200, 5100, 5400, 5200, 5600, 5200]],
          ['name' => 'Gastos', 'color' => '#F43F5E', 'values' => [3100, 3900, 2800, 3300, 3500, 3180]],
      ]" />
  Legenda: use <x-sys.chart-legend> no slot legend do <x-sys.chart>.
--}}
@props(['labels' => [], 'series' => [], 'format' => 'money'])

@php
$max = collect($series)->flatMap(fn ($s) => $s['values'])->max() ?: 1;
$f = $format === 'int'
    ? fn ($v) => number_format($v, 0, ',', '.')
    : fn ($v) => 'R$ ' . number_format($v, 0, ',', '.');
@endphp

<div {{ $attributes->class(['flex h-full flex-col']) }}>
    <div class="relative flex flex-1 items-end gap-2 border-b border-line">
        {{-- grade: 50% e 100% do máximo --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-line-subtle"><span class="absolute left-0 -translate-y-full pb-0.5 text-2xs tabular text-ink-muted">{{ $f($max) }}</span></div>
        <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-line-subtle"></div>

        @foreach ($labels as $i => $label)
            <div class="relative flex h-full flex-1 items-end justify-center gap-0.5">
                @foreach ($series as $s)
                    @php $v = $s['values'][$i] ?? 0; @endphp
                    <span class="group/bar relative flex h-full max-w-5 flex-1 items-end" data-tip="{{ $label }} · {{ $s['name'] }} {{ $f($v) }}">
                        <span class="w-full rounded-t-[4px] transition-opacity duration-160 group-hover/bar:opacity-80"
                              style="height: {{ round($v / $max * 100, 2) }}%; background: {{ $s['color'] }}; min-height: {{ $v > 0 ? '2px' : '0' }}"></span>
                    </span>
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="mt-1.5 flex gap-2" aria-hidden="true">
        @foreach ($labels as $label)
            <span class="flex-1 text-center text-2xs uppercase text-ink-muted">{{ $label }}</span>
        @endforeach
    </div>
</div>
