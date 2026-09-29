{{-- VISÃO ANO — 12 mini-heatmaps (1 tom azul, 5 níveis: vazio → 100% concluído) --}}
@php
$ano = $data->year;
$tot = collect($resumoAno)->sum('total');
$fei = collect($resumoAno)->sum('feitas');
$nivel = function ($r) {
    if (! $r || ! $r['total']) return 0;
    $p = $r['feitas'] / $r['total'];
    return $p >= 1 ? 4 : ($p >= 0.66 ? 3 : ($p >= 0.33 ? 2 : 1));
};
// escala sequencial: um tom (#38BDF8), claridade crescente
$cores = ['#111A2E', 'rgba(56,189,248,0.22)', 'rgba(56,189,248,0.45)', 'rgba(56,189,248,0.72)', '#38BDF8'];
@endphp

<div class="grid grid-cols-3 gap-2">
    <x-sys.stat label="Missões">{{ number_format($tot, 0, ',', '.') }}</x-sys.stat>
    <x-sys.stat label="Concluídas" tone="sys">{{ number_format($fei, 0, ',', '.') }}</x-sys.stat>
    <x-sys.stat label="Taxa" tone="gain">{{ $tot ? round($fei / $tot * 100) : 0 }}%</x-sys.stat>
</div>

<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
    @for ($mes = 1; $mes <= 12; $mes++)
        @php
            $ini = \Carbon\Carbon::create($ano, $mes, 1);
            $offset = $ini->dayOfWeek;              // 0 = domingo
            $qtd = $ini->daysInMonth;
        @endphp
        <a href="{{ route('missoes.index', ['v' => 'mes', 'data' => $ini->format('Y-m-d')]) }}"
           @class(['chamfer sys-window is-interactive flex flex-col gap-2 p-3 [--ch:8px]', 'border-active' => $ini->isSameMonth(today())])>
            <span class="flex items-baseline justify-between">
                <span class="font-display text-sm font-semibold uppercase tracking-wider text-ink">{{ $ini->translatedFormat('M') }}</span>
                @php $rm = collect($resumoAno)->filter(fn ($v, $k) => str_starts_with($k, $ini->format('Y-m'))); @endphp
                <span class="text-2xs tabular text-ink-muted">{{ $rm->sum('feitas') }}/{{ $rm->sum('total') }}</span>
            </span>
            {{-- colunas = semanas, linhas = dias da semana --}}
            <span class="grid grid-flow-col grid-rows-7 gap-[3px]" aria-hidden="true">
                @for ($i = 0; $i < $offset; $i++)<span class="size-2"></span>@endfor
                @for ($dia = 1; $dia <= $qtd; $dia++)
                    @php $k = $ini->copy()->day($dia)->format('Y-m-d'); $r = $resumoAno[$k] ?? null; @endphp
                    <span class="size-2 rounded-[2px]" style="background: {{ $cores[$nivel($r)] }}"
                          @if ($r) data-tip="{{ $dia }}/{{ $mes }} · {{ $r['feitas'] }}/{{ $r['total'] }}" @endif></span>
                @endfor
            </span>
        </a>
    @endfor
</div>

<p class="flex items-center justify-end gap-1.5 text-2xs text-ink-muted" aria-hidden="true">
    menos @foreach ($cores as $c)<span class="size-2.5 rounded-[2px]" style="background: {{ $c }}"></span>@endforeach mais
</p>
