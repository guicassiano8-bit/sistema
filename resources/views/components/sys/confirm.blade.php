{{--
  Diálogo de confirmação único. Coloque UMA vez no layout: <x-sys.confirm />
  Qualquer <form data-confirm="Pergunta?" data-confirm-ok="Excluir"> passa por ele antes de enviar.
  (Se o texto do botão tiver "Excluir"/"Apagar", o botão vira vermelho.)
--}}
<x-sys.modal id="sys-confirm" label="Confirmação" title="Tem certeza?" size="sm">
    <p data-confirm-message class="text-base text-ink-soft"></p>
    <x-slot:footer>
        <x-sys.button variant="ghost" data-modal-close>Cancelar</x-sys.button>
        <x-sys.button data-confirm-accept="default">Confirmar</x-sys.button>
        <x-sys.button variant="danger" icon="trash" data-confirm-accept="danger" class="hidden">Excluir</x-sys.button>
    </x-slot:footer>
</x-sys.modal>
