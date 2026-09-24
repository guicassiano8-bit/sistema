{{--
  Controle segmentado (extra — usado em Missões dia/semana/mês/ano, períodos de gráfico, raridade no form)

  Navegação (links):
  <x-sys.segmented label="Visão" :items="[
      ['label' => 'Dia',    'href' => route('missoes.index', ['v' => 'dia']),    'active' => $v === 'dia'],
      ['label' => 'Semana', 'href' => route('missoes.index', ['v' => 'semana']), 'active' => $v === 'semana'],
      ['label' => 'Mês',    'href' => route('missoes.index', ['v' => 'mes']),    'active' => $v === 'mes'],
      ['label' => 'Ano',    'href' => route('missoes.index', ['v' => 'ano']),    'active' => $v === 'ano'],
  ]" />

  Campo de formulário (radios):
  <x-sys.segmented label="Tipo" name="tipo" :options="['gasto' => 'Gasto', 'ganho' => 'Ganho']" value="gasto" />
--}}
@props([
    'label',
    'items' => [],
    'name' => null,
    'options' => [],
    'value' => null,
])

@php
$wrap = 'chamfer chamfer-all grid auto-cols-fr grid-flow-col gap-1 p-1 [--ch:6px] [--ch-bg:#0B1220]';
$seg  = 'chamfer chamfer-all [--ch:4px] [--ch-bg:transparent] [--ch-border:transparent] flex min-h-tap items-center justify-center px-3 font-display text-sm font-semibold uppercase tracking-wider text-ink-soft transition-colors duration-160 hover:text-ink';
$on   = 'aria-[current=page]:text-white aria-[current=page]:[--ch-bg:rgba(37,99,235,0.28)] aria-[current=page]:[--ch-border:#3B82F6]';
$current = $name ? (string) old($name, $value) : null;
@endphp

@if ($name)
    <fieldset {{ $attributes->class(['min-w-0']) }}>
        <legend class="mb-1.5 text-sm font-medium text-ink-soft">{{ $label }}</legend>
        <div class="{{ $wrap }}">
            @foreach ($options as $v => $text)
                <label class="relative cursor-pointer">
                    <input type="radio" name="{{ $name }}" value="{{ $v }}" class="peer sr-only" @checked($current === (string) $v)>
                    <span class="{{ $seg }} peer-checked:[--ch-bg:rgba(37,99,235,0.28)] peer-checked:[--ch-border:#3B82F6] peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sys-glow">{{ $text }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
@else
    <nav aria-label="{{ $label }}" {{ $attributes->class([$wrap]) }}>
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" @if ($item['active'] ?? false) aria-current="page" @endif
               class="{{ $seg }} {{ $on }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
@endif
