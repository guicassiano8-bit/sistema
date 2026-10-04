{{--
  GERENCIAR INVESTIMENTO · GET /tesouro/investimentos/{investimento}/edit (name: tesouro.investimentos.edit)
  PUT tesouro.investimentos.update · DELETE tesouro.investimentos.destroy
  POST tesouro.investimentos.valor.store (bag "valor") · POST tesouro.investimentos.movimentos.store (bag "movimento") · DELETE tesouro.rendimentos.destroy

  Dados: $investimento (Asset) · $posicao (AssetPosition) · $movimentos e $rendimentos (mais recentes primeiro)
         $tiposDeMovimento [valor => rótulo] · $indexadores · $contas
  "Valor atual" é informado à mão (saldo do CDB no app do banco ou cotação do FII).
--}}
@php
$comCotas = $posicao->marketTraded;
$erros = $errors->investimento;
$valor = $errors->valor;
$mov = $errors->movimento;
$fmtCotas = fn ($n) => number_format((float) (string) $n, 0, ',', '.');
@endphp
<x-layouts.app title="Gerenciar investimento">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar aos ativos" :href="route('tesouro.index', ['aba' => 'ativos'])" />
        <x-sys.page-header :label="$posicao->type" :title="$investimento->name" class="min-w-0 flex-1" />
    </div>

    {{-- POSIÇÃO --}}
    <x-sys.window label="Posição" class="lg:max-w-xl">
        <dl class="grid grid-cols-3 gap-2">
            <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Aplicado</dt><dd><x-sys.money :value="(string) $posicao->invested" class="font-display text-base" /></dd></div>
            <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Atual</dt><dd><x-sys.money :value="(string) $posicao->current" class="font-display text-base font-semibold" /></dd></div>
            <div><dt class="text-2xs uppercase tracking-wider text-ink-muted">Rendeu</dt><dd><x-sys.money :value="(string) $posicao->result()" signed class="font-display text-base" /></dd></div>
        </dl>
        @if ($posicao->income->isPositive())
            <p class="mt-3 text-xs text-ink-muted">Rendeu inclui <x-sys.money :value="(string) $posicao->income" /> em rendimentos recebidos.</p>
        @endif
        @if ($comCotas)
            <p class="mt-3 text-xs text-ink-muted">
                {{ $fmtCotas($posicao->quantity) }} cotas
                @if ($posicao->lastPrice) · cotação R$ {{ number_format($posicao->lastPrice->toFloat(), 2, ',', '.') }} @else · sem cotação informada: vale o que custou @endif
            </p>
        @endif
    </x-sys.window>

    {{-- ATUALIZAR VALOR --}}
    <form method="POST" action="{{ route('tesouro.investimentos.valor.store', $investimento) }}" data-sys-form class="lg:max-w-xl">
        @csrf
        <x-sys.window :label="$comCotas ? 'Atualizar cotação' : 'Atualizar saldo'">
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input id="f-valor-valor" name="value" :label="$comCotas ? 'Preço por cota' : 'Saldo bruto'" prefix="R$" inputmode="decimal" required :error="$valor->first('value')" />
                    <x-sys.input id="f-valor-data" name="date" label="Data" type="date" :value="today()->format('Y-m-d')" required :error="$valor->first('date')" />
                </div>
                @unless ($comCotas)
                    <x-sys.input id="f-valor-liquido" name="net_balance" label="Saldo líquido" prefix="R$" inputmode="decimal" hint="Opcional: descontado o IR, se o banco mostrar." :error="$valor->first('net_balance')" />
                @endunless
                <x-sys.button type="submit" variant="secondary" icon="check">Atualizar</x-sys.button>
            </div>
        </x-sys.window>
    </form>

    {{-- MOVIMENTAR --}}
    <form method="POST" action="{{ route('tesouro.investimentos.movimentos.store', $investimento) }}" data-sys-form class="lg:max-w-xl">
        @csrf
        <x-sys.window :label="$comCotas ? 'Comprar ou vender' : 'Aportar ou resgatar'">
            <div class="flex flex-col gap-4">
                <x-sys.segmented label="Operação" name="type" :value="array_key_first($tiposDeMovimento)" :options="$tiposDeMovimento" />
                @if ($comCotas)
                    <div class="grid grid-cols-2 gap-3">
                        <x-sys.input id="f-mov-cotas" name="quantity" label="Cotas" type="number" min="1" inputmode="numeric" required :error="$mov->first('quantity')" />
                        <x-sys.input id="f-mov-preco" name="unit_price" label="Preço por cota" prefix="R$" inputmode="decimal" required :error="$mov->first('unit_price')" />
                    </div>
                    <x-sys.input id="f-mov-taxas" name="fees" label="Taxas" prefix="R$" inputmode="decimal" hint="Corretagem e outras. Opcional." :error="$mov->first('fees')" />
                @else
                    <x-sys.input id="f-mov-total" name="total" label="Valor" prefix="R$" inputmode="decimal" required :error="$mov->first('total')" />
                @endif
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input id="f-mov-data" name="date" label="Data" type="date" :value="today()->format('Y-m-d')" required :error="$mov->first('date')" />
                    <x-sys.select id="f-mov-conta" name="account_id" label="Conta" placeholder="Nenhuma" :options="$contas" :error="$mov->first('account_id')" />
                </div>
                <x-sys.button type="submit" variant="secondary" icon="check">Registrar operação</x-sys.button>
            </div>
        </x-sys.window>
    </form>

    {{-- DADOS DO ATIVO --}}
    <form id="form-inv-edit" method="POST" action="{{ route('tesouro.investimentos.update', $investimento) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Dados do ativo">
            <div class="flex flex-col gap-4">
                <x-sys.input name="name" label="Nome" :value="$investimento->name" required :error="$erros->first('name')" />
                <x-sys.input name="institution" label="Instituição" :value="$investimento->institution" :error="$erros->first('institution')" />
                @if ($comCotas)
                    <x-sys.input name="ticker" label="Ticker" :value="$investimento->ticker" maxlength="12" required :error="$erros->first('ticker')" />
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <x-sys.select name="indexer" label="Indexador" placeholder="Nenhum" :options="$indexadores" :value="$investimento->indexer?->value" :error="$erros->first('indexer')" />
                        <x-sys.input name="rate" label="Taxa" inputmode="decimal" :value="$investimento->rate !== null ? rtrim(rtrim(number_format((float) $investimento->rate, 4, ',', ''), '0'), ',') : null" :error="$erros->first('rate')" />
                    </div>
                    <x-sys.input name="maturity_date" label="Vencimento" type="date" :value="$investimento->maturity_date?->format('Y-m-d')" :error="$erros->first('maturity_date')" />
                @endif
                <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                    <input type="checkbox" name="is_active" value="1" class="size-5 accent-sys-500" @checked(old('is_active', $investimento->is_active))>
                    Ativo (aparece na carteira)
                </label>
            </div>
        </x-sys.window>
    </form>

    {{-- HISTÓRICO --}}
    <section aria-labelledby="hist-mov" class="flex flex-col gap-2 lg:max-w-xl">
        <h2 id="hist-mov" class="sys-label">Movimentos</h2>
        <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
            @forelse ($movimentos as $m)
                <li class="flex min-h-12 items-center gap-3 px-4 py-2">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm text-ink">{{ $m->type->label() }}@if ($m->quantity !== null) · {{ $fmtCotas($m->quantity) }} cotas @endif</span>
                        <span class="block text-xs text-ink-muted">
                            {{ $m->date->format('d/m/Y') }}@if ($m->realized_profit !== null) · lucro realizado <x-sys.money :value="$m->realized_profit" signed />@endif
                        </span>
                    </span>
                    <x-sys.money :value="$m->type->isInflow() ? '-'.$m->total : $m->total" signed class="font-display text-sm font-semibold" />
                </li>
            @empty
                <li class="px-4 py-4 text-center text-sm text-ink-soft">Nenhum movimento.</li>
            @endforelse
        </x-sys.window>
    </section>

    <section aria-labelledby="hist-rend" class="flex flex-col gap-2 lg:max-w-xl">
        <h2 id="hist-rend" class="sys-label">Rendimentos</h2>
        <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
            @forelse ($rendimentos as $r)
                <li class="flex min-h-12 items-center gap-3 px-4 py-2">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm text-ink">{{ $r->type->label() }}</span>
                        <span class="block text-xs text-ink-muted">{{ $r->payment_date->format('d/m/Y') }}</span>
                    </span>
                    <x-sys.money :value="$r->amount" signed class="font-display text-sm font-semibold" />
                    <form method="POST" action="{{ route('tesouro.rendimentos.destroy', $r) }}" data-confirm="Excluir este rendimento?">
                        @csrf @method('DELETE')
                        <x-sys.icon-button icon="trash" label="Excluir rendimento" type="submit" variant="ghost" class="!text-danger-text" />
                    </form>
                </li>
            @empty
                <li class="px-4 py-4 text-center text-sm text-ink-soft">Nenhum rendimento registrado.</li>
            @endforelse
        </x-sys.window>
    </section>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('tesouro.investimentos.destroy', $investimento) }}"
              data-confirm="Excluir {{ $investimento->name }}? Se já teve rendimentos ou saídas, ele será apenas encerrado.">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir investimento" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-inv-edit" size="lg" icon="check" class="flex-1">Salvar dados</x-sys.button>
    </div>
</x-layouts.app>
