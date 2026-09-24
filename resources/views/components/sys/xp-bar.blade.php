{{--
  Barra de XP

  <x-sys.xp-bar :current="1240" :max="2000" />
  <x-sys.xp-bar :current="$xp" :max="$next" size="sm" :show-values="false" />
  <x-sys.xp-bar :current="80" :max="100" variant="gold" label="Meta de Ouro" unit="Ouro" />

  size: sm (4px, topo fixo) | md (8px) | lg (12px, tela de nível)
  variant: xp (azul) | gold | s (roxo, rank S)
  JS: Sistema.xp.set(el, novoValor, novoMax) anima a largura e dispara o brilho.
--}}
@props([
    'current' => 0,
    'max' => 100,
    'size' => 'md',
    'variant' => 'xp',
    'label' => 'Experiência',
    'showValues' => true,
    'unit' => 'XP',
])

@php
$pct = $max > 0 ? min(100, max(0, round($current / $max * 100, 1))) : 0;
$heights = ['sm' => 'h-1', 'md' => 'h-2', 'lg' => 'h-3'];
$fills = [
    'xp'   => 'from-sys-600 to-sys-glow shadow-glow-sm',
    'gold' => 'from-warning to-gold shadow-[0_0_8px_rgba(251,191,36,0.45)]',
    's'    => 'from-rank-s to-rank-s-text shadow-glow-s',
];
$fmt = fn ($n) => number_format($n, 0, ',', '.');
@endphp

<div data-xp-bar {{ $attributes->class(['w-full']) }}>
    <div role="progressbar" aria-label="{{ $label }}" aria-valuemin="0"
         aria-valuemax="{{ $max }}" aria-valuenow="{{ $current }}"
         aria-valuetext="{{ $fmt($current) }} de {{ $fmt($max) }} {{ $unit }}"
         @class(['relative overflow-hidden rounded-pill border border-line bg-surface-raised', $heights[$size] ?? $heights['md']])>
        <div data-xp-fill style="--xp: {{ $pct }}%"
             @class(['sys-xp-fill relative h-full overflow-hidden rounded-pill bg-linear-to-r', $fills[$variant] ?? $fills['xp']])>
            <span data-xp-shine class="sys-xp-shine"></span>
        </div>
    </div>

    @if ($showValues)
        <div class="mt-1.5 flex justify-between text-xs text-ink-soft tabular">
            <span><span data-xp-current>{{ $fmt($current) }}</span> / <span data-xp-max>{{ $fmt($max) }}</span> {{ $unit }}</span>
            <span>faltam <span data-xp-left>{{ $fmt(max(0, $max - $current)) }}</span></span>
        </div>
    @endif
</div>
