{{--
  Navegação lateral — um componente, dois formatos (o layout já inclui):
    md (768–1023, tablet)   → RAIL de 88px: ícone + rótulo curto, rank no topo, sair no rodapé
    lg (≥ 1024, desktop)    → SIDEBAR de 264px: cartão do jogador (nível, XP, Ouro), ações rápidas com atalho, navegação, sair
  Some no celular (< 768), onde ficam o HUD + bottom nav.

  <x-sys.sidebar :jogador="$jogador" :badges="$badges" />
  Itens e ações rápidas: config/sistema.php
--}}
@props(['jogador', 'badges' => []])

@php $fmt = fn ($n) => number_format($n, 0, ',', '.'); @endphp

<aside aria-label="Navegação do Sistema"
       {{ $attributes->class([
           'fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-line bg-surface/[0.97] md:flex',
           'w-[88px] lg:w-64',
           'pb-[env(safe-area-inset-bottom)] pt-[env(safe-area-inset-top)]',
       ]) }}>

    {{-- ─── IDENTIDADE / JOGADOR ─── --}}
    <a href="{{ Route::has('dashboard') ? route('dashboard') : '#' }}"
       aria-label="Status do jogador: nível {{ $jogador->nivel }}, rank {{ $jogador->rank }}"
       class="group/card flex flex-col items-center gap-1 px-2 pb-3 pt-4 lg:m-3 lg:mb-2 lg:items-stretch lg:gap-3 lg:p-0">

        {{-- rail: só o rank + nível --}}
        <span class="flex flex-col items-center gap-1 lg:hidden">
            <x-sys.rank-badge :rank="$jogador->rank" />
            <span class="font-display text-sm font-bold tracking-wide text-glow">LV.<span data-player-level>{{ $jogador->nivel }}</span></span>
        </span>

        {{-- sidebar: cartão completo --}}
        <div class="chamfer sys-window scanlines hidden flex-col gap-3 p-4 [--ch:12px] group-hover/card:[--ch-border:rgba(96,165,250,0.5)] lg:flex">
            <span class="flex items-center gap-3">
                <x-sys.rank-badge :rank="$jogador->rank" />
                <span class="min-w-0 flex-1 leading-none">
                    <span class="sys-label block truncate">{{ $jogador->nome ?? 'Jogador' }}</span>
                    <span class="mt-1 block font-display text-2xl font-bold tracking-wide text-glow">LV.<span data-player-level>{{ $jogador->nivel }}</span></span>
                </span>
            </span>
            <x-sys.xp-bar data-player-xp :current="$jogador->xp" :max="$jogador->xp_proximo" label="Experiência do nível {{ $jogador->nivel }}" />
            <span class="flex items-center justify-between border-t border-line-subtle pt-2">
                <span class="sys-label text-gold">Ouro</span>
                <span class="inline-flex items-center gap-1 font-display text-lg font-semibold tabular text-gold">
                    <x-sys.icon name="coins" size="size-4" /><span data-player-gold @if (session()->has('ouro_anterior')) data-count-from="{{ session('ouro_anterior') }}" @endif>{{ $fmt($jogador->ouro) }}</span>
                </span>
            </span>
        </div>
    </a>

    {{-- ─── AÇÕES RÁPIDAS (só desktop; no tablet o FAB continua) ─── --}}
    <div class="hidden flex-col gap-2 px-3 pb-3 lg:flex" aria-label="Ações rápidas" role="group">
        @foreach (config('sistema.acoes') as $acao)
            @php $href = Route::has($acao['route']) ? route($acao['route'], ['acao' => $acao['acao']]) : '#'; @endphp
            <x-sys.button :href="$href" :variant="$acao['variant']" :icon="$acao['icon']" size="md"
                          :data-modal-open="$acao['modal']" :data-focus-target="$acao['foco'] ?? null" data-shortcut="{{ strtolower($acao['atalho']) }}"
                          aria-keyshortcuts="{{ $acao['atalho'] }}"
                          class="w-full !justify-start">
                {{ $acao['label'] }}
            </x-sys.button>
        @endforeach
    </div>

    <div class="mx-3 hidden border-t border-line-subtle lg:block" aria-hidden="true"></div>

    {{-- ─── NAVEGAÇÃO ─── --}}
    <nav aria-label="Principal" class="flex-1 overflow-y-auto px-2 py-2 lg:px-3 lg:py-3">
        <p class="sys-label mb-2 hidden px-3 text-ink-muted lg:block">Menu</p>
        <ul class="flex flex-col gap-1">
            @foreach (config('sistema.nav') as $item)
                @php
                    $active = request()->routeIs(...(array) $item['match']);
                    $href = Route::has($item['route']) ? route($item['route']) : '#';
                    $badge = $badges[$item['key']] ?? 0;
                @endphp
                <li>
                    <a href="{{ $href }}" @if ($active) aria-current="page" @endif
                       data-shortcut="{{ $item['atalho'] }}" aria-keyshortcuts="{{ $item['atalho'] }}"
                       @class([
                           'group/nav relative flex items-center transition-colors duration-160',
                           // rail: coluna, 64px de alto
                           'min-h-16 flex-col justify-center gap-1 rounded-sm text-center',
                           // sidebar: linha, 44px
                           'lg:min-h-tap lg:flex-row lg:justify-start lg:gap-3 lg:px-3 lg:text-left',
                           'bg-sys-600/15 text-sys-glow' => $active,
                           'text-ink-soft hover:bg-surface-hover hover:text-ink' => ! $active,
                       ])>
                        {{-- traço de energia vertical no item ativo --}}
                        @if ($active)
                            <span class="vt-marker absolute inset-y-2 left-0 w-0.5 bg-sys-glow shadow-glow" aria-hidden="true"></span>
                        @endif
                        <span class="relative">
                            <x-sys.icon :name="$item['icon']" size="size-[22px]" :class="$active ? 'drop-shadow-[0_0_6px_rgba(56,189,248,0.6)]' : ''" />
                            @if ($badge)
                                <span class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-pill bg-rank-s px-1 font-display text-[10px] font-bold leading-none text-white lg:hidden">
                                    {{ $badge > 9 ? '9+' : $badge }}<span class="sr-only"> pendentes</span>
                                </span>
                            @endif
                        </span>
                        <span class="font-display text-[11px] font-semibold uppercase tracking-wider lg:flex-1 lg:text-sm">{{ $item['label'] }}</span>
                        @if ($badge)
                            <span class="hidden min-w-5 items-center justify-center rounded-pill bg-rank-s/20 px-1.5 font-display text-xs font-bold text-rank-s-text lg:inline-flex" aria-hidden="true">{{ $badge }}</span>
                        @endif
                        <kbd class="hidden font-display text-2xs text-ink-muted group-hover/nav:text-ink-soft lg:inline" aria-hidden="true">{{ $item['atalho'] }}</kbd>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ─── RODAPÉ ─── --}}
    <div class="border-t border-line-subtle p-2 lg:p-3">
        <form method="POST" action="{{ Route::has('login.destroy') ? route('login.destroy') : '#' }}">
            @csrf
            <button type="submit"
                    class="flex min-h-tap w-full flex-col items-center justify-center gap-1 rounded-sm text-ink-muted transition-colors duration-160 hover:bg-surface-hover hover:text-danger-text lg:flex-row lg:justify-start lg:gap-3 lg:px-3">
                <x-sys.icon name="logout" size="size-5" />
                <span class="font-display text-[11px] font-semibold uppercase tracking-wider lg:text-sm">Sair</span>
            </button>
        </form>
        <p class="mt-2 hidden px-3 text-2xs text-ink-muted lg:block">Atalhos: <kbd>N</kbd> missão · <kbd>G</kbd> gasto · <kbd>I</kbd> item · <kbd>1–5</kbd> telas</p>
    </div>
</aside>
