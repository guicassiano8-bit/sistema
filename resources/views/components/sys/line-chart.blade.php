{{--
  Gráfico de linha/área renderizado no servidor (SVG + HTML, zero biblioteca).
  1 série só (o título do <x-sys.chart> nomeia a série; sem legenda).

  <x-sys.line-chart :points="[['label' => 'out/25', 'value' => 41200], …]" format="money" />

  format: money | int | pct       color: cor da linha (padrão sys.glow)
  Hover/toque: coluna invisível por ponto com data-tip → tooltip do sistema.js
--}}
@props(['points' => [], 'format' => 'money', 'color' => '#38BDF8', 'area' => true])

@php
$n = count($points);
$vals = array_column($points, 'value');
$min = $n ? min($vals) : 0;
$max = $n ? max($vals) : 1;
$pad = max(($max - $min) * 0.15, abs($max) * 0.02, 1);
$lo = $min - $pad; $hi = $max + $pad;
$W = 300; $H = 150;
$x = fn ($i) => $n > 1 ? $i / ($n - 1) * $W : $W / 2;
$y = fn ($v) => $H - (($v - $lo) / ($hi - $lo)) * $H;
$line = collect($points)->map(fn ($p, $i) => ($i ? 'L' : 'M') . round($x($i), 2) . ' ' . round($y($p['value']), 2))->implode(' ');
$areaPath = $n ? $line . " L{$W} {$H} L0 {$H} Z" : '';
$f = match ($format) {
    'int' => fn ($v) => number_format($v, 0, ',', '.'),
    'pct' => fn ($v) => number_format($v, 1, ',', '.') . '%',
    default => fn ($v) => 'R$ ' . number_format($v, 0, ',', '.'),
};
$ticks = [$hi - ($hi - $lo) * 0.125, ($hi + $lo) / 2, $lo + ($hi - $lo) * 0.125];
$gid = 'lg' . \Illuminate\Support\Str::random(5);
$last = $n ? $points[$n - 1] : null;
@endphp

<div {{ $attributes->class(['relative flex h-full flex-col']) }} data-chart-line>
    <div class="relative flex-1">
        {{-- grade + rótulos do eixo Y (recessivos) --}}
        @foreach ($ticks as $t)
            <div class="pointer-events-none absolute inset-x-0 border-t border-line-subtle" style="top: {{ round($y($t) / $H * 100, 2) }}%">
                <span class="absolute left-0 -translate-y-full pb-0.5 text-2xs tabular text-ink-muted">{{ $f($t) }}</span>
            </div>
        @endforeach

        <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" class="absolute inset-0 size-full overflow-visible" aria-hidden="true">
            <defs>
                <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="{{ $color }}" stop-opacity="0.28" />
                    <stop offset="1" stop-color="{{ $color }}" stop-opacity="0" />
                </linearGradient>
            </defs>
            @if ($area && $n)<path d="{{ $areaPath }}" fill="url(#{{ $gid }})" />@endif
            <path d="{{ $line }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        </svg>

        {{-- marcador do último ponto + rótulo direto (HTML, não distorce) --}}
        @if ($last)
            <span class="pointer-events-none absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-surface"
                  style="left: 100%; top: {{ round($y($last['value']) / $H * 100, 2) }}%; background: {{ $color }}; box-shadow: 0 0 8px {{ $color }}"></span>
        @endif

        {{-- colunas de hover (alvo maior que a marca) --}}
        @php $colW = 100 / max($n - 1, 1); @endphp
        <div class="absolute inset-0">
            @foreach ($points as $i => $p)
                <span class="group/pt absolute inset-y-0" data-tip="{{ $p['label'] }} · {{ $f($p['value']) }}"
                      style="left: {{ round($x($i) / $W * 100 - $colW / 2, 3) }}%; width: {{ round($colW, 3) }}%">
                    <span class="pointer-events-none absolute inset-y-0 left-1/2 hidden w-px bg-sys-400/50 group-hover/pt:block"></span>
                </span>
            @endforeach
        </div>
    </div>

    @if ($n)
        <div class="mt-1.5 flex justify-between text-2xs tabular text-ink-muted" aria-hidden="true">
            <span>{{ $points[0]['label'] }}</span>
            @if ($n > 2)<span>{{ $points[intdiv($n - 1, 2)]['label'] }}</span>@endif
            <span>{{ $last['label'] }}</span>
        </div>
    @endif
</div>
