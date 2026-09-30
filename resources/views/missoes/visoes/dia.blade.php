{{-- VISÃO DIA — atrasadas (se hoje) → diárias → avulsas
     Etapa 5: contadores dentro de [data-progress-scope] se atualizam sozinhos ao marcar (sistema.js). --}}
@php
$atrasadas = $missoes->filter->is_overdue;
$doDia     = $missoes->reject->is_overdue;
$diarias   = $doDia->whereNotNull('recorrencia')->sortBy('concluida');
$avulsas   = $doDia->whereNull('recorrencia')->sortBy([['concluida', 'asc'], ['horario', 'asc']]);
$feitas    = $missoes->where('concluida', true);
@endphp

<div data-progress-scope class="contents">
    {{-- progresso do dia (inclui atrasadas resolvidas hoje) --}}
    <div>
        <x-sys.xp-bar data-progress-bar :current="$feitas->count()" :max="max($missoes->count(), 1)" unit="missões" label="Missões concluídas no dia" :show-values="false" />
        <p class="mt-1.5 flex justify-between text-xs text-ink-soft tabular">
            <span><span data-progress-done>{{ $feitas->count() }}</span>/<span data-progress-total>{{ $missoes->count() }}</span> concluídas</span>
            <span>+<span data-progress-xp class="font-semibold text-sys-glow">{{ $feitas->sum('xp') }}</span> de {{ $missoes->sum('xp') }} XP</span>
        </p>
    </div>

    @if ($atrasadas->isNotEmpty())
        <x-sys.window :label="'Atrasadas · ' . $atrasadas->count()" variant="danger" padding="sm">
            <div class="flex flex-col gap-2">
                @foreach ($atrasadas as $m) @include('missoes._card', ['m' => $m]) @endforeach
                <form method="POST" action="{{ route('missoes.atrasadas-hoje') }}">
                    @csrf @method('PATCH')
                    <x-sys.button type="submit" variant="secondary" icon="arrow-right" class="w-full">Trazer todas para hoje</x-sys.button>
                </form>
            </div>
        </x-sys.window>
    @endif

    @if ($missoes->isEmpty())
        <x-sys.window as="div" padding="lg">
            <div class="flex flex-col items-center gap-3 py-4 text-center">
                <x-sys.icon name="target" size="size-10" :stroke="1.5" class="text-ink-faint" />
                <p class="text-sm text-ink-soft">Nenhuma missão neste dia. O Sistema aguarda.</p>
                <x-sys.button variant="secondary" icon="plus" data-modal-open="modal-missao">Nova missão</x-sys.button>
            </div>
        </x-sys.window>
    @endif

    @foreach (['Missões diárias' => $diarias, 'Avulsas' => $avulsas] as $rotulo => $lista)
        @if ($lista->isNotEmpty())
            <section aria-label="{{ $rotulo }}" data-progress-scope class="flex flex-col gap-2">
                <h3 class="sys-label flex items-center gap-2">
                    {{ $rotulo }} <span class="text-ink-muted">· <span data-progress-done>{{ $lista->where('concluida', true)->count() }}</span>/{{ $lista->count() }}</span>
                </h3>
                @foreach ($lista as $m) @include('missoes._card', ['m' => $m]) @endforeach
            </section>
        @endif
    @endforeach
</div>
