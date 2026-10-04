{{--
  TESOURO (financeiro) · GET /tesouro?aba=resumo|extrato|ativos|relatorios (name: tesouro.index)
  Hierarquia: 1. patrimônio (atributo principal) · 2. fluxo do mês · 3. renda passiva · 4. detalhes

  Dados (sempre):  $aba · $categorias (['gasto' => [valor => rótulo], 'ganho' => [...]] para os modais de lançamento) · $investimentos (para o modal de rendimento)
  Por aba — ver o topo de cada arquivo em tesouro/abas/.
  Lançamento: Transaction (amount sempre positivo; o sinal vem de type), com category carregada
  Rotas: tesouro.lancamentos.* · tesouro.investimentos.store · tesouro.rendimentos.store (as duas últimas na Parte 4)
--}}
@php
$abas = \App\Enums\FinanceTab::options();
@endphp

<x-layouts.app title="Tesouro">
    <x-sys.page-header label="Status financeiro" title="Tesouro" />

    <x-sys.segmented label="Seção do Tesouro" :items="collect($abas)->map(fn ($rotulo, $k) => [
        'label' => $rotulo, 'href' => route('tesouro.index', ['aba' => $k]), 'active' => $aba === $k,
    ])->values()->all()" class="[&_a]:px-1 [&_a]:text-xs sm:[&_a]:text-sm" />

    @include('tesouro.abas.' . $aba)

    <x-slot:fab><x-sys.fab label="Lançar gasto" icon="minus" variant="danger" modal="modal-gasto" /></x-slot:fab>

    <x-slot:modals>
        @include('tesouro._modal-lancamento', ['tipo' => 'gasto'])
        @include('tesouro._modal-lancamento', ['tipo' => 'ganho'])

        @if (Route::has('tesouro.investimentos.store'))
        {{-- NOVO INVESTIMENTO --}}
        <x-sys.modal id="modal-investimento" label="Ativos" title="Novo investimento" :open="$errors->investimento->any()">
            <form id="form-investimento" method="POST" action="{{ route('tesouro.investimentos.store') }}" data-sys-form class="group/inv flex flex-col gap-4">
                @csrf
                <x-sys.segmented label="Tipo" name="tipo" value="CDB" :options="['CDB' => 'CDB', 'FII' => 'FII']" />
                <x-sys.input name="nome" label="Nome" placeholder="Ex.: CDB Banco X · XPTO11" required :error="$errors->investimento->first('nome')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="aplicado" label="Valor aplicado" prefix="R$" inputmode="decimal" required :error="$errors->investimento->first('aplicado')" />
                    <x-sys.input id="f-inv-data" name="data" label="Data" type="date" :value="today()->format('Y-m-d')" required />
                </div>
                <div class="grid grid-cols-2 gap-3 group-has-[[value=FII]:checked]/inv:hidden">
                    <x-sys.input name="taxa" label="Taxa" placeholder="110% CDI" />
                    <x-sys.input name="vencimento" label="Vencimento" type="date" />
                </div>
                <div class="hidden group-has-[[value=FII]:checked]/inv:block">
                    <x-sys.input name="cotas" label="Cotas" type="number" min="1" inputmode="numeric" />
                </div>
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-investimento" icon="check">Registrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>

        {{-- RENDIMENTO (ganho passivo) --}}
        <x-sys.modal id="modal-rendimento" label="Ganho passivo" title="Registrar rendimento" size="sm">
            <form id="form-rendimento" method="POST" action="{{ route('tesouro.rendimentos.store') }}" data-sys-form class="flex flex-col gap-4">
                @csrf
                <x-sys.select name="investimento_id" label="Investimento" required
                              :options="$investimentos->pluck('name', 'id')->all()" placeholder="Escolha…" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input id="f-rend-valor" name="valor" label="Valor" prefix="R$" inputmode="decimal" required />
                    <x-sys.input id="f-rend-data" name="data" label="Data" type="date" :value="today()->format('Y-m-d')" required />
                </div>
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-rendimento" icon="trending-up">Registrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>
        @endif
    </x-slot:modals>
</x-layouts.app>
