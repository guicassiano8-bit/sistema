{{--
  CRIAR / EDITAR MISSÃO (formulário completo)
  GET /missoes/criar (name: missoes.create — aceita ?titulo=&xp=&data=&recorrencia= vindos do modal rápido)
  GET /missoes/{missao}/editar (name: missoes.edit)
  Dados: $missao (Missao — new Missao() no create)
  Campos: titulo, descricao, data, horario, xp, ouro, rank, recorrencia (nenhuma|diaria|semanal|mensal),
          recorrencia_dias (array 0–6, semanal), recorrencia_dia_mes (1–31, mensal), termina_em
--}}
@php
$editando = $missao->exists;
$val = fn ($campo, $padrao = null) => old($campo, request($campo, $missao->{$campo} ?? $padrao));
$diasSemana = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
$dataVal = $val('data', today());
$dataVal = $dataVal instanceof \DateTimeInterface ? $dataVal->format('Y-m-d') : $dataVal;
$terminaVal = old('termina_em', $missao->termina_em);
$terminaVal = $terminaVal instanceof \DateTimeInterface ? $terminaVal->format('Y-m-d') : $terminaVal;
$diasMarcados = collect(old('recorrencia_dias', $missao->recorrencia_dias ?? []))->map(fn ($d) => (int) $d)->all();
@endphp

<x-layouts.app :title="$editando ? 'Editar missão' : 'Nova missão'">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar para missões" :href="route('missoes.index')" />
        <x-sys.page-header :label="$editando ? 'Editar' : 'Nova'" :title="$editando ? $missao->titulo : 'Nova missão'" class="min-w-0 flex-1" />
    </div>

    <form id="form-missao" method="POST" data-sys-form
          action="{{ $editando ? route('missoes.update', $missao) : route('missoes.store') }}"
          class="group/form flex flex-col gap-5 lg:max-w-2xl">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-sys.window label="Objetivo">
            <div class="flex flex-col gap-4">
                <x-sys.input name="titulo" label="Missão" :value="$val('titulo')" required autofocus maxlength="120" />
                <div class="flex flex-col gap-1.5">
                    <label for="f-descricao" class="text-sm font-medium text-ink-soft">Descrição <span class="text-ink-muted">(opcional)</span></label>
                    <textarea id="f-descricao" name="descricao" rows="3"
                              class="w-full resize-y rounded-sm border-0 bg-surface-raised px-3 py-2.5 text-base text-ink outline-none ring-1 ring-line placeholder:text-ink-faint focus:ring-2 focus:ring-sys-glow">{{ $val('descricao') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="data" label="Data" type="date" :value="$dataVal" required />
                    <x-sys.input name="horario" label="Horário" type="time" :value="$val('horario') ? substr($val('horario'), 0, 5) : null" hint="Opcional" />
                </div>
            </div>
        </x-sys.window>

        <x-sys.window label="Recompensa">
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="xp" label="Experiência" type="number" min="0" step="5" suffix="XP" :value="$val('xp', 30)" required inputmode="numeric" />
                    <x-sys.input name="ouro" label="Ouro" type="number" min="0" step="5" suffix="OURO" :value="$val('ouro', 0)" inputmode="numeric" />
                </div>
                <x-sys.segmented label="Dificuldade (rank)" name="rank" :value="$val('rank', 'E')"
                                 :options="['E' => 'E', 'D' => 'D', 'C' => 'C', 'B' => 'B', 'A' => 'A', 'S' => 'S']" />
            </div>
        </x-sys.window>

        <x-sys.window label="Recorrência">
            <div class="group/rec flex flex-col gap-4">
                <x-sys.segmented label="Repetir" name="recorrencia" :value="$val('recorrencia') ?? 'nenhuma'"
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
                </fieldset>

                <div class="hidden group-has-[[value=mensal]:checked]/rec:block">
                    <x-sys.input name="recorrencia_dia_mes" label="Todo dia" type="number" min="1" max="31" :value="$val('recorrencia_dia_mes', today()->day)" inputmode="numeric" />
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
            <form method="POST" action="{{ route('missoes.destroy', $missao) }}" data-confirm="Excluir a missão “{{ $missao->titulo }}”?">
                @csrf @method('DELETE')
                <x-sys.icon-button icon="trash" label="Excluir missão" type="submit" variant="outline" class="!text-danger-text" />
            </form>
        @endif
        <x-sys.button type="submit" form="form-missao" size="lg" icon="check" class="flex-1 glow">
            {{ $editando ? 'Salvar missão' : 'Aceitar missão' }}
        </x-sys.button>
    </div>
</x-layouts.app>
