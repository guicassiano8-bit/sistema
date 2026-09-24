{{--
  Coloque UMA vez no layout, logo após <body>:  <x-sys.toast-stack />
  • Mostra o flash session('sys_toast') (array único ou lista de arrays)
  • Guarda <template> de cada tipo para o Sistema.toast() clonar no JS
--}}
@php
$flash = session('sys_toast');
$flash = $flash ? (isset($flash['title']) || isset($flash['type']) ? [$flash] : $flash) : [];
@endphp

<div class="sys-toast-stack" data-toast-stack role="status" aria-live="polite" aria-atomic="false">
    @foreach ($flash as $t)
        <x-sys.toast :type="$t['type'] ?? 'info'" :title="$t['title'] ?? 'Sistema'"
                     :message="$t['message'] ?? null" :value="$t['value'] ?? null" />
    @endforeach
</div>

@foreach (['success', 'info', 'reward', 'warning', 'danger', 'level'] as $type)
    <template data-toast-template="{{ $type }}">
        <x-sys.toast :type="$type" title="" message="" value="" />
    </template>
@endforeach
