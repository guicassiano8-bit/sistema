{{--
  Card de recompensa (Loja do Sistema) — trocar = 2 toques (Trocar → Confirmar)

  <x-sys.reward-card name="Episódio extra de série" description="1 episódio fora do horário"
      :cost="150" :balance="$jogador->ouro" icon="gift" tier="C"
      :redeem-url="route('loja.trocar', $recompensa)" />

  <x-sys.reward-card name="Jantar fora" :cost="1200" :balance="480" tier="A" image="{{ $url }}" />
  <x-sys.reward-card name="Dia livre" :cost="3000" :balance="5000" tier="S" cooldown="Disponível em 3 dias" />

  Estados (automáticos):
    disponível   → saldo ≥ custo: botão dourado "Trocar"
    bloqueado    → saldo < custo: cadeado + barra "faltam X Ouro"
    em recarga   → cooldown preenchido: botão desabilitado com o texto
  tier: E–S opcional (a cor do rank indica o "peso" da recompensa)
--}}
@props([
    'name',
    'cost',
    'balance' => 0,
    'description' => null,
    'icon' => 'gift',
    'image' => null,
    'tier' => null,
    'cooldown' => null,
    'redeemUrl' => '#',
    'editUrl' => null,
])

@php
$fmt = fn ($n) => number_format($n, 0, ',', '.');
$canAfford = $balance >= $cost;
$state = $cooldown ? 'cooldown' : ($canAfford ? 'available' : 'locked');
$isS = strtoupper((string) $tier) === 'S';
@endphp

<article data-reward data-state="{{ $state }}"
         {{ $attributes->class([
             'chamfer flex flex-col [--ch:12px]',
             'sys-window scanlines' => ! $isS,
             '[--ch-border:rgba(251,191,36,0.55)]' => $state === 'available' && ! $isS,
             '[--ch-border:#A855F7] [--ch-bg:#140F24] glow-s' => $isS,
         ]) }}>

    {{-- ARTE: imagem enviada ou ícone num "pedestal" holográfico --}}
    <div class="relative m-px flex aspect-[16/9] items-center justify-center overflow-hidden border-b border-line-subtle [clip-path:polygon(11px_0,100%_0,100%_100%,0_100%,0_11px)]">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" @class(['size-full object-cover', 'opacity-40 grayscale' => $state === 'locked'])>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_60%,rgba(37,99,235,0.22),transparent_60%)]" aria-hidden="true"></div>
            <x-sys.icon :name="$state === 'locked' ? 'lock' : $icon" size="size-12" :stroke="1.5"
                        :class="$state === 'locked' ? 'text-ink-faint' : ($isS ? 'text-rank-s-text drop-shadow-[0_0_10px_rgba(168,85,247,0.7)]' : 'text-sys-400 drop-shadow-[0_0_10px_rgba(56,189,248,0.6)]')" />
        @endif
        @if ($tier)
            <x-sys.rank-badge :rank="$tier" size="sm" class="absolute left-3 top-3" />
        @endif
        @if ($editUrl)
            <x-sys.icon-button icon="pencil" :label="'Editar ' . $name" :href="$editUrl" class="!absolute right-1 top-1 !text-ink-muted" />
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 p-4">
        <div class="min-w-0">
            <h3 class="font-display text-lg font-semibold leading-snug text-ink">{{ $name }}</h3>
            @if ($description)<p class="mt-0.5 line-clamp-2 text-sm text-ink-soft">{{ $description }}</p>@endif
        </div>

        <p class="mt-auto flex items-center gap-1.5 font-display text-xl font-bold tabular text-gold">
            <x-sys.icon name="coins" size="size-5" /> {{ $fmt($cost) }}
            <span class="text-xs font-semibold tracking-wider">OURO</span>
        </p>

        @if ($state === 'available')
            <form method="POST" action="{{ $redeemUrl }}" data-sys-form data-confirm="Trocar {{ $fmt($cost) }} Ouro por “{{ $name }}”?" data-confirm-ok="Trocar">
                @csrf
                <x-sys.button type="submit" variant="gold" icon="gift" :block="true">Trocar</x-sys.button>
            </form>
        @elseif ($state === 'locked')
            <div>
                <x-sys.xp-bar :current="$balance" :max="$cost" variant="gold" size="sm" label="Progresso para {{ $name }}" :show-values="false" />
                <p class="mt-1.5 flex items-center gap-1.5 text-xs text-ink-soft">
                    <x-sys.icon name="lock" size="size-3.5" /> Faltam <span class="font-semibold tabular text-gold">{{ $fmt($cost - $balance) }}</span> Ouro
                </p>
            </div>
        @else
            <x-sys.button variant="secondary" icon="clock" :block="true" :disabled="true">{{ $cooldown }}</x-sys.button>
        @endif
    </div>
</article>
