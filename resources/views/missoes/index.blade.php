{{--
  MISSÕES · GET /missoes?v=dia|semana|mes|ano&data=Y-m-d (name: missoes.index)

  Dados do controller (sempre):
    $visao     'dia'|'semana'|'mes'|'ano'
    $data      Carbon  (data em foco)
    $anterior  Carbon  $proxima Carbon   (período anterior/seguinte — o controller calcula conforme a visão)
  Por visão:
    dia    → $missoes   Collection<Missao> do dia (+ atrasadas quando $data é hoje)
    semana → $dias      Collection de ['data' => Carbon, 'missoes' => Collection<Missao>] (dom → sáb — use startOfWeek(Carbon::SUNDAY))
    mes    → $resumoMes array 'Y-m-d' => ['total' => int, 'feitas' => int]  +  $missoes do dia em foco
    ano    → $resumoAno array 'Y-m-d' => ['total' => int, 'feitas' => int]
--}}
@php
$link = fn ($v, $d = null) => route('missoes.index', ['v' => $v, 'data' => ($d ?? $data)->format('Y-m-d')]);
$titulo = match ($visao) {
    'dia'    => $data->isToday() ? 'Hoje · ' . $data->translatedFormat('D, d M') : $data->translatedFormat('l, d M'),
    'semana' => $data->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->translatedFormat('d M') . ' – ' . $data->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)->translatedFormat('d M'),
    'mes'    => ucfirst($data->translatedFormat('F Y')),
    'ano'    => $data->format('Y'),
};
$nomes = ['dia' => 'dia', 'semana' => 'semana', 'mes' => 'mês', 'ano' => 'ano'];
@endphp

<x-layouts.app title="Missões">
    <x-sys.page-header label="Registro de missões" title="Missões">
        <x-slot:actions>
            @unless ($data->isToday())
                <x-sys.button variant="secondary" size="md" :href="$link($visao, today())">Hoje</x-sys.button>
            @endunless
        </x-slot:actions>
    </x-sys.page-header>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-sys.segmented label="Visão" class="lg:w-96" :items="[
            ['label' => 'Dia',    'href' => $link('dia'),    'active' => $visao === 'dia'],
            ['label' => 'Semana', 'href' => $link('semana'), 'active' => $visao === 'semana'],
            ['label' => 'Mês',    'href' => $link('mes'),    'active' => $visao === 'mes'],
            ['label' => 'Ano',    'href' => $link('ano'),    'active' => $visao === 'ano'],
        ]" />

        <nav aria-label="Período" class="flex items-center justify-between gap-2 lg:w-80">
            <x-sys.icon-button icon="chevron-left" variant="outline" :label="'Anterior ' . $nomes[$visao]" :href="$link($visao, $anterior)" />
            <h2 class="font-display text-lg font-semibold capitalize text-ink" aria-live="polite">{{ $titulo }}</h2>
            <x-sys.icon-button icon="chevron-right" variant="outline" :label="'Próximo ' . $nomes[$visao]" :href="$link($visao, $proxima)" />
        </nav>
    </div>

    @include('missoes.visoes.' . $visao)

    <x-slot:fab><x-sys.fab label="Nova missão" modal="modal-missao" /></x-slot:fab>
    <x-slot:modals>@include('missoes._modais', ['dataPadrao' => $data])</x-slot:modals>
</x-layouts.app>
