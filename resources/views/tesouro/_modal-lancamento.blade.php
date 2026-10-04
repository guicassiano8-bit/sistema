{{--
  Modal de lançamento — @include('tesouro._modal-lancamento', ['tipo' => 'gasto'|'ganho'])
  Gasto: FAB → digitar valor → Enter (2 toques). O controller grava valor negativo quando tipo = gasto.
  Erros: validateWithBag('lancamento_' . $tipo, …) reabre o modal certo.
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
        <input type="hidden" name="tipo" value="{{ $tipo }}">
        <x-sys.input :id="'f-' . $tipo . '-valor'" name="valor" label="Valor" prefix="R$" inputmode="decimal" enterkeyhint="send"
                     placeholder="0,00" required autofocus autocomplete="off" :error="$bag->first('valor')" />
        <x-sys.input :id="'f-' . $tipo . '-descricao'" name="descricao" :label="$gasto ? 'Com o quê?' : 'De onde?'"
                     :placeholder="$gasto ? 'Ex.: Mercado' : 'Ex.: Salário'" autocomplete="off" />
        <div class="grid grid-cols-2 gap-3">
            <x-sys.select :id="'f-' . $tipo . '-categoria'" name="categoria" label="Categoria" :options="$categorias[$tipo] ?? []" />
            <x-sys.input :id="'f-' . $tipo . '-data'" name="data" label="Data" type="date" :value="today()->format('Y-m-d')" />
        </div>
    </form>
    <x-slot:footer>
        <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
        <x-sys.button type="submit" :form="$fid" :variant="$gasto ? 'danger' : 'primary'" :icon="$gasto ? 'minus' : 'plus'">
            {{ $gasto ? 'Registrar gasto' : 'Registrar ganho' }}
        </x-sys.button>
    </x-slot:footer>
</x-sys.modal>
