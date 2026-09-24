{{--
  HUD do jogador — fixo no topo de todas as telas logadas (o layout já inclui).
  <x-sys.player-hud :jogador="$jogador" />

  $jogador precisa ter: nome, nivel, xp (XP dentro do nível), xp_proximo (XP total do nível), rank (E–S), ouro
  O JS atualiza sozinho após concluir missão (data-player-xp / -level / -gold).
--}}
@props(['jogador'])

@php $fmt = fn ($n) => number_format($n, 0, ',', '.'); @endphp

<header {{ $attributes->class(['sticky top-0 z-40 border-b border-line bg-void/[0.97] pt-[env(safe-area-inset-top)]']) }}>
    <div class="mx-auto flex max-w-5xl items-center gap-3 px-gutter pb-2 pt-3">
        <a href="{{ Route::has('status') ? route('status') : '#' }}" class="flex min-w-0 flex-1 items-center gap-3" aria-label="Status do jogador: nível {{ $jogador->nivel }}, rank {{ $jogador->rank }}">
            <x-sys.rank-badge :rank="$jogador->rank" />
            <div class="min-w-0 leading-none">
                <p class="sys-label truncate">{{ $jogador->nome ?? 'Jogador' }}</p>
                <p class="mt-1 font-display text-2xl font-bold tracking-wide text-glow">
                    LV.<span data-player-level>{{ $jogador->nivel }}</span>
                </p>
            </div>
        </a>
        <a href="{{ Route::has('loja.index') ? route('loja.index') : '#' }}" class="flex min-h-tap flex-col items-end justify-center" aria-label="{{ $fmt($jogador->ouro) }} de Ouro — abrir a Loja">
            <span class="sys-label text-gold">Ouro</span>
            <span class="inline-flex items-center gap-1 font-display text-lg font-semibold tabular text-gold">
                <x-sys.icon name="coins" size="size-4" /> <span data-player-gold @if (session()->has('ouro_anterior')) data-count-from="{{ session('ouro_anterior') }}" @endif>{{ $fmt($jogador->ouro) }}</span>
            </span>
        </a>
    </div>
    <div class="mx-auto max-w-5xl px-gutter pb-2">
        <x-sys.xp-bar data-player-xp :current="$jogador->xp" :max="$jogador->xp_proximo" size="sm" :show-values="false"
                      label="Experiência do nível {{ $jogador->nivel }}" />
    </div>
</header>
