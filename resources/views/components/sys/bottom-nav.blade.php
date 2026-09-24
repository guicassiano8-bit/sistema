{{--
  Bottom navigation — só no celular (< 768px). Tablet usa o rail e desktop a sidebar (<x-sys.sidebar>).
  Itens vêm de config('sistema.nav'). O layout já inclui e passa os badges.
  <x-sys.bottom-nav :badges="['inventario' => 2, 'missoes' => 3]" />
--}}
@props(['badges' => []])

<nav aria-label="Principal"
     {{ $attributes->class(['fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/[0.97] pb-safe md:hidden']) }}>
    <ul class="mx-auto grid h-16 max-w-xl grid-cols-5">
        @foreach (config('sistema.nav') as $item)
            @php
                $active = request()->routeIs(...(array) $item['match']);
                $href = Route::has($item['route']) ? route($item['route']) : '#';
                $badge = $badges[$item['key']] ?? 0;
            @endphp
            <li>
                <a href="{{ $href }}" @if ($active) aria-current="page" @endif
                   @class([
                       'relative flex h-full flex-col items-center justify-center gap-1 transition-colors duration-160',
                       'text-sys-glow' => $active,
                       'text-ink-soft hover:text-ink active:text-sys-300' => ! $active,
                   ])>
                    @if ($active)
                        <span class="vt-marker absolute inset-x-3 top-0 h-0.5 bg-sys-glow shadow-glow" aria-hidden="true"></span>
                    @endif
                    <span class="relative">
                        <x-sys.icon :name="$item['icon']" size="size-[22px]" :class="$active ? 'drop-shadow-[0_0_6px_rgba(56,189,248,0.6)]' : ''" />
                        @if ($badge)
                            <span class="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-pill bg-rank-s px-1 font-display text-[10px] font-bold leading-none text-white">
                                {{ $badge > 9 ? '9+' : $badge }}<span class="sr-only"> pendentes</span>
                            </span>
                        @endif
                    </span>
                    <span class="font-display text-[11px] font-semibold uppercase tracking-wider">{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
