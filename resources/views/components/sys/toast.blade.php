{{--
  Notificação do Sistema (um toast). Normalmente você NÃO usa direto:
  coloque <x-sys.toast-stack /> no layout e dispare:

  • PHP (redirect):  return back()->with('sys_toast', ['type' => 'success', 'title' => 'Missão concluída', 'message' => 'Leitura', 'value' => '+50 XP']);
  • JS:              Sistema.toast({ type: 'success', title: 'Missão concluída', message: 'Leitura', value: '+50 XP' })

  type: success (missão) | info (sistema) | reward (ouro/loja) | warning (alerta) | danger (erro/gasto alto) | level (nível — curto; a tela cheia vem na Etapa 3)
  Some sozinho em 3,5 s (level: 5 s; erros ficam até fechar) — a barra fina embaixo mostra o tempo e pausa com hover/foco. Leitores de tela: região aria-live do stack.
--}}
@props([
    'type' => 'info',
    'title' => 'Sistema',
    'message' => null,
    'value' => null,
])

@php
$types = [
    'success' => ['icon' => 'check',  'accent' => 'text-success-text', 'border' => '[--ch-border:rgba(45,212,191,0.6)]',  'glow' => 'drop-shadow-[0_0_8px_rgba(45,212,191,0.35)]'],
    'info'    => ['icon' => 'info',   'accent' => 'text-sys-400',      'border' => '[--ch-border:rgba(96,165,250,0.6)]',  'glow' => 'glow'],
    'reward'  => ['icon' => 'gift',   'accent' => 'text-gold',         'border' => '[--ch-border:rgba(251,191,36,0.6)]',  'glow' => 'drop-shadow-[0_0_8px_rgba(251,191,36,0.35)]'],
    'warning' => ['icon' => 'alert',  'accent' => 'text-warning-text', 'border' => '[--ch-border:rgba(245,158,11,0.6)]',  'glow' => ''],
    'danger'  => ['icon' => 'alert',  'accent' => 'text-danger-text',  'border' => '[--ch-border:rgba(244,63,94,0.6)]',   'glow' => 'shadow-none'],
    'level'   => ['icon' => 'zap',    'accent' => 'text-rank-s-text',  'border' => '[--ch-border:#A855F7]',               'glow' => 'glow-s'],
];
$t = $types[$type] ?? $types['info'];
@endphp

<div data-toast data-type="{{ $type }}" {{ $attributes->class(['sys-toast chamfer sys-window-raised flex items-center gap-3 py-3 pl-4 pr-1 [--ch:8px]', $t['border'], $t['glow']]) }}>
    <x-sys.icon :name="$t['icon']" :class="$t['accent']" />
    <div class="min-w-0 flex-1">
        <p class="sys-label {{ $t['accent'] }}">[ <span data-toast-title>{{ $title }}</span> ]</p>
        <p data-toast-message @class(['truncate text-sm text-ink-soft', 'hidden' => ! $message])>{{ $message }}</p>
    </div>
    <p data-toast-value @class(['shrink-0 font-display text-lg font-bold tabular', $t['accent'], 'hidden' => ! $value])>{{ $value }}</p>
    @unless ($type === 'danger')
        {{-- tempo de vida: some quando a barra termina; pausa com hover/foco --}}
        <span data-toast-timer aria-hidden="true" class="sys-toast-timer {{ $t['accent'] }}" style="--toast-ms: {{ $type === 'level' ? 5000 : 3500 }}ms"></span>
    @endunless
    <button type="button" data-toast-close aria-label="Fechar notificação"
            class="flex size-tap shrink-0 items-center justify-center text-ink-muted hover:text-ink">
        <x-sys.icon name="x" size="size-4" />
    </button>
</div>
