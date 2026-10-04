{{--
  ABA EXTRATO
  $mes          Carbon (mês em foco)          $filtro 'todos'|'ganhos'|'gastos'
  $porDia       Collection 'Y-m-d' => Collection<Transaction> (dias mais recentes primeiro)
  $totais       ['entradas' => string, 'saidas' => string (positivo)] — só lançamentos pagos, mês inteiro
--}}
@php
$url = fn ($m, $f = null) => route('tesouro.index', ['aba' => 'extrato', 'mes' => $m->format('Y-m'), 'filtro' => $f ?? $filtro]);
@endphp

<nav aria-label="Mês" class="flex items-center justify-between gap-2">
    <x-sys.icon-button icon="chevron-left" variant="outline" label="Mês anterior" :href="$url($mes->copy()->subMonth())" />
    <h2 class="font-display text-lg font-semibold capitalize text-ink" aria-live="polite">{{ $mes->translatedFormat('F Y') }}</h2>
    <x-sys.icon-button icon="chevron-right" variant="outline" label="Próximo mês" :href="$url($mes->copy()->addMonth())" />
</nav>

<x-sys.segmented label="Filtro" :items="[
    ['label' => 'Todos',  'href' => $url($mes, 'todos'),  'active' => $filtro === 'todos'],
    ['label' => 'Ganhos', 'href' => $url($mes, 'ganhos'), 'active' => $filtro === 'ganhos'],
    ['label' => 'Gastos', 'href' => $url($mes, 'gastos'), 'active' => $filtro === 'gastos'],
]" />

<div class="grid grid-cols-2 gap-2">
    <x-sys.stat label="Entradas" icon="trending-up" tone="gain"><x-sys.money :value="$totais['entradas']" /></x-sys.stat>
    <x-sys.stat label="Saídas" icon="trending-down" tone="danger"><x-sys.money :value="$totais['saidas']" /></x-sys.stat>
</div>

@forelse ($porDia as $dia => $lancs)
    @php $d = \Carbon\Carbon::parse($dia); @endphp
    <section aria-labelledby="dia-{{ $dia }}" class="flex flex-col gap-2">
        <h3 id="dia-{{ $dia }}" class="flex items-baseline justify-between">
            <span class="sys-label">{{ $d->isToday() ? 'Hoje' : ($d->isYesterday() ? 'Ontem' : $d->translatedFormat('D, d M')) }}</span>
            <x-sys.money :value="$lancs->filter(fn ($t) => $t->status === \App\Enums\TransactionStatus::Paid)->sum('signed_amount')" signed class="text-xs" />
        </h3>
        <x-sys.window as="ul" padding="none" :scan="false" class="divide-y divide-line-subtle">
            @foreach ($lancs as $l)
                @include('tesouro._linha', ['l' => $l, 'semData' => true])
            @endforeach
        </x-sys.window>
    </section>
@empty
    <p class="py-8 text-center text-sm text-ink-soft">Nenhum lançamento neste mês.</p>
@endforelse
