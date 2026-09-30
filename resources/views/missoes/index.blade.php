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
$filtros = $filtros ?? [];
// os filtros acompanham a navegação entre visões e períodos
$link = fn ($v, $d = null) => route('missoes.index', ['v' => $v, 'data' => ($d ?? $data)->format('Y-m-d')] + array_filter($filtros));
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

    <details class="group/filtros" @if (array_filter($filtros)) open @endif>
        <summary class="flex min-h-tap cursor-pointer items-center gap-2 text-sm text-ink-soft hover:text-ink">
            <x-sys.icon name="search" size="size-4" class="text-sys-400" />
            Filtros e busca
            @if (array_filter($filtros))
                <a href="{{ route('missoes.index', ['v' => $visao, 'data' => $data->format('Y-m-d')]) }}" class="ml-auto text-xs text-sys-400 underline">Limpar</a>
            @endif
        </summary>
        <form method="GET" action="{{ route('missoes.index') }}" class="mt-2 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <input type="hidden" name="v" value="{{ $visao }}">
            <input type="hidden" name="data" value="{{ $data->format('Y-m-d') }}">
            <div class="col-span-2 lg:col-span-1">
                <x-sys.input name="q" label="Buscar" icon="search" :value="$filtros['q'] ?? null" maxlength="120" />
            </div>
            <x-sys.select name="status" label="Status" placeholder="Todos" :value="$filtros['status'] ?? null"
                          :options="['pending' => 'Pendentes', 'done' => 'Concluídas', 'cancelled' => 'Canceladas']" />
            <x-sys.select name="rank" label="Rank" placeholder="Todos" :value="$filtros['rank'] ?? null"
                          :options="array_combine(\App\Enums\TaskRank::values(), \App\Enums\TaskRank::values())" />
            <x-sys.select name="tipo" label="Tipo" placeholder="Todos" :value="$filtros['tipo'] ?? null"
                          :options="['avulsa' => 'Avulsas', 'recorrente' => 'Recorrentes']" />
            <x-sys.button type="submit" variant="secondary" class="col-span-2 lg:col-span-4">Aplicar</x-sys.button>
        </form>
    </details>

    @include('missoes.visoes.' . $visao)

    <x-slot:fab><x-sys.fab label="Nova missão" modal="modal-missao" /></x-slot:fab>
    <x-slot:modals>@include('missoes._modais', ['dataPadrao' => $data])</x-slot:modals>
</x-layouts.app>
