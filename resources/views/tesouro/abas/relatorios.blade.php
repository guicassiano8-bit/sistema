{{--
  ABA RELATÓRIOS (gráficos renderizados no servidor, sem biblioteca)
  $serie12m      array [['label' => 'out/25', 'value' => float], …]  patrimônio no fim de cada mês
  $fluxo6m       ['labels' => ['abr', …], 'ganhos' => [float…], 'gastos' => [float… positivos]]
  $porCategoria  array [['label' => 'Alimentação', 'value' => float], …]  gastos do mês, maior → menor
--}}
@php
$fmt = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
$ultimo = end($serie12m) ?: ['value' => 0];
$primeiro = reset($serie12m) ?: ['value' => 0];
@endphp

{{-- 1 · PATRIMÔNIO 12M — linha, 1 série (o título nomeia; sem legenda) --}}
<x-sys.chart label="Relatório" title="Patrimônio · 12 meses"
             :value="$fmt($ultimo['value'])"
             :delta="($ultimo['value'] >= $primeiro['value'] ? '+' : '−') . $fmt(abs($ultimo['value'] - $primeiro['value']))"
             :delta-positive="$ultimo['value'] >= $primeiro['value']"
             description="Patrimônio no fim de cada mês, últimos 12 meses"
             :state="count($serie12m) ? 'ready' : 'empty'">
    <x-sys.line-chart :points="$serie12m" />
    <x-slot:table>
        <table><caption>Patrimônio por mês</caption>
            <thead><tr><th scope="col">Mês</th><th scope="col">Patrimônio</th></tr></thead>
            <tbody>@foreach ($serie12m as $p)<tr><td>{{ $p['label'] }}</td><td>{{ $fmt($p['value']) }}</td></tr>@endforeach</tbody>
        </table>
    </x-slot:table>
</x-sys.chart>

{{-- 2 · GANHOS × GASTOS 6M — barras agrupadas, 2 séries + legenda --}}
<x-sys.chart label="Relatório" title="Ganhos × gastos" description="Ganhos e gastos por mês, últimos 6 meses"
             :state="count($fluxo6m['labels']) ? 'ready' : 'empty'">
    <x-sys.bar-chart :labels="$fluxo6m['labels']" :series="[
        ['name' => 'Ganhos', 'color' => '#34D399', 'values' => $fluxo6m['ganhos']],
        ['name' => 'Gastos', 'color' => '#F43F5E', 'values' => $fluxo6m['gastos']],
    ]" />
    <x-slot:legend>
        <x-sys.chart-legend :items="[['label' => 'Ganhos', 'color' => '#34D399'], ['label' => 'Gastos', 'color' => '#F43F5E']]" />
    </x-slot:legend>
    <x-slot:table>
        <table><caption>Ganhos e gastos por mês</caption>
            <thead><tr><th scope="col">Mês</th><th scope="col">Ganhos</th><th scope="col">Gastos</th></tr></thead>
            <tbody>@foreach ($fluxo6m['labels'] as $i => $m)<tr><td>{{ $m }}</td><td>{{ $fmt($fluxo6m['ganhos'][$i] ?? 0) }}</td><td>{{ $fmt($fluxo6m['gastos'][$i] ?? 0) }}</td></tr>@endforeach</tbody>
        </table>
    </x-slot:table>
</x-sys.chart>

{{-- 3 · GASTOS POR CATEGORIA — barras horizontais, 1 tom, valor escrito --}}
<x-sys.chart label="Relatório" title="Gastos por categoria · {{ now()->translatedFormat('F') }}" height="h-auto"
             :state="count($porCategoria) ? 'ready' : 'empty'">
    <x-sys.hbar-chart :items="$porCategoria" color="#F43F5E" />
</x-sys.chart>
