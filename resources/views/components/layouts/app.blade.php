{{--
  Layout das telas logadas — <x-layouts.app title="Missões"> … </x-layouts.app>
  Slots:  $fab (opcional) · $modals (opcional, fora do <main>)

  Navegação por largura (Etapa 4):
    < 768   celular  → HUD no topo + bottom nav + FAB
    768+    tablet   → rail de 88px à esquerda + HUD no topo + FAB
    1024+   desktop  → sidebar de 264px (jogador + ações rápidas + menu); sem HUD, sem FAB

  $jogador e $navBadges chegam por View::composer (ver docs/ETAPA-4-NAVEGACAO.md).
  O prop :badges de cada tela soma/sobrescreve os $navBadges.
--}}
@props(['title' => null, 'badges' => []])

@php $badges = array_merge($navBadges ?? [], $badges); @endphp

<!DOCTYPE html>
<html lang="pt-BR" class="bg-void">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05070F">
    <title>{{ $title ? $title . ' · ' : '' }}Sistema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-60 focus:bg-surface-raised focus:px-4 focus:py-2">Pular para o conteúdo</a>

    <x-sys.toast-stack />

    {{-- tablet (rail) e desktop (sidebar) --}}
    <x-sys.sidebar :jogador="$jogador" :badges="$badges" class="vt-sidebar" />

    <div class="md:pl-[88px] lg:pl-64">
        {{-- HUD: celular e tablet; no desktop o jogador mora na sidebar --}}
        <x-sys.player-hud :jogador="$jogador" class="vt-hud lg:hidden" />

        <main id="conteudo" tabindex="-1"
              class="sys-enter mx-auto flex max-w-5xl flex-col gap-5 px-gutter pb-[calc(8.5rem+env(safe-area-inset-bottom))] pt-5 outline-none md:px-6 md:pb-28 lg:px-8 lg:pb-12 lg:pt-8">
            {{ $slot }}
        </main>
    </div>

    {{ $fab ?? '' }}
    <x-sys.bottom-nav :badges="$badges" class="vt-navbar" />

    {{ $modals ?? '' }}
    <x-sys.confirm />
    <x-sys.level-up />
</body>
</html>
