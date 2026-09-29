{{-- VISÃO MÊS — calendário (semana começa no domingo) + lista do dia selecionado --}}
@php
$inicio = $data->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SUNDAY);
$fim    = $data->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
$semanaNomes = [['D', 'Domingo'], ['S', 'Segunda'], ['T', 'Terça'], ['Q', 'Quarta'], ['Q', 'Quinta'], ['S', 'Sexta'], ['S', 'Sábado']];
$dias   = collect(\Carbon\CarbonPeriod::create($inicio, $fim));
$sel    = $data->format('Y-m-d');
@endphp

<x-sys.window as="div" padding="sm">
    <table class="w-full table-fixed border-separate border-spacing-1" aria-label="{{ ucfirst($data->translatedFormat('F Y')) }}">
        <thead>
            <tr>
                @foreach ($semanaNomes as [$abrev, $nome])
                    <th scope="col" class="pb-1 font-display text-xs font-semibold text-ink-muted"><abbr title="{{ $nome }}" class="no-underline">{{ $abrev }}</abbr></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($dias->chunk(7) as $semana)
                <tr>
                    @foreach ($semana as $d)
                        @php
                            $k = $d->format('Y-m-d');
                            $r = $resumoMes[$k] ?? ['total' => 0, 'feitas' => 0];
                            $pct = $r['total'] ? $r['feitas'] / $r['total'] : 0;
                            $fora = ! $d->isSameMonth($data);
                            $cor = $r['total'] === 0 ? null : ($pct >= 1 ? 'bg-success' : ($d->lt(today()) ? 'bg-warning' : 'bg-sys-500'));
                        @endphp
                        <td class="p-0">
                            <a href="{{ route('missoes.index', ['v' => 'mes', 'data' => $k]) }}"
                               @if ($k === $sel) aria-current="date" @endif
                               aria-label="{{ $d->translatedFormat('d \d\e F') }}{{ $r['total'] ? ", {$r['feitas']} de {$r['total']} missões" : ', sem missões' }}"
                               @class([
                                   'flex h-12 flex-col items-center justify-center gap-1 rounded-sm font-display text-sm font-semibold tabular transition-colors duration-160',
                                   'bg-sys-600/25 text-white ring-1 ring-sys-500' => $k === $sel,
                                   'ring-1 ring-sys-glow/70 text-sys-glow shadow-glow-sm' => $d->isToday() && $k !== $sel,
                                   'text-ink-faint' => $fora && $k !== $sel,
                                   'text-ink hover:bg-surface-hover' => ! $fora && $k !== $sel && ! $d->isToday(),
                               ])>
                                {{ $d->day }}
                                <span class="h-1 w-6 overflow-hidden rounded-pill {{ $r['total'] ? 'bg-surface-hover' : '' }}" aria-hidden="true">
                                    @if ($cor)<span class="block h-full {{ $cor }}" style="width: {{ max(round($pct * 100), 15) }}%"></span>@endif
                                </span>
                            </a>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 px-1 text-2xs text-ink-muted">
        <span class="inline-flex items-center gap-1.5"><span class="h-1 w-3 rounded-pill bg-success"></span>completo</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-1 w-3 rounded-pill bg-warning"></span>incompleto</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-1 w-3 rounded-pill bg-sys-500"></span>a fazer</span>
    </p>
</x-sys.window>

{{-- dia selecionado --}}
<section aria-labelledby="mes-dia" data-progress-scope class="flex flex-col gap-2">
    <h3 id="mes-dia" class="sys-label">{{ $data->translatedFormat('l, d \d\e F') }} · <span data-progress-done>{{ $missoes->where('concluida', true)->count() }}</span>/<span data-progress-total>{{ $missoes->count() }}</span></h3>
    @forelse ($missoes as $m)
        @include('missoes._card', ['m' => $m])
    @empty
        <p class="py-4 text-center text-sm text-ink-soft">Sem missões neste dia.</p>
    @endforelse
</section>
