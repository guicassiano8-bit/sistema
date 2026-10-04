{{--
  TESOURO (financeiro) · GET /tesouro?aba=resumo|extrato|ativos|relatorios (name: tesouro.index)
  Hierarquia: 1. patrimônio (atributo principal) · 2. fluxo do mês · 3. renda passiva · 4. detalhes

  Dados (sempre):  $aba · $categorias (['gasto' => [id => nome], 'ganho' => [...]] para os modais de lançamento)
                   $investimentos (Assets ativos, para o modal de rendimento) · $contas [id => nome] · $indexadores [valor => rótulo]
  Por aba — ver o topo de cada arquivo em tesouro/abas/.
  Lançamento: Transaction (amount sempre positivo; o sinal vem de type), com category carregada
  Rotas: tesouro.lancamentos.* · tesouro.investimentos.* · tesouro.rendimentos.store
--}}
@php
$abas = \App\Enums\FinanceTab::options();
$inv = $errors->investimento;
$rend = $errors->rendimento;
@endphp

<x-layouts.app title="Tesouro">
    <x-sys.page-header label="Status financeiro" title="Tesouro" />

    <x-sys.segmented label="Seção do Tesouro" :items="collect($abas)->map(fn ($rotulo, $k) => [
        'label' => $rotulo, 'href' => route('tesouro.index', ['aba' => $k]), 'active' => $aba === $k,
    ])->values()->all()" class="[&_a]:px-1 [&_a]:text-xs sm:[&_a]:text-sm" />

    @include('tesouro.abas.' . $aba)

    <nav aria-label="Cadastros" class="grid grid-cols-2 gap-2">
        <x-sys.button variant="ghost" icon="coins" :href="route('tesouro.contas.index')">Contas</x-sys.button>
        <x-sys.button variant="ghost" icon="gem" :href="route('tesouro.categorias.index')">Categorias</x-sys.button>
    </nav>

    <x-slot:fab><x-sys.fab label="Lançar gasto" icon="minus" variant="danger" modal="modal-gasto" /></x-slot:fab>

    <x-slot:modals>
        @include('tesouro._modal-lancamento', ['tipo' => 'gasto'])
        @include('tesouro._modal-lancamento', ['tipo' => 'ganho'])

        {{-- NOVO INVESTIMENTO · o segmentado "type" esconde/mostra os campos de CDB ou FII via CSS --}}
        <x-sys.modal id="modal-investimento" label="Ativos" title="Novo investimento" :open="$inv->any()">
            <form id="form-investimento" method="POST" action="{{ route('tesouro.investimentos.store') }}" data-sys-form class="group/inv flex flex-col gap-4">
                @csrf
                <x-sys.segmented label="Tipo" name="type" value="CDB" :options="['CDB' => 'CDB', 'FII' => 'FII']" />
                <x-sys.input id="f-inv-nome" name="name" label="Nome" placeholder="Ex.: CDB Banco X · Maxi Renda" required autocomplete="off" :error="$inv->first('name')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input id="f-inv-valor" name="amount" label="Valor aplicado" prefix="R$" inputmode="decimal" required :error="$inv->first('amount')" />
                    <x-sys.input id="f-inv-data" name="date" label="Data" type="date" :value="today()->format('Y-m-d')" required :error="$inv->first('date')" />
                </div>
                <div class="flex flex-col gap-4 group-has-[[value=FII]:checked]/inv:hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <x-sys.select id="f-inv-indexador" name="indexer" label="Indexador" placeholder="Nenhum" :options="$indexadores" :error="$inv->first('indexer')" />
                        <x-sys.input id="f-inv-taxa" name="rate" label="Taxa" inputmode="decimal" placeholder="110" hint="% do CDI, IPCA + % ou % a.a." :error="$inv->first('rate')" />
                    </div>
                    <x-sys.input id="f-inv-vencimento" name="maturity_date" label="Vencimento" type="date" :error="$inv->first('maturity_date')" />
                </div>
                <div class="hidden flex-col gap-4 group-has-[[value=FII]:checked]/inv:flex">
                    <div class="grid grid-cols-2 gap-3">
                        <x-sys.input id="f-inv-ticker" name="ticker" label="Ticker" placeholder="MXRF11" maxlength="12" autocomplete="off" :error="$inv->first('ticker')" />
                        <x-sys.input id="f-inv-cotas" name="quantity" label="Cotas" type="number" min="1" inputmode="numeric" :error="$inv->first('quantity')" />
                    </div>
                </div>
                <x-sys.select id="f-inv-conta" name="account_id" label="Debitar de uma conta?" placeholder="Não debitar de nenhuma" :options="$contas"
                              hint="Escolha uma conta se o dinheiro saiu dela." :error="$inv->first('account_id')" />
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-investimento" icon="check">Registrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>

        {{-- RENDIMENTO (ganho passivo) --}}
        <x-sys.modal id="modal-rendimento" label="Ganho passivo" title="Registrar rendimento" size="sm" :open="$rend->any()">
            <form id="form-rendimento" method="POST" action="{{ route('tesouro.rendimentos.store') }}" data-sys-form class="flex flex-col gap-4">
                @csrf
                <x-sys.select id="f-rend-ativo" name="asset_id" label="Investimento" required
                              :options="$investimentos->pluck('name', 'id')->all()" placeholder="Escolha…" :error="$rend->first('asset_id')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input id="f-rend-valor" name="amount" label="Valor" prefix="R$" inputmode="decimal" required :error="$rend->first('amount')" />
                    <x-sys.input id="f-rend-data" name="payment_date" label="Data" type="date" :value="today()->format('Y-m-d')" required :error="$rend->first('payment_date')" />
                </div>
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-rendimento" icon="trending-up">Registrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>
    </x-slot:modals>
</x-layouts.app>
