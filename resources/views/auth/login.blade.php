{{--
  LOGIN · rota GET /login (name: login.index) · POST /login
  Hierarquia: 1. formulário · 2. identidade do Sistema · 3. erro
  Usuário único: sem cadastro/recuperação. Controller: Auth::attempt($request->only('email','password'), $request->boolean('remember'))
--}}
<x-layouts.guest title="Entrar">
    <div class="flex flex-col gap-8">
        <div class="text-center">
            <p class="sys-label">[ Sistema ]</p>
            <h1 class="mt-2 font-display text-3xl font-bold uppercase tracking-wider text-ink text-glow">Identifique-se, jogador</h1>
            <p class="mt-2 text-sm text-ink-soft">Acesso restrito.</p>
        </div>

        <x-sys.window as="div" padding="lg" variant="active">
            <form method="POST" action="{{ route('login.store') }}" data-sys-form class="flex flex-col gap-4" novalidate>
                @csrf

                <x-sys.input name="email" label="E-mail" type="email" autocomplete="username" inputmode="email"
                             required autofocus />

                <div class="relative">
                    <x-sys.input name="password" label="Senha" type="password" autocomplete="current-password" required class="pr-10" />
                    <x-sys.icon-button icon="eye" label="Mostrar senha" data-toggle-password="#f-password"
                                       class="absolute right-0.5 top-7" />
                </div>

                <label class="flex min-h-tap cursor-pointer items-center gap-3 text-sm text-ink-soft">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember', true))
                           class="size-5 accent-sys-600">
                    Manter conectado
                </label>

                <x-sys.button type="submit" size="lg" :block="true" icon-right="arrow-right">Entrar</x-sys.button>
            </form>
        </x-sys.window>
    </div>
</x-layouts.guest>
