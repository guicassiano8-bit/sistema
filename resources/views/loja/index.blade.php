{{--
  LOJA DO SISTEMA · GET /recompensas?aba=loja|historico (name: recompensas.index)
  Hierarquia: 1. saldo · 2. o que dá para trocar agora · 3. metas bloqueadas · 4. histórico

  Dados:
    $aba           'loja'|'historico'
    $recompensas   Collection<Reward> (name, description, cost, rank)
                   ordem: disponíveis (mais baratas) → bloqueadas (mais baratas)
    $historico     Collection de ['mes' => Carbon, 'total' => int, 'trocas' => Collection<RewardRedemption>]  (reward->name, cost_paid, redeemed_at)
  Rotas: recompensas.trocar (POST {recompensa}) · recompensas.store · recompensas.edit · recompensas.desfazer (DELETE {resgate})
--}}
<x-layouts.app title="Loja">
    <x-sys.page-header label="Loja do Sistema" title="Loja">
        <x-slot:actions>
            <x-sys.icon-button icon="plus" label="Cadastrar recompensa" variant="outline" data-modal-open="modal-recompensa" />
        </x-slot:actions>
    </x-sys.page-header>

    {{-- 1 · SALDO --}}
    <x-sys.window as="div" padding="sm" class="[--ch-border:rgba(251,191,36,0.45)]">
        <div class="flex items-center justify-between gap-3 px-1">
            <div>
                <p class="sys-label text-gold">Saldo disponível</p>
                <p class="flex items-baseline gap-2 font-display text-4xl font-bold tabular text-gold drop-shadow-[0_0_10px_rgba(251,191,36,0.35)]">
                    <span data-player-gold>{{ number_format($jogador->ouro, 0, ',', '.') }}</span>
                    <span class="text-sm tracking-wider">OURO</span>
                </p>
            </div>
            <x-sys.icon name="coins" size="size-10" :stroke="1.5" class="text-gold/70" />
        </div>
    </x-sys.window>

    @error('recompensa')
        <p class="text-sm text-danger-text" role="alert">{{ $message }}</p>
    @enderror

    <x-sys.segmented label="Seção da loja" :items="[
        ['label' => 'Loja',      'href' => route('recompensas.index'),                         'active' => $aba === 'loja'],
        ['label' => 'Histórico', 'href' => route('recompensas.index', ['aba' => 'historico']), 'active' => $aba === 'historico'],
    ]" />

    @if ($aba === 'loja')
        @if ($recompensas->isEmpty())
            <x-sys.window as="div" padding="lg">
                <div class="flex flex-col items-center gap-3 py-4 text-center">
                    <x-sys.icon name="gift" size="size-10" :stroke="1.5" class="text-ink-faint" />
                    <p class="text-sm text-ink-soft">A Loja está vazia. Cadastre as recompensas que você quer conquistar.</p>
                    <x-sys.button variant="secondary" icon="plus" data-modal-open="modal-recompensa">Cadastrar recompensa</x-sys.button>
                </div>
            </x-sys.window>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($recompensas as $r)
                    <x-sys.reward-card
                        :name="$r->name" :description="$r->description" :cost="$r->cost" :balance="$jogador->ouro"
                        :tier="$r->rank->value"
                        :redeem-url="route('recompensas.trocar', $r)"
                        :edit-url="route('recompensas.edit', $r)" />
                @endforeach
            </div>
        @endif
    @else
        {{-- HISTÓRICO --}}
        @forelse ($historico as $grupo)
            <section aria-labelledby="hist-{{ $grupo['mes']->format('Ym') }}" class="flex flex-col gap-2">
                <h2 id="hist-{{ $grupo['mes']->format('Ym') }}" class="flex items-baseline justify-between">
                    <span class="sys-label">{{ $grupo['mes']->translatedFormat('F Y') }}</span>
                    <span class="text-xs tabular text-ink-soft">{{ $grupo['trocas']->count() }} trocas · <span class="text-gold">−{{ number_format($grupo['total'], 0, ',', '.') }} Ouro</span></span>
                </h2>
                <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
                    @foreach ($grupo['trocas'] as $t)
                        <li class="flex min-h-14 items-center gap-3 px-4 py-2">
                            <x-sys.icon name="gift" size="size-4" class="text-sys-400" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-base text-ink">{{ $t->reward->name }}</span>
                                <time datetime="{{ $t->redeemed_at->toIso8601String() }}" class="text-xs text-ink-muted">{{ $t->redeemed_at->translatedFormat('d/m · H:i') }}</time>
                            </span>
                            <span class="font-display text-sm font-semibold tabular text-gold">−{{ number_format($t->cost_paid, 0, ',', '.') }}</span>
                            <form method="POST" action="{{ route('recompensas.desfazer', $t) }}" data-sys-form
                                  data-confirm="Desfazer o resgate de “{{ $t->reward->name }}”? Você recebe {{ number_format($t->cost_paid, 0, ',', '.') }} Ouro de volta e o registro é apagado."
                                  data-confirm-ok="Desfazer">
                                @csrf
                                @method('DELETE')
                                <x-sys.icon-button type="submit" icon="undo" :label="'Desfazer resgate de '.$t->reward->name" />
                            </form>
                        </li>
                    @endforeach
                </x-sys.window>
            </section>
        @empty
            <p class="py-8 text-center text-sm text-ink-soft">Nenhuma troca ainda. Conclua missões para juntar Ouro.</p>
        @endforelse
    @endif

    <x-slot:modals>
        <x-sys.modal id="modal-recompensa" label="Loja" title="Nova recompensa" :open="$errors->recompensa->any()">
            <form id="form-recompensa" method="POST" action="{{ route('recompensas.store') }}" data-sys-form class="flex flex-col gap-4">
                @csrf
                <x-sys.input name="name" label="Recompensa" placeholder="Ex.: Jantar fora" required autofocus :error="$errors->recompensa->first('name')" />
                <x-sys.input name="description" label="Descrição" placeholder="Opcional" :error="$errors->recompensa->first('description')" />
                <x-sys.input name="cost" label="Custo" type="number" min="1" step="1" suffix="OURO" inputmode="numeric" required :error="$errors->recompensa->first('cost')" />
                <x-sys.segmented label="Resgate" name="is_repeatable" value="1" :options="['1' => 'Repetível', '0' => 'Única vez']" />
                <x-sys.segmented label="Peso (rank)" name="rank" value="C"
                                 :options="array_combine(\App\Enums\TaskRank::values(), \App\Enums\TaskRank::values())" />
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-recompensa" icon="check">Cadastrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>
    </x-slot:modals>
</x-layouts.app>
