{{--
  CONTAS · GET /tesouro/contas (name: tesouro.contas.index)
  Dados: $contas Collection de ['conta' => Account, 'saldo' => BigDecimal] (ativas primeiro) · $total BigDecimal (só ativas) · $tipos [valor => rótulo]
  Rotas: tesouro.contas.store (POST, bag "conta") · tesouro.contas.edit
--}}
@php $bag = $errors->conta; @endphp

<x-layouts.app title="Contas">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar ao Tesouro" :href="route('tesouro.index')" />
        <x-sys.page-header label="Tesouro" title="Contas" class="min-w-0 flex-1">
            <x-slot:actions>
                <x-sys.icon-button icon="plus" label="Nova conta" variant="outline" data-modal-open="modal-conta" />
            </x-slot:actions>
        </x-sys.page-header>
    </div>

    <x-sys.window label="Saldo em contas">
        <p class="font-display text-3xl font-bold text-ink"><x-sys.money :value="(string) $total" /></p>
        <p class="mt-1 text-xs text-ink-muted">Soma das contas ativas, sem os investimentos.</p>
    </x-sys.window>

    <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
        @forelse ($contas as $c)
            @php $conta = $c['conta']; @endphp
            <li>
                <a href="{{ route('tesouro.contas.edit', $conta) }}" class="flex min-h-14 items-center gap-3 px-4 py-2 hover:bg-sys-400/5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-sys-400/10 text-sys-400"><x-sys.icon name="coins" size="size-4" /></span>
                    <span class="min-w-0 flex-1">
                        <span @class(['block truncate text-base', 'text-ink' => $conta->is_active, 'text-ink-muted' => ! $conta->is_active])>{{ $conta->name }}</span>
                        <span class="block truncate text-xs text-ink-muted">
                            {{ $conta->type->label() }}@if ($conta->institution) · {{ $conta->institution }}@endif @unless ($conta->is_active) · desativada @endunless
                        </span>
                    </span>
                    <x-sys.money :value="(string) $c['saldo']" @class(['font-display text-sm font-semibold', 'opacity-60' => ! $conta->is_active]) />
                </a>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-ink-soft">Nenhuma conta ainda. Cadastre de onde o dinheiro sai e entra.</li>
        @endforelse
    </x-sys.window>

    <x-slot:modals>
        <x-sys.modal id="modal-conta" label="Contas" title="Nova conta" :open="$bag->any()">
            <form id="form-conta" method="POST" action="{{ route('tesouro.contas.store') }}" data-sys-form class="flex flex-col gap-4">
                @csrf
                <x-sys.input name="name" label="Nome" placeholder="Ex.: Nubank, Carteira" required autofocus autocomplete="off" :error="$bag->first('name')" />
                <x-sys.select name="type" label="Tipo" :options="$tipos" :error="$bag->first('type')" />
                <x-sys.input name="institution" label="Instituição" placeholder="Opcional" autocomplete="off" :error="$bag->first('institution')" />
                <x-sys.input name="initial_balance" label="Saldo inicial" prefix="R$" inputmode="decimal" placeholder="0,00"
                             hint="Quanto havia na conta antes de começar a lançar aqui." :error="$bag->first('initial_balance')" />
            </form>
            <x-slot:footer>
                <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
                <x-sys.button type="submit" form="form-conta" icon="check">Cadastrar</x-sys.button>
            </x-slot:footer>
        </x-sys.modal>
    </x-slot:modals>
</x-layouts.app>
