{{--
  ABA RESUMO
  $resumo  strings decimais: patrimonio, contas, investido, ganhos_mes, gastos_mes (positivo), saldo_mes, passivo_mes
           delta_pct (float|null: nulo sem mês anterior para comparar) · pct_gasto (int, 0–100)
  $ultimos Collection<Transaction> (5 mais recentes, com category)
--}}
@php
$delta = $resumo['delta_pct'];
$pctGasto = $resumo['pct_gasto'];
@endphp

{{-- 1 · ATRIBUTO PRINCIPAL --}}
<x-sys.window label="Atributo principal" variant="active">
    <p class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-ink-soft">Patrimônio</p>
    <p class="mt-1 font-display text-4xl font-bold text-ink text-glow lg:text-stat">
        <x-sys.money :value="$resumo['patrimonio']" />
    </p>
    @if ($delta !== null)
        <p @class(['mt-2 inline-flex items-center gap-1.5 text-sm tabular', 'text-gain-text' => $delta >= 0, 'text-danger-text' => $delta < 0])>
            <x-sys.icon :name="$delta >= 0 ? 'trending-up' : 'trending-down'" size="size-4" />
            {{ $delta >= 0 ? '+' : '−' }}{{ number_format(abs($delta), 1, ',', '.') }}% em relação ao mês passado
        </p>
    @else
        <p class="mt-2 text-sm text-ink-muted">Sem mês anterior para comparar.</p>
    @endif
    <p class="mt-1 text-xs text-ink-muted">
        Contas <x-sys.money :value="$resumo['contas']" /> · Investimentos <x-sys.money :value="$resumo['investido']" />
    </p>
</x-sys.window>

{{-- 2–3 · FLUXO DO MÊS + GANHO PASSIVO --}}
<div class="grid grid-cols-2 gap-2">
    <x-sys.stat label="Ganhos" icon="trending-up" tone="gain"><x-sys.money :value="$resumo['ganhos_mes']" :decimals="0" /></x-sys.stat>
    <x-sys.stat label="Gastos" icon="trending-down" tone="danger"><x-sys.money :value="$resumo['gastos_mes']" :decimals="0" /></x-sys.stat>
    <x-sys.stat label="Passivo" icon="zap" tone="sys" hint="rendimentos"><x-sys.money :value="$resumo['passivo_mes']" :decimals="0" /></x-sys.stat>
</div>

<x-sys.window label="Saldo do mês" padding="sm">
    <div class="flex items-baseline justify-between px-1">
        <span class="text-sm text-ink-soft">Gastou {{ round($pctGasto) }}% do que ganhou</span>
        <x-sys.money :value="$resumo['saldo_mes']" signed class="font-display text-lg font-semibold" />
    </div>
    <div class="mt-2 px-1">
        <x-sys.xp-bar :current="round($pctGasto)" :max="100" size="md" :show-values="false"
                      :variant="$pctGasto > 90 ? 'gold' : 'xp'" label="Percentual dos ganhos já gasto" />
    </div>
</x-sys.window>

{{-- ÚLTIMOS LANÇAMENTOS --}}
<section aria-labelledby="ult-lanc" class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <h2 id="ult-lanc" class="sys-label">Últimos lançamentos</h2>
        <a href="{{ route('tesouro.index', ['aba' => 'extrato']) }}" class="flex min-h-tap items-center gap-1 text-sm text-sys-400 hover:text-sys-300">Ver extrato <x-sys.icon name="chevron-right" size="size-4" /></a>
    </div>
    <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
        @forelse ($ultimos as $l)
            @include('tesouro._linha', ['l' => $l])
        @empty
            <li class="px-4 py-6 text-center text-sm text-ink-soft">Nenhum lançamento ainda.</li>
        @endforelse
    </x-sys.window>
</section>

<div class="grid grid-cols-2 gap-2">
    <x-sys.button variant="secondary" icon="plus" data-modal-open="modal-ganho">Lançar ganho</x-sys.button>
    <x-sys.button variant="danger" icon="minus" data-modal-open="modal-gasto" class="hidden lg:inline-flex">Lançar gasto</x-sys.button>
    <x-sys.button variant="secondary" icon="zap" data-modal-open="modal-rendimento" class="lg:hidden">Rendimento</x-sys.button>
</div>
