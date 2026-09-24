{{--
  Campo de texto

  <x-sys.input name="titulo" label="Missão" placeholder="Ex.: Ler 20 páginas" required autofocus />
  <x-sys.input name="valor" label="Valor" prefix="R$" inputmode="decimal" data-money />
  <x-sys.input name="xp" label="Recompensa" type="number" suffix="XP" min="0" step="10" />
  <x-sys.input name="data" label="Data" type="date" :value="$missao->data?->format('Y-m-d')" />
  <x-sys.input name="busca" label="Buscar item" icon="search" :hide-label="true" />

  Erros: lê $errors->first($name) sozinho (e old($name)). Pode forçar com :error="'Mensagem'".
  Estados: default · hover (borda 50%) · focus (borda glow + anel) · erro (vermelho) · disabled/readonly.
  Fonte 16px sempre → o iOS não dá zoom ao focar.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'prefix' => null,
    'suffix' => null,
    'icon' => null,
    'hideLabel' => false,
])

@php
$id = $attributes->get('id') ?? 'f-' . str_replace(['[', ']', '.'], '-', $name);
$dotName = str_replace(['[', ']'], ['.', ''], $name);
$message = $error ?? $errors->first($dotName);
$current = $type === 'password' ? null : old($dotName, $value);
$describedBy = $message ? "$id-error" : ($hint ? "$id-hint" : null);
@endphp

<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" @class(['text-sm font-medium text-ink-soft', 'sr-only' => $hideLabel])>
        {{ $label }}@if ($attributes->has('required'))<span class="ml-0.5 text-sys-400" aria-hidden="true">*</span>@endif
    </label>

    <div @class([
        'chamfer group/field flex min-h-12 items-center gap-2 px-3 [--ch:6px] [--ch-bg:#111A2E]',
        'transition-[filter] duration-160',
        '[--ch-border:rgba(96,165,250,0.3)] hover:[--ch-border:rgba(96,165,250,0.5)]',
        'focus-within:[--ch-border:#38BDF8] focus-within:glow' => ! $message,
        '[--ch-border:#F43F5E] focus-within:drop-shadow-[0_0_6px_rgba(244,63,94,0.4)]' => $message,
        'has-[:disabled]:opacity-45' ,
    ])>
        @if ($icon)<x-sys.icon :name="$icon" size="size-5" class="text-ink-muted" />@endif
        @if ($prefix)<span class="font-display text-sm font-semibold text-ink-muted">{{ $prefix }}</span>@endif

        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}"
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if ($message) aria-invalid="true" @endif
               {{ $attributes->except('id')->class([
                   'min-w-0 flex-1 bg-transparent py-2.5 text-base text-ink placeholder:text-ink-faint',
                   'border-0 p-0 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none',
                   'tabular' => in_array($type, ['number', 'date', 'time']) || $prefix,
                   '[color-scheme:dark]',
               ]) }}>

        @if ($suffix)<span class="font-display text-sm font-semibold text-ink-muted">{{ $suffix }}</span>@endif
    </div>

    @if ($message)
        <p id="{{ $id }}-error" class="flex items-center gap-1.5 text-sm text-danger-text">
            <x-sys.icon name="alert" size="size-4" /> {{ $message }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
