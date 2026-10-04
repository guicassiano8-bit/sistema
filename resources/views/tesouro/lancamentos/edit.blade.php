{{-- EDITAR LANÇAMENTO · GET /tesouro/lancamentos/{lancamento}/edit (name: tesouro.lancamentos.edit) · PUT tesouro.lancamentos.update · DELETE tesouro.lancamentos.destroy --}}
@php
$gasto = $lancamento->type === \App\Enums\TransactionType::Expense;
$erros = $errors->lancamento;
@endphp
<x-layouts.app :title="$gasto ? 'Editar gasto' : 'Editar ganho'">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar ao extrato" :href="route('tesouro.index', ['aba' => 'extrato', 'mes' => $lancamento->date->format('Y-m')])" />
        <x-sys.page-header :label="$gasto ? 'Gasto' : 'Ganho'" :title="$lancamento->description" class="min-w-0 flex-1" />
    </div>

    <form id="form-lancamento" method="POST" action="{{ route('tesouro.lancamentos.update', $lancamento) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Lançamento">
            <div class="flex flex-col gap-4">
                <x-sys.input name="amount" label="Valor" prefix="R$" inputmode="decimal" :value="number_format((float) $lancamento->amount, 2, ',', '')" required :error="$erros->first('amount')" />
                <x-sys.input name="description" label="Descrição" :value="$lancamento->description" :error="$erros->first('description')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.select name="finance_category_id" label="Categoria" :options="$categorias" :value="$lancamento->finance_category_id" :error="$erros->first('finance_category_id')" />
                    <x-sys.input name="date" label="Data" type="date" :value="$lancamento->date->format('Y-m-d')" required :error="$erros->first('date')" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.select name="account_id" label="Conta" :options="$contas" :value="$lancamento->account_id" :error="$erros->first('account_id')" />
                    <x-sys.select name="payment_method" label="Forma de pagamento" placeholder="Não informada" :options="$formasDePagamento" :value="$lancamento->payment_method?->value" :error="$erros->first('payment_method')" />
                </div>
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('tesouro.lancamentos.destroy', $lancamento) }}" data-confirm="Excluir “{{ $lancamento->description }}”?">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir lançamento" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-lancamento" size="lg" icon="check" class="flex-1">Salvar</x-sys.button>
    </div>
</x-layouts.app>
