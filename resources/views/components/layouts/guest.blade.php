{{-- Layout sem HUD/nav (login) — <x-layouts.guest title="Entrar"> … </x-layouts.guest> --}}
@props(['title' => null])

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
    <x-sys.toast-stack />
    <main class="mx-auto flex min-h-dvh max-w-sm flex-col justify-center px-gutter py-10">
        {{ $slot }}
    </main>
</body>
</html>
