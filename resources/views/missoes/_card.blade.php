{{--
  Converte um Model Task no <x-sys.mission-card>. Use: @include('missoes._card', ['m' => $missao])
  Task não tem ouro nem rank por missão (ouro/rank são do jogador, ver App\Support\Jogador).
--}}
<x-sys.mission-card
    :title="$m->title"
    :xp="$m->points"
    :time="$m->is_overdue ? $m->scheduled_date->translatedFormat('d/m') : ($m->start_time ? substr($m->start_time, 0, 5) : null)"
    :recurring="$m->recurringTask?->frequency?->label()"
    :done="$m->is_done"
    :overdue="$m->is_overdue"
    :toggle-url="route('missoes.toggle', $m)"
    :transfer-url="route('missoes.transferir', $m)"
    :edit-url="route('missoes.edit', $m)"
    :delete-url="route('missoes.destroy', $m)" />
