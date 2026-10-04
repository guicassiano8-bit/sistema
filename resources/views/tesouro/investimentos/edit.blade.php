{{--
  EDITAR INVESTIMENTO · GET /tesouro/investimentos/{investimento}/editar (name: tesouro.investimentos.edit)
  PUT tesouro.investimentos.update · DELETE tesouro.investimentos.destroy
  "atual" é atualizado à mão (valor de mercado / saldo do CDB no app do banco).
--}}
@php
$venc = old('vencimento', $investimento->vencimento);
$venc = $venc instanceof \DateTimeInterface ? $venc->format('Y-m-d') : $venc;
@endphp
<x-layouts.app title="Editar investimento">
    <div class="flex items-center gap-2">
        <x-sys.icon-button icon="chevron-left" label="Voltar aos ativos" :href="route('tesouro.index', ['aba' => 'ativos'])" />
        <x-sys.page-header :label="$investimento->tipo" :title="$investimento->nome" class="min-w-0 flex-1" />
    </div>

    <form id="form-inv-edit" method="POST" action="{{ route('tesouro.investimentos.update', $investimento) }}" data-sys-form class="flex flex-col gap-5 lg:max-w-xl">
        @csrf @method('PUT')
        <x-sys.window label="Ativo">
            <div class="flex flex-col gap-4">
                <x-sys.input name="nome" label="Nome" :value="$investimento->nome" required />
                <div class="grid grid-cols-2 gap-3">
                    <x-sys.input name="aplicado" label="Aplicado" prefix="R$" inputmode="decimal" :value="number_format($investimento->aplicado, 2, ',', '.')" required />
                    <x-sys.input name="atual" label="Valor atual" prefix="R$" inputmode="decimal" :value="number_format($investimento->atual, 2, ',', '.')" required hint="Atualize pelo app do banco" />
                </div>
                @if ($investimento->tipo === 'CDB')
                    <div class="grid grid-cols-2 gap-3">
                        <x-sys.input name="taxa" label="Taxa" :value="$investimento->taxa" placeholder="110% CDI" />
                        <x-sys.input name="vencimento" label="Vencimento" type="date" :value="$venc" />
                    </div>
                @else
                    <x-sys.input name="cotas" label="Cotas" type="number" min="1" inputmode="numeric" :value="$investimento->cotas" />
                @endif
            </div>
        </x-sys.window>
    </form>

    <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-30 flex gap-2 md:bottom-6 lg:max-w-xl">
        <form method="POST" action="{{ route('tesouro.investimentos.destroy', $investimento) }}" data-confirm="Excluir “{{ $investimento->nome }}” e seus rendimentos?">
            @csrf @method('DELETE')
            <x-sys.icon-button icon="trash" label="Excluir investimento" type="submit" variant="outline" class="!text-danger-text" />
        </form>
        <x-sys.button type="submit" form="form-inv-edit" size="lg" icon="check" class="flex-1">Salvar</x-sys.button>
    </div>
</x-layouts.app>
