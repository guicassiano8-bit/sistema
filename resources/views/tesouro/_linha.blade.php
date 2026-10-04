{{-- Uma linha de lançamento — @include('tesouro._linha', ['l' => $lancamento, 'semData' => false]) --}}
@php $gasto = $l->valor < 0; @endphp
<li class="flex min-h-14 items-center gap-3 px-4 py-2">
    <span @class(['flex size-8 shrink-0 items-center justify-center rounded-sm', 'bg-danger/10 text-danger-text' => $gasto, 'bg-gain/10 text-gain-text' => ! $gasto])>
        <x-sys.icon :name="$gasto ? 'trending-down' : 'trending-up'" size="size-4" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block truncate text-base text-ink">{{ $l->descricao ?: ($gasto ? 'Gasto' : 'Ganho') }}</span>
        <span class="block truncate text-xs text-ink-muted">
            {{ $l->categoria_rotulo ?? $l->categoria }}@unless ($semData ?? false) · {{ $l->data->isToday() ? 'hoje' : ($l->data->isYesterday() ? 'ontem' : $l->data->translatedFormat('d/m')) }}@endunless
        </span>
    </span>
    <x-sys.money :value="$l->valor" signed class="font-display text-sm font-semibold" />
</li>
