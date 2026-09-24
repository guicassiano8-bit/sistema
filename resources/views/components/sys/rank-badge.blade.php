{{--
  Badge de rank E · D · C · B · A · S

  <x-sys.rank-badge rank="C" />                  (md, 40px — cabeçalho, perfil)
  <x-sys.rank-badge rank="S" size="lg" />        (lg, 64px — tela de nível)
  <x-sys.rank-badge rank="B" size="sm" />        (sm, 24px — dificuldade da missão)
  <x-sys.rank-badge rank="A" size="sm" :with-label="true" />   → [A] RANK A

  A cor e o brilho sobem junto com o rank: E/D/C só borda · B/A glow · S glow roxo duplo + texto brilhante.
--}}
@props(['rank' => 'E', 'size' => 'md', 'withLabel' => false])

@php
$r = strtoupper($rank);
$ranks = [
    'E' => '[--ch-border:rgba(148,163,184,0.6)] [--ch-bg:rgba(148,163,184,0.08)] text-rank-e',
    'D' => '[--ch-border:rgba(52,211,153,0.7)]  [--ch-bg:rgba(52,211,153,0.10)]  text-rank-d',
    'C' => '[--ch-border:#38BDF8] [--ch-bg:rgba(56,189,248,0.10)] text-rank-c',
    'B' => '[--ch-border:#818CF8] [--ch-bg:rgba(129,140,248,0.12)] text-rank-b glow',
    'A' => '[--ch-border:#F59E0B] [--ch-bg:rgba(245,158,11,0.12)] text-rank-a drop-shadow-[0_0_6px_rgba(245,158,11,0.45)]',
    'S' => '[--ch-border:#C084FC] [--ch-bg:rgba(168,85,247,0.18)] text-rank-s-text glow-s',
];
$sizes = [
    'sm' => 'size-6 text-xs [--ch:4px]',
    'md' => 'size-10 text-xl [--ch:6px]',
    'lg' => 'size-16 text-4xl [--ch:10px]',
];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    <span @class([
            'chamfer chamfer-all inline-flex shrink-0 items-center justify-center font-display font-bold leading-none',
            $ranks[$r] ?? $ranks['E'],
            $sizes[$size] ?? $sizes['md'],
            'text-glow-s' => $r === 'S',
          ])
          @unless ($withLabel) role="img" aria-label="Rank {{ $r }}" @endunless>
        <span aria-hidden="true">{{ $r }}</span>
    </span>
    @if ($withLabel)
        <span class="sys-label text-ink-soft">Rank {{ $r }}</span>
    @endif
</span>
