{{--
  Valor em reais, pt-BR, com sinal e cor opcionais.
  <x-sys.money :value="-42.9" signed />            → −R$ 42,90 (rosa)
  <x-sys.money :value="5200" signed />             → +R$ 5.200,00 (verde)
  <x-sys.money :value="48920.15" />                → R$ 48.920,15 (neutro)
  <x-sys.money :value="312" :decimals="0" />       → R$ 312
  tone="auto" (padrão: colore só quando signed) | neutral | gain | danger
--}}
@props(['value' => 0, 'signed' => false, 'decimals' => 2, 'tone' => 'auto'])

@php
$v = (float) $value;
$abs = number_format(abs($v), $decimals, ',', '.');
$sign = $signed ? ($v < 0 ? '−' : ($v > 0 ? '+' : '')) : ($v < 0 ? '−' : '');
$tone = $tone === 'auto' ? ($signed ? ($v < 0 ? 'danger' : ($v > 0 ? 'gain' : 'neutral')) : 'neutral') : $tone;
$colors = ['neutral' => '', 'gain' => 'text-gain-text', 'danger' => 'text-danger-text'];
@endphp

<span {{ $attributes->class(['tabular whitespace-nowrap', $colors[$tone] ?? '']) }}>{{ $sign }}R$&nbsp;{{ $abs }}</span>
