{{--
  Modal de lançamento — @include('tesouro._modal-lancamento', ['tipo' => 'gasto'|'ganho'])
  Gasto: FAB → digitar valor → Enter (2 toques). Só o valor é obrigatório; categoria e conta têm padrão.
  Erros: a Form Request usa o bag lancamento_gasto / lancamento_ganho e reabre o modal certo.
--}}
@php
$gasto = $tipo === 'gasto';
$bag = $errors->{'lancamento_' . $tipo};
$fid = 'form-' . $tipo;
@endphp

<x-sys.modal :id="'modal-' . $tipo" label="Tesouro" :title="$gasto ? 'Lançar gasto' : 'Lançar ganho'"
             :variant="$gasto ? 'danger' : 'default'" :open="$bag->any() || request('acao') === $tipo">
    <form id="{{ $fid }}" method="POST" action="{{ route('tesouro.lancamentos.store') }}" data-sys-form class="flex flex-col gap-4">
        @csrf
        <input type="hidden" name="type" value="{{ $gasto ? 'expense' : 'income' }}">
        <x-sys.input :id="'f-' . $tipo . '-valor'" name="amount" label="Valor" prefix="R$" inputmode="decimal" enterkeyhint="send"
                     placeholder="0,00" required autofocus autocomplete="off" :error="$bag->first('amount')" />
        <x-sys.input :id="'f-' . $tipo . '-descricao'" name="description" :label="$gasto ? 'Com o quê?' : 'De onde?'"
                     :placeholder="$gasto ? 'Ex.: Mercado' : 'Ex.: Salário'" autocomplete="off" :error="$bag->first('description')" />
        <div class="grid grid-cols-2 gap-3">
            <x-sys.select :id="'f-' . $tipo . '-categoria'" name="finance_category_id" label="Categoria"
                          :options="$categorias[$tipo] ?? []" :error="$bag->first('finance_category_id')" />
            <x-sys.input :id="'f-' . $tipo . '-data'" name="date" label="Data" type="date" :value="today()->format('Y-m-d')" :error="$bag->first('date')" />
        </div>
        @if ($bag->has('account_id'))
            <p class="text-sm text-danger-text">{{ $bag->first('account_id') }}</p>
        @endif
    </form>
    <x-slot:footer>
        <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
        <x-sys.button type="submit" :form="$fid" :variant="$gasto ? 'danger' : 'primary'" :icon="$gasto ? 'minus' : 'plus'">
            {{ $gasto ? 'Registrar gasto' : 'Registrar ganho' }}
        </x-sys.button>
    </x-slot:footer>
</x-sys.modal>
