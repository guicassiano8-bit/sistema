{{--
  ABA ATIVOS (investimentos)
  $investimentos Collection<Investimento>: id, nome, tipo (CDB|FII), aplicado, atual, taxa (string), vencimento (date|null),
                 cotas (int|null), ultimo_dividendo (float|null, por cota)
  Alocação CDB × FII calculada aqui a partir de "atual".
--}}
@php
$aplicado = $investimentos->sum('aplicado');
$atual    = $investimentos->sum('atual');
$porTipo  = $investimentos->groupBy('tipo')->map->sum('atual');
$cores    = ['CDB' => '#38BDF8', 'FII' => '#A855F7'];   // validado: CVD ΔE 15 · direto rotulado
@endphp

<x-sys.window label="Carteira">
    <div class="grid grid-cols-2 gap-3">
        <div><p class="text-xs text-ink-soft">Investido</p><x-sys.money :value="$aplicado" class="font-display text-xl font-semibold" /></div>
        <div>
            <p class="text-xs text-ink-soft">Valor atual</p>
            <x-sys.money :value="$atual" class="font-display text-xl font-semibold" />
            <x-sys.money :value="$atual - $aplicado" signed class="block text-xs" />
        </div>
    </div>

    @if ($atual > 0)
        {{-- alocação: barra empilhada com 2px de respiro entre segmentos + rótulos diretos --}}
        <div class="mt-4 flex h-3 gap-0.5" role="img"
             aria-label="Alocação: {{ $porTipo->map(fn ($v, $t) => $t . ' ' . round($v / $atual * 100) . '%')->join(', ') }}">
            @foreach ($porTipo as $tipo => $valor)
                <span class="h-full first:rounded-l-[4px] last:rounded-r-[4px]" data-tip="{{ $tipo }} · {{ round($valor / $atual * 100) }}%"
                      style="width: {{ $valor / $atual * 100 }}%; background: {{ $cores[$tipo] ?? '#94A3B8' }}"></span>
            @endforeach
        </div>
        <div class="mt-2 flex gap-4 text-xs text-ink-soft" aria-hidden="true">
            @foreach ($porTipo as $tipo => $valor)
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-2.5" style="background: {{ $cores[$tipo] ?? '#94A3B8' }}"></span>
                    {{ $tipo }} <span class="font-medium tabular text-ink">{{ round($valor / $atual * 100) }}%</span>
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
    @forelse ($investimentos as $inv)
        @php $rend = $inv->atual - $inv->aplicado; $pct = $inv->aplicado > 0 ? $rend / $inv->aplicado * 100 : 0; @endphp
        <x-sys.window as="article" padding="md">
            <header class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1.5 font-display text-2xs font-semibold uppercase tracking-[0.14em]" style="color: {{ $inv->tipo === 'FII' ? '#C084FC' : '#38BDF8' }}">
                        <span class="size-1.5 rotate-45" style="background: currentColor"></span>{{ $inv->tipo }}
                    </span>
                    <h3 class="truncate font-display text-lg font-semibold text-ink">{{ $inv->nome }}</h3>
                    <p class="text-xs text-ink-muted">
                        @if ($inv->tipo === 'CDB')
                            {{ $inv->taxa }}@if ($inv->vencimento) · vence {{ $inv->vencimento->translatedFormat('d/m/Y') }}@endif
                        @else
                            {{ $inv->cotas }} cotas@if ($inv->ultimo_dividendo) · último dividendo R$ {{ number_format($inv->ultimo_dividendo, 2, ',', '.') }}/cota@endif
                        @endif
                    </p>
                </div>
                <x-sys.icon-button icon="pencil" :label="'Editar ' . $inv->nome" :href="route('tesouro.investimentos.edit', $inv)" />
            </header>
            <dl class="grid grid-cols-3 gap-2 border-t border-line-subtle pt-3">
                <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Aplicado</dt><dd><x-sys.money :value="$inv->aplicado" :decimals="0" class="text-sm" /></dd></div>
                <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Atual</dt><dd><x-sys.money :value="$inv->atual" :decimals="0" class="text-sm font-semibold" /></dd></div>
                <div>
                    <dt class="text-2xs uppercase tracking-wider text-ink-muted">Rendeu</dt>
                    <dd class="text-sm"><x-sys.money :value="$rend" signed :decimals="0" />
                        <span class="block text-2xs tabular {{ $pct >= 0 ? 'text-gain-text' : 'text-danger-text' }}">{{ $pct >= 0 ? '+' : '−' }}{{ number_format(abs($pct), 1, ',', '.') }}%</span>
                    </dd>
                </div>
            </dl>
        </x-sys.window>
    @empty
        <p class="py-8 text-center text-sm text-ink-soft lg:col-span-2">Nenhum investimento registrado.</p>
    @endforelse
</div>
