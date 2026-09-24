{{--
  Select nativo estilizado (no celular abre o seletor do sistema operacional — mais rápido e acessível)

  <x-sys.select name="categoria" label="Raridade" :options="[
      'urgente' => 'Lendário · Urgente',
      'importante' => 'Raro · Importante',
      'dia_a_dia' => 'Comum · Dia a dia',
      'nao_importante' => 'Descartável · Não importante',
  ]" :value="$item->categoria" />

  <x-sys.select name="recorrencia" label="Repetir" placeholder="Não repete">
      <option value="diaria">Todo dia</option>
      <optgroup label="Semanal">...</optgroup>
  </x-sys.select>

  Mesmos estados e regras de erro do <x-sys.input>.
--}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'hideLabel' => false,
])

@php
$id = $attributes->get('id') ?? 'f-' . str_replace(['[', ']', '.'], '-', $name);
$dotName = str_replace(['[', ']'], ['.', ''], $name);
$message = $error ?? $errors->first($dotName);
$current = (string) old($dotName, $value);
$describedBy = $message ? "$id-error" : ($hint ? "$id-hint" : null);
@endphp

<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" @class(['text-sm font-medium text-ink-soft', 'sr-only' => $hideLabel])>{{ $label }}</label>

    <div @class([
        'chamfer relative flex min-h-12 items-center [--ch:6px] [--ch-bg:#111A2E]',
        '[--ch-border:rgba(96,165,250,0.3)] hover:[--ch-border:rgba(96,165,250,0.5)]',
        'focus-within:[--ch-border:#38BDF8] focus-within:glow' => ! $message,
        '[--ch-border:#F43F5E]' => $message,
        'has-[:disabled]:opacity-45',
    ])>
        <select id="{{ $id }}" name="{{ $name }}"
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($message) aria-invalid="true" @endif
                {{ $attributes->except('id')->class([
                    'w-full min-h-12 cursor-pointer appearance-none bg-transparent py-2.5 pl-3 pr-10 text-base text-ink',
                    'border-0 outline-none focus:outline-none focus:ring-0 [color-scheme:dark]',
                ]) }}>
            @if ($placeholder)
                <option value="" @selected($current === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected($current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-sys.icon name="chevron-down" size="size-5" class="pointer-events-none absolute right-3 text-sys-400" />
    </div>

    @if ($message)
        <p id="{{ $id }}-error" class="flex items-center gap-1.5 text-sm text-danger-text"><x-sys.icon name="alert" size="size-4" /> {{ $message }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
