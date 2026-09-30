{{--
  STATUS DO JOGADOR (dashboard) · GET / (name: dashboard)
  Hierarquia: 1. missões de hoje · 2. progresso (HUD) · 3. alerta lendário · 4. Tesouro · 5. próxima recompensa

  Dados do controller:
    $missoesHoje      Collection<Task>  (atrasadas + hoje; pendentes primeiro)
    $hojeFeitas       int      $hojeTotal  int (só as agendadas para hoje)
    $xpHoje           int (XP líquido de hoje)
  Ainda não fornecidos (fase 2):
    $lendarios        Collection<ShoppingItem>  (raridade urgente, não comprados)
    $tesouro          array    patrimonio, delta_pct, ganhos_mes, gastos_mes, passivo_mes
    $proximaRecompensa Reward|null  (a mais barata que o jogador ainda não pode pagar)
--}}
<x-layouts.app title="Status">
    <x-sys.page-header  title="Status do jogador" />

    <div class="grid gap-5 lg:grid-cols-12">
        {{-- 1 · MISSÕES DE HOJE --}}
        <x-sys.window label="Missões de hoje" class="lg:col-span-7" variant="active" data-progress-scope>
           <x-slot:actions>
                <span class="px-2 font-display text-sm font-semibold tabular text-ink-soft"><span data-progress-done>{{ $hojeFeitas }}</span>/<span data-progress-total>{{ $hojeTotal }}</span></span>
            </x-slot:actions>

            <x-sys.xp-bar data-progress-bar :current="$hojeFeitas" :max="max($hojeTotal, 1)" unit="missões" label="Missões concluídas hoje" :show-values="false" />
            <p class="mb-3 mt-1.5 text-xs text-ink-soft">+<span data-progress-xp class="font-semibold tabular text-sys-glow">{{ $xpHoje }}</span> XP hoje</p>

            @if ($missoesHoje->isEmpty())
                <div class="flex flex-col items-center gap-3 py-6 text-center">
                    <p class="text-sm text-ink-soft">Nenhuma missão para hoje. O Sistema aguarda.</p>
                    <x-sys.button variant="secondary" icon="plus" data-modal-open="modal-missao">Nova missão</x-sys.button>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach ($missoesHoje->take(6) as $m)
                        @include('missoes._card', ['m' => $m])
                    @endforeach
                </div>
            @endif

            <x-slot:footer>
                <x-sys.button variant="ghost" :href="route('missoes.index')" icon-right="arrow-right" class="w-full">
                    Ver todas as missões
                </x-sys.button>
            </x-slot:footer>

        </x-sys.window>

        <div class="flex flex-col gap-5 lg:col-span-5">
            
        </div>
    </div>

    {{-- sair: no celular fica aqui (tablet/desktop: rodapé do rail/sidebar) --}}
    <form method="POST" action="{{ Route::has('login.destroy') ? route('login.destroy') : '#' }}" class="self-center md:hidden">
        @csrf
        <x-sys.button type="submit" variant="ghost" icon="logout" class="!text-ink-muted">Sair do Sistema</x-sys.button>
    </form>

    <x-slot:fab><x-sys.fab label="Nova missão" modal="modal-missao" /></x-slot:fab>
</x-layouts.app>
