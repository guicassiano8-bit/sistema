{{--
  Tela de celebração LEVEL UP (o layout já inclui uma vez).
  Abre por:
   • JS:       document.dispatchEvent(new CustomEvent('sistema:levelup', { detail: { level: 28, rank: 'C', rank_changed: false, xp: 0, xp_max: 2100 } }))
               (o sistema.js já dispara isso quando o toggle responde leveled_up: true)
   • Redirect: ->with('level_up', ['level' => 28, 'rank' => 'B', 'rank_changed' => true, 'xp' => 40, 'xp_max' => 2100])
  Fecha: botão Continuar, Esc ou sozinho em 6 s.

  Coreografia (Etapa 5, classes .lu-* em app.css) — total ≈ 1,1 s:
    0 ms    fundo escurece (200 ms) · anel de energia cresce 0,6 → 1 (900 ms)
    100 ms  linha de varredura desce pela tela (900 ms) · rótulo [ SISTEMA ] sobe
    150 ms  "LEVEL UP" abre do espaçamento .5em → .04em e estica na vertical (700 ms)
    500 ms  LV.27 → LV.28 sobe · 700 ms o número novo dá um pulinho
    800 ms  badge do novo rank entra com giro leve (se mudou)
    850 ms  barra de XP do novo nível aparece e enche
    950 ms  botão Continuar
  Com prefers-reduced-motion: tudo aparece de uma vez, sem movimento.
--}}
@php $lu = session('level_up'); @endphp

<dialog id="sys-levelup" aria-labelledby="sys-levelup-title" data-levelup
        @if ($lu) data-levelup-initial="{{ json_encode($lu) }}" @endif
        class="sys-levelup m-0 size-full max-h-none max-w-none overflow-hidden bg-transparent p-0 text-ink backdrop:bg-void/90">
    <div class="relative flex size-full flex-col items-center justify-center gap-6 px-gutter text-center">
        {{-- anel de energia --}}
        <div class="lu-ring pointer-events-none absolute left-1/2 top-1/2 size-[min(80vw,26rem)] -translate-x-1/2 -translate-y-1/2 rounded-full border border-sys-glow/30 shadow-[0_0_80px_rgba(56,189,248,0.25),inset_0_0_60px_rgba(56,189,248,0.15)]" aria-hidden="true"></div>
        {{-- varredura (1 passada) --}}
        <div class="lu-sweep pointer-events-none absolute inset-x-0 top-1/2 h-px bg-linear-to-r from-transparent via-sys-glow to-transparent shadow-[0_0_12px_rgba(56,189,248,0.8)]" aria-hidden="true"></div>

        <div data-levelup-panel class="relative flex flex-col items-center gap-4">
            <p class="lu-label sys-label">[ Sistema ]</p>
            <h2 id="sys-levelup-title" class="lu-title font-display text-hero font-bold uppercase text-ink text-glow">Level up</h2>

            <p class="lu-levels flex items-center gap-4 font-display text-3xl font-semibold tabular">
                <span class="text-ink-muted">LV.<span data-levelup-from></span></span>
                <x-sys.icon name="arrow-right" size="size-6" class="text-sys-400" />
                <span class="lu-to text-sys-glow text-glow">LV.<span data-levelup-to></span></span>
            </p>

            <div data-levelup-rank class="lu-rank hidden flex-col items-center gap-2">
                <p class="sys-label text-rank-s-text">Novo rank alcançado</p>
                <span data-levelup-rank-badge></span>
            </div>

            <div class="lu-xp w-64">
                <x-sys.xp-bar data-levelup-xp :current="0" :max="100" :show-values="true" label="Experiência do novo nível" />
            </div>

            <x-sys.button size="lg" data-levelup-close class="lu-cta mt-2 min-w-48" autofocus>Continuar</x-sys.button>
        </div>
    </div>

    {{-- badges pré-renderizados para o JS copiar quando o rank muda --}}
    @foreach (['E', 'D', 'C', 'B', 'A', 'S'] as $r)
        <template data-rank-template="{{ $r }}"><x-sys.rank-badge :rank="$r" size="lg" /></template>
    @endforeach
</dialog>
