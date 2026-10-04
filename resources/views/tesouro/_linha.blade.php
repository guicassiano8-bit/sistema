{{-- Uma linha de lançamento (Transaction com category carregada) — @include('tesouro._linha', ['l' => $lancamento, 'semData' => false]) --}}
@php
$gasto = $l->type === \App\Enums\TransactionType::Expense;
$pendente = $l->status === \App\Enums\TransactionStatus::Pending;
@endphp
<li>
    <a href="{{ route('tesouro.lancamentos.edit', $l) }}" class="flex min-h-14 items-center gap-3 px-4 py-2 hover:bg-sys-400/5">
        <span @class(['flex size-8 shrink-0 items-center justify-center rounded-sm', 'bg-danger/10 text-danger-text' => $gasto, 'bg-gain/10 text-gain-text' => ! $gasto])>
            <x-sys.icon :name="$gasto ? 'trending-down' : 'trending-up'" size="size-4" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-base text-ink">{{ $l->description ?: ($gasto ? 'Gasto' : 'Ganho') }}</span>
            <span class="block truncate text-xs text-ink-muted">
                {{ $l->category->name }}@unless ($semData ?? false) · {{ $l->date->isToday() ? 'hoje' : ($l->date->isYesterday() ? 'ontem' : $l->date->translatedFormat('d/m')) }}@endunless @if ($pendente) · pendente @endif
            </span>
        </span>
        <x-sys.money :value="$l->signed_amount" signed @class(['font-display text-sm font-semibold', 'opacity-60' => $pendente]) />
    </a>
</li>
