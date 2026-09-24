{{--
  <x-sys.chart-legend :items="[['label' => 'CDB', 'color' => '#3B82F6', 'value' => 'R$ 30.100'], ['label' => 'FII', 'color' => '#A855F7']]" />
--}}
@props(['items' => []])

<ul {{ $attributes->class(['flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-ink-soft']) }}>
    @foreach ($items as $i)
        <li class="inline-flex items-center gap-1.5">
            <span class="size-2.5 shrink-0" style="background: {{ $i['color'] }}; box-shadow: 0 0 6px {{ $i['color'] }}" aria-hidden="true"></span>
            {{ $i['label'] }}
            @isset($i['value'])<span class="font-medium tabular text-ink">{{ $i['value'] }}</span>@endisset
        </li>
    @endforeach
</ul>
