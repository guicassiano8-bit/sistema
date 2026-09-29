{{--
  Modais de missão (criar rápido + transferir). Inclua no slot "modals" das telas Status e Missões:
  <x-slot:modals>@include('missoes._modais', ['dataPadrao' => $data ?? today()])</x-slot:modals>
  Controller do store rápido: $request->validateWithBag('missaoRapida', [...]) → o modal reabre sozinho com o erro.
--}}
@php $dataPadrao = ($dataPadrao ?? today())->format('Y-m-d'); @endphp

{{-- CRIAR RÁPIDO — FAB → digitar → Enter (2 toques) --}}
<x-sys.modal id="modal-missao" label="Nova missão" title="Aceitar missão" :open="$errors->missaoRapida->any() || request('acao') === 'nova-missao'">
    <form id="form-missao-rapida" method="POST" action="{{ route('missoes.store') }}" data-sys-form class="flex flex-col gap-4">
        @csrf
        <x-sys.input name="titulo" label="Missão" placeholder="Ex.: Ler 20 páginas" required autofocus
                     autocomplete="off" enterkeyhint="send" :error="$errors->missaoRapida->first('titulo')" />

        <x-sys.segmented label="Recompensa (XP)" name="xp" value="30"
                         :options="[10 => '10', 30 => '30', 50 => '50', 100 => '100']" />

        <div class="grid grid-cols-2 gap-3">
            <x-sys.input name="data" label="Data" type="date" :value="$dataPadrao" />
            <x-sys.select name="recorrencia" label="Repetir" placeholder="Não repete"
                          :options="['diaria' => 'Todo dia', 'semanal' => 'Toda semana']" />
        </div>
    </form>
    <x-slot:footer>
        <x-sys.button variant="ghost" type="submit" form="form-missao-rapida"
                      formaction="{{ route('missoes.create') }}" formmethod="get" formnovalidate>Mais opções</x-sys.button>
        <x-sys.button type="submit" form="form-missao-rapida" icon="check">Aceitar</x-sys.button>
    </x-slot:footer>
</x-sys.modal>

{{-- TRANSFERIR — aberto por "Escolher outro dia…" no menu ⋮ (o JS preenche action e título) --}}
<x-sys.modal id="modal-transferir" label="Transferir" title="Mover missão" size="sm">
    <form id="form-transferir" method="POST" action="#" data-transfer-form data-sys-form class="flex flex-col gap-4">
        @csrf @method('PATCH')
        <p class="text-sm text-ink-soft">Missão: <span data-transfer-title class="font-medium text-ink"></span></p>
        <div class="grid grid-cols-3 gap-2">
            <x-sys.button variant="secondary" class="!px-2" data-set-date="1" data-target="#f-transferir-data">Amanhã</x-sys.button>
            <x-sys.button variant="secondary" class="!px-2" data-set-date="segunda" data-target="#f-transferir-data">Segunda</x-sys.button>
            <x-sys.button variant="secondary" class="!px-2" data-set-date="7" data-target="#f-transferir-data">+7 dias</x-sys.button>
        </div>
        <x-sys.input id="f-transferir-data" name="data" label="Nova data" type="date" :value="today()->addDay()->format('Y-m-d')" required />
    </form>
    <x-slot:footer>
        <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
        <x-sys.button type="submit" form="form-transferir" icon="arrow-right">Mover</x-sys.button>
    </x-slot:footer>
</x-sys.modal>
