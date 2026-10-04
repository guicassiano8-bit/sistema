{{--
  ABA ATIVOS (investimentos)
  $posicoes Collection<AssetPosition>: asset (Asset), type (CDB|FII), invested e current (BigDecimal), quantity (BigDecimal|null, cotas),
            lastIncome (IncomeEntry|null), income (BigDecimal, rendimentos recebidos), result() (valorização + rendimentos), resultPercent(), lastIncomePerShare()
  Alocação por tipo calculada aqui a partir de "current".
--}}
@php
$aplicado = $posicoes->reduce(fn ($soma, $p) => $soma->plus($p->invested), \Brick\Math\BigDecimal::zero());
$atual    = $posicoes->reduce(fn ($soma, $p) => $soma->plus($p->current), \Brick\Math\BigDecimal::zero());
$porTipo  = $posicoes->groupBy('type')->map(fn ($grupo) => $grupo->reduce(fn ($soma, $p) => $soma->plus($p->current), \Brick\Math\BigDecimal::zero())->toFloat());
$totalAtual = $atual->toFloat();
$rendeuTotal = $posicoes->reduce(fn ($soma, $p) => $soma->plus($p->result()), \Brick\Math\BigDecimal::zero());
$cores    = ['CDB' => '#38BDF8', 'FII' => '#A855F7'];   // validado: CVD ΔE 15 · direto rotulado
@endphp

<x-sys.window label="Carteira">
    <div class="grid grid-cols-2 gap-3">
        <div><p class="text-xs text-ink-soft">Investido</p><x-sys.money :value="(string) $aplicado" class="font-display text-xl font-semibold" /></div>
        <div>
            <p class="text-xs text-ink-soft">Valor atual</p>
            <x-sys.money :value="(string) $atual" class="font-display text-xl font-semibold" />
            <x-sys.money :value="(string) $rendeuTotal" signed class="block text-xs" />
        </div>
    </div>

    @if ($totalAtual > 0)
        {{-- alocação: barra empilhada com 2px de respiro entre segmentos + rótulos diretos --}}
        <div class="mt-4 flex h-3 gap-0.5" role="img"
             aria-label="Alocação: {{ $porTipo->map(fn ($v, $t) => $t . ' ' . round($v / $totalAtual * 100) . '%')->join(', ') }}">
            @foreach ($porTipo as $tipo => $valor)
                <span class="h-full first:rounded-l-[4px] last:rounded-r-[4px]" data-tip="{{ $tipo }} · {{ round($valor / $totalAtual * 100) }}%"
                      style="width: {{ $valor / $totalAtual * 100 }}%; background: {{ $cores[$tipo] ?? '#94A3B8' }}"></span>
            @endforeach
        </div>
        <div class="mt-2 flex gap-4 text-xs text-ink-soft" aria-hidden="true">
            @foreach ($porTipo as $tipo => $valor)
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-2.5" style="background: {{ $cores[$tipo] ?? '#94A3B8' }}"></span>
                    {{ $tipo }} <span class="font-medium tabular text-ink">{{ round($valor / $totalAtual * 100) }}%</span>
                </span>
            @endforeach
        </div>
    @endif
</x-sys.window>

<div class="grid grid-cols-2 gap-2">
    <x-sys.button variant="secondary" icon="plus" data-modal-open="modal-investimento">Investimento</x-sys.button>
    <x-sys.button variant="secondary" icon="zap" data-modal-open="modal-rendimento">Rendimento</x-sys.button>
</div>

<div class="grid gap-3 lg:grid-cols-2">
    @forelse ($posicoes as $p)
        @php
        $inv = $p->asset;
        $rend = $p->result();
        $pct = $p->resultPercent();
        $porCota = $p->lastIncomePerShare();
        @endphp
        <x-sys.window as="article" padding="md">
            <header class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1.5 font-display text-2xs font-semibold uppercase tracking-[0.14em]" style="color: {{ $p->type === 'FII' ? '#C084FC' : '#38BDF8' }}">
                        <span class="size-1.5 rotate-45" style="background: currentColor"></span>{{ $p->type }}
                    </span>
                    <h3 class="truncate font-display text-lg font-semibold text-ink">{{ $inv->name }}</h3>
                    <p class="text-xs text-ink-muted">
                        @if (! $p->marketTraded)
                            {{ $inv->rate_label ?? 'Sem taxa informada' }}@if ($inv->maturity_date) · vence {{ $inv->maturity_date->format('d/m/Y') }}@endif
                        @else
                            {{ $inv->ticker }} · {{ number_format($p->quantity->toFloat(), 0, ',', '.') }} cotas @if ($porCota) · último rendimento R$ {{ number_format($porCota->toFloat(), 2, ',', '.') }}/cota @endif
                        @endif
                    </p>
                </div>
                <x-sys.icon-button icon="pencil" :label="'Gerenciar ' . $inv->name" :href="route('tesouro.investimentos.edit', $inv)" />
            </header>
            <dl class="grid grid-cols-3 gap-2 border-t border-line-subtle pt-3">
                <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Aplicado</dt><dd><x-sys.money :value="(string) $p->invested" :decimals="0" class="text-sm" /></dd></div>
                <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Atual</dt><dd><x-sys.money :value="(string) $p->current" :decimals="0" class="text-sm font-semibold" /></dd></div>
                <div>
                    <dt class="text-2xs uppercase tracking-wider text-ink-muted">Rendeu</dt>
                    <dd class="text-sm"><x-sys.money :value="(string) $rend" signed :decimals="0" />
                        @if ($p->income->isPositive())<span class="block text-2xs text-ink-muted">c/ rendimentos</span>@endif
                        <span class="block text-2xs tabular {{ $pct >= 0 ? 'text-gain-text' : 'text-danger-text' }}">{{ $pct >= 0 ? '+' : '−' }}{{ number_format(abs($pct), 1, ',', '.') }}%</span>
                    </dd>
                </div>
            </dl>
        </x-sys.window>
    @empty
        <p class="py-8 text-center text-sm text-ink-soft lg:col-span-2">Nenhum investimento registrado.</p>
    @endforelse
</div>
