{{--
  Barras horizontais em um só tom (ranking: gastos por categoria, etc.)
  <x-sys.hbar-chart :items="[['label' => 'Alimentação', 'value' => 1240], ['label' => 'Transporte', 'value' => 410]]" />
  Ordene do maior para o menor no controller. Valor sempre escrito ao lado (não depende de cor).
--}}
@props(['items' => [], 'color' => '#38BDF8', 'format' => 'money'])

@php
$max = collect($items)->max('value') ?: 1;
$f = $format === 'int'
    ? fn ($v) => number_format($v, 0, ',', '.')
    : fn ($v) => 'R$ ' . number_format($v, 0, ',', '.');
@endphp

<ul {{ $attributes->class(['flex flex-col gap-3']) }}>
    @foreach ($items as $it)
        <li class="grid grid-cols-[minmax(0,7rem)_1fr_auto] items-center gap-3" data-tip="{{ $it['label'] }} · {{ $f($it['value']) }}">
            <span class="truncate text-sm text-ink-soft">{{ $it['label'] }}</span>
            <span class="h-2 overflow-hidden rounded-r-[4px] bg-surface-raised">
                <span class="block h-full rounded-r-[4px]" style="width: {{ round($it['value'] / $max * 100, 2) }}%; background: {{ $color }}"></span>
            </span>
            <span class="text-sm font-medium tabular text-ink">{{ $f($it['value']) }}</span>
        </li>
    @endforeach
</ul>
