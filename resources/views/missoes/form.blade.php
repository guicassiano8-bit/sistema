{{--
  CRIAR / EDITAR MISSÃO (formulário completo)
  GET /missoes/create (name: missoes.create — aceita ?titulo=&xp=&data=&recorrencia= vindos do modal rápido)
  GET /missoes/{missao}/edit (name: missoes.edit)
  Dados: $missao (Task — new Task no create; no edit já vem com recurringTask)
  Campos: titulo, descricao, data, horario, xp, ouro, rank, recorrencia (nenhuma|diaria|semanal|mensal),
          recorrencia_dias (array ISO 1–7, semanal), recorrencia_dia_mes (1–31, mensal), termina_em,
          aplicar_futuras (só ao editar uma ocorrência recorrente)
--}}
@php
use App\Enums\Frequency;
use App\Enums\TaskRank;

$editando = $missao->exists;
$molde = $missao->recurringTask;
$concluida = $editando && $missao->is_done;

// old() > query string do modal rápido > valor da missão > padrão
$val = fn ($campo, $atual = null, $padrao = null) => old($campo, request($campo, $atual ?? $padrao));

$fmtData = fn ($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : $d;

$recorrenciaAtual = match ($molde?->frequency) {
    Frequency::Daily => 'diaria',
    Frequency::Weekly => 'semanal',
    Frequency::Monthly => 'mensal',
    default => 'nenhuma',
};

// letra exibida => dia ISO (1 = segunda … 7 = domingo)
$diasSemana = [7 => 'D', 1 => 'S', 2 => 'T', 3 => 'Q', 4 => 'Q', 5 => 'S', 6 => 'S'];
$diasMarcados = collect(old('recorrencia_dias', $molde?->days_of_week ?? []))->map(fn ($d) => (int) $d)->all();

$dataVal = $fmtData($val('data', $fmtData($missao->scheduled_date), today()));
$terminaVal = $fmtData(old('termina_em', $molde?->ends_on));
$horario = $val('horario', $missao->start_time);
@endphp

<x-layouts.app :title="$editando ? 'Editar missão' : 'Nova missão'">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar para missões" :href="route('missoes.index')" />
        <x-sys.page-header :label="$editando ? 'Editar' : 'Nova'" :title="$editando ? $missao->title : 'Nova missão'" class="min-w-0 flex-1" />
    </div>

    <form id="form-missao" method="POST" data-sys-form
          action="{{ $editando ? route('missoes.update', $missao) : route('missoes.store') }}"
          class="group/form flex flex-col gap-5 lg:max-w-2xl">
        @csrf
        @if ($editando) @method('PUT') @else <input type="hidden" name="completo" value="1"> @endif

        <x-sys.window label="Objetivo">
            <div class="flex flex-col gap-4">
                <x-sys.input name="titulo" label="Missão" :value="$val('titulo', $missao->title)" required autofocus maxlength="120" />
                <div class="flex flex-col gap-1.5">
                    <label for="f-descricao" class="text-sm font-medium text-ink-soft">Descrição <span class="text-ink-muted">(opcional)</span></label>
                    <textarea id="f-descricao" name="descricao" rows="3"
                              class="w-full resize-y rounded-sm border-0 bg-surface-raised px-3 py-2.5 text-base text-ink outline-none ring-1 ring-line placeholder:text-ink-faint focus:ring-2 focus:ring-sys-glow">{{ $val('descricao', $missao->description) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="data" label="Data" type="date" :value="$dataVal" required />
                    <x-sys.input name="horario" label="Horário" type="time" :value="$horario ? substr($horario, 0, 5) : null" hint="Opcional" />
                </div>
            </div>
        </x-sys.window>

        <x-sys.window label="Recompensa">
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="xp" label="Experiência" type="number" min="0" step="5" suffix="XP" :value="$val('xp', $missao->points, 30)" :readonly="$concluida" required inputmode="numeric" />
                    <x-sys.input name="ouro" label="Ouro" type="number" min="0" step="5" suffix="OURO" :value="$val('ouro', $editando ? $missao->gold : null, $val('xp', null, 30))" :readonly="$concluida" inputmode="numeric" />
                </div>
                <x-sys.segmented label="Dificuldade (rank)" name="rank" :value="$val('rank', $missao->rank?->value, 'E')"
                                 :options="array_combine(TaskRank::values(), TaskRank::values())" />
            </div>
        </x-sys.window>

        <x-sys.window label="Recorrência">
            <div class="group/rec flex flex-col gap-4">
                @if ($molde)
                    {{-- sem isto só esta ocorrência muda e a regra de recorrência fica intacta --}}
                    <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                        <input type="checkbox" name="aplicar_futuras" value="1" class="size-5 accent-sys-500" @checked(old('aplicar_futuras'))>
                        Aplicar também às próximas ocorrências e à regra
                    </label>
                @endif
                <x-sys.segmented label="Repetir" name="recorrencia" :value="$val('recorrencia', $recorrenciaAtual, 'nenhuma')"
                                 :options="['nenhuma' => 'Não', 'diaria' => 'Diária', 'semanal' => 'Semanal', 'mensal' => 'Mensal']" />

                {{-- só aparece com "Semanal" marcado (CSS :has, sem JS) --}}
                <fieldset class="hidden group-has-[[value=semanal]:checked]/rec:block">
                    <legend class="mb-1.5 text-sm font-medium text-ink-soft">Dias da semana</legend>
                    <div class="grid grid-cols-7 gap-1">
                        @foreach ($diasSemana as $i => $letra)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="recorrencia_dias[]" value="{{ $i }}" class="peer sr-only" @checked(in_array($i, $diasMarcados))>
                                <span class="chamfer chamfer-all flex h-tap items-center justify-center font-display text-sm font-semibold text-ink-soft [--ch:4px] [--ch-bg:#111A2E]
                                             peer-checked:text-white peer-checked:[--ch-bg:rgba(37,99,235,0.35)] peer-checked:[--ch-border:#3B82F6]
                                             peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-sys-glow">{{ $letra }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('recorrencia_dias')<p class="mt-1.5 text-sm text-danger-text" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <div class="hidden group-has-[[value=mensal]:checked]/rec:block">
                    <x-sys.input name="recorrencia_dia_mes" label="Todo dia" type="number" min="1" max="31" :value="$val('recorrencia_dia_mes', $molde?->day_of_month, today()->day)" inputmode="numeric" />
                </div>

                <div class="hidden group-has-[[value=diaria]:checked]/rec:block group-has-[[value=semanal]:checked]/rec:block group-has-[[value=mensal]:checked]/rec:block">
                    <x-sys.input name="termina_em" label="Termina em" type="date" :value="$terminaVal" hint="Vazio = repete para sempre" />
                </div>
            </div>
        </x-sys.window>
    </form>

    {{-- barra de ações fixa acima da bottom nav --}}
    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-2xl">
        @if ($editando)
            <form method="POST" action="{{ route('missoes.destroy', $missao) }}" data-confirm="Excluir a missão “{{ $missao->title }}”?">
                @csrf @method('DELETE')
                <x-sys.icon-button icon="trash" label="Excluir missão" type="submit" variant="outline" class="!text-danger-text" />
            </form>
            @if ($missao->status !== \App\Enums\TaskStatus::Cancelled)
                <form method="POST" action="{{ route('missoes.cancelar', $missao) }}" data-confirm="Cancelar a missão “{{ $missao->title }}”? Ela não vale XP.">
                    @csrf @method('PATCH')
                    <x-sys.icon-button icon="x" label="Cancelar missão" type="submit" variant="outline" />
                </form>
            @endif
            @if ($molde?->is_active)
                <form method="POST" action="{{ route('missoes.encerrar-serie', $missao) }}" data-confirm="Encerrar a série de “{{ $missao->title }}”? As próximas ocorrências pendentes serão removidas.">
                    @csrf @method('DELETE')
                    <x-sys.icon-button icon="repeat" label="Encerrar série" type="submit" variant="outline" class="!text-danger-text" />
                </form>
            @endif
        @endif
        <x-sys.button type="submit" form="form-missao" size="lg" icon="check" class="flex-1 glow">
            {{ $editando ? 'Salvar missão' : 'Aceitar missão' }}
        </x-sys.button>
    </div>
</x-layouts.app>
