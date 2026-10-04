{{--
  EDITAR CONTA · GET /tesouro/contas/{conta}/edit (name: tesouro.contas.edit) · PUT tesouro.contas.update · DELETE tesouro.contas.destroy
  Dados: $conta · $saldo BigDecimal · $tipos · $temMovimentacoes bool
--}}
@php $erros = $errors->conta; @endphp

<x-layouts.app title="Editar conta">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar às contas" :href="route('tesouro.contas.index')" />
        <x-sys.page-header label="Conta" :title="$conta->name" class="min-w-0 flex-1" />
    </div>

    <x-sys.window label="Saldo atual" class="lg:max-w-xl">
        <p class="font-display text-2xl font-bold text-ink"><x-sys.money :value="(string) $saldo" /></p>
    </x-sys.window>

    <form id="form-conta" method="POST" action="{{ route('tesouro.contas.update', $conta) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Conta">
            <div class="flex flex-col gap-4">
                <x-sys.input name="name" label="Nome" :value="$conta->name" required maxlength="255" :error="$erros->first('name')" />
                <x-sys.select name="type" label="Tipo" :options="$tipos" :value="$conta->type->value" :error="$erros->first('type')" />
                <x-sys.input name="institution" label="Instituição" :value="$conta->institution" :error="$erros->first('institution')" />
                <x-sys.input name="initial_balance" label="Saldo inicial" prefix="R$" inputmode="decimal"
                             :value="number_format((float) $conta->initial_balance, 2, ',', '')" :error="$erros->first('initial_balance')" />
                <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                    <input type="checkbox" name="is_active" value="1" class="size-5 accent-sys-500" @checked(old('is_active', $conta->is_active))>
                    Conta ativa (aparece nos lançamentos)
                </label>
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('tesouro.contas.destroy', $conta) }}"
              data-confirm="{{ $temMovimentacoes ? 'Esta conta tem histórico. Ela será apenas desativada.' : 'Excluir a conta '.$conta->name.'?' }}">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir conta" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-conta" size="lg" icon="check" class="flex-1">Salvar conta</x-sys.button>
    </div>
</x-layouts.app>
