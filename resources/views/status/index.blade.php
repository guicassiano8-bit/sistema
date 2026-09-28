{{--
  STATUS DO JOGADOR (dashboard) · GET / (name: dashboard)
  Hierarquia: 1. missões de hoje · 2. progresso (HUD) · 3. alerta lendário · 4. Tesouro · 5. próxima recompensa

  Dados do controller:
    $missoesHoje      Collection<Missao>  (atrasadas + hoje; pendentes primeiro)
    $hojeFeitas       int      $hojeTotal  int      $xpHoje  int (XP já ganho hoje)
    $lendarios        Collection<Item>    (raridade urgente, não comprados)
    $tesouro          array    patrimonio, delta_pct, ganhos_mes, gastos_mes, passivo_mes
    $proximaRecompensa Recompensa|null    (a mais barata que o jogador ainda não pode pagar)
--}}
<x-layouts.app title="Status">
    <x-sys.page-header  title="Status do jogador" />

    <div class="grid gap-5 lg:grid-cols-12">
        {{-- 1 · MISSÕES DE HOJE --}}
        <x-sys.window label="Missões de hoje" class="lg:col-span-7" variant="active" data-progress-scope>
           

            <x-slot:footer>
           
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
