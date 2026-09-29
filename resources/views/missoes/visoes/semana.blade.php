{{-- VISÃO SEMANA — agenda vertical até 1279px · 7 colunas a partir de 1280px (com a sidebar, abaixo disso cada coluna ficaria < 100px) --}}
@php
$diaLink = fn ($d) => route('missoes.index', ['v' => 'dia', 'data' => $d->format('Y-m-d')]);
@endphp

{{-- CELULAR / TABLET / DESKTOP ESTREITO: lista por dia --}}
<div class="flex flex-col gap-4 xl:hidden">
    @foreach ($dias as $dia)
        @php
            $d = $dia['data']; $ms = $dia['missoes'];
            $feitas = $ms->where('concluida', true)->count();
        @endphp
        <section aria-labelledby="sem-{{ $d->format('Ymd') }}" data-progress-scope
                 @class(['flex flex-col gap-2', 'chamfer sys-window border-active glow p-3' => $d->isToday()])>
            <a href="{{ $diaLink($d) }}" id="sem-{{ $d->format('Ymd') }}" class="flex min-h-tap items-center gap-3">
                <span @class(['font-display text-sm font-semibold uppercase tracking-wider', 'text-sys-glow' => $d->isToday(), 'text-ink-soft' => ! $d->isToday()])>
                    {{ $d->translatedFormat('D') }}
                </span>
                <span @class(['font-display text-2xl font-bold tabular', 'text-ink' => ! $d->isPast() || $d->isToday(), 'text-ink-muted' => $d->isPast() && ! $d->isToday()])>{{ $d->format('d') }}</span>
                @if ($d->isToday())<span class="sys-label">Hoje</span>@endif
                <span class="ml-auto flex items-center gap-2 text-xs tabular text-ink-soft">
                    @if ($ms->isNotEmpty())
                        <span class="h-1 w-12 overflow-hidden rounded-pill bg-surface-raised" aria-hidden="true">
                            <span data-progress-fill class="block h-full bg-success transition-[width] duration-400 ease-out" style="width: {{ round($feitas / $ms->count() * 100) }}%"></span>
                        </span>
                        <span><span data-progress-done>{{ $feitas }}</span>/<span data-progress-total>{{ $ms->count() }}</span></span>
                    @else
                        sem missões
                    @endif
                    <x-sys.icon name="chevron-right" size="size-4" />
                </span>
            </a>
            @foreach ($ms as $m) @include('missoes._card', ['m' => $m]) @endforeach
        </section>
    @endforeach
</div>

{{-- DESKTOP LARGO (≥ 1280): 7 colunas estilo agenda --}}
<div class="hidden grid-cols-7 gap-2 xl:grid">
    @foreach ($dias as $dia)
        @php $d = $dia['data']; $ms = $dia['missoes']; @endphp
        <section aria-label="{{ $d->translatedFormat('l, d \d\e F') }}"
                 @class(['chamfer sys-window flex min-h-72 flex-col gap-1.5 p-2 [--ch:8px]', 'border-active glow' => $d->isToday()])>
            <a href="{{ $diaLink($d) }}" class="mb-1 flex items-baseline justify-between px-1 hover:text-sys-300">
                <span class="font-display text-xs font-semibold uppercase tracking-wider {{ $d->isToday() ? 'text-sys-glow' : 'text-ink-soft' }}">{{ $d->translatedFormat('D') }}</span>
                <span class="font-display text-xl font-bold tabular">{{ $d->format('d') }}</span>
            </a>
            @forelse ($ms as $m)
                <form method="POST" action="{{ route('missoes.toggle', $m) }}" data-mission-toggle
                      data-mission data-state="{{ $m->status ? 'done' : 'pending' }}" data-xp="{{ $m->points }}" data-title="{{ $m->title }}"
                      class="group">
                    @csrf @method('PATCH')
                    <button type="submit" aria-pressed="{{ $m->status ? 'true' : 'false' }}" aria-label="Concluir missão: {{ $m->title }}"
                            class="flex w-full items-start gap-1.5 rounded-sm px-1 py-1 text-left hover:bg-surface-hover">
                        <span class="mt-0.5 flex size-3.5 shrink-0 items-center justify-center border border-sys-400/60 text-transparent group-data-[state=done]:border-success group-data-[state=done]:bg-success/20 group-data-[state=done]:text-success-text">
                            <x-sys.icon name="check" size="size-3" :stroke="3" />
                        </span>
                        <span class="min-w-0 flex-1 text-xs leading-snug text-ink group-data-[state=done]:text-ink-muted"><span class="sys-strike">{{ $m->title }}</span></span>
                        <span class="font-display text-2xs font-semibold text-sys-glow group-data-[state=done]:text-ink-muted">+{{ $m->points }}</span>
                    </button>
                </form>
            @empty
                <p class="px-1 text-xs text-ink-faint">—</p>
            @endforelse
        </section>
    @endforeach
</div>
