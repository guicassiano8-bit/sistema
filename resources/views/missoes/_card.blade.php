{{--
  Converte um Model Task no <x-sys.mission-card>. Use: @include('missoes._card', ['m' => $missao])
  Ouro e dificuldade (rank) vêm da própria Task; rank é só visual.
--}}
<x-sys.mission-card
    :title="$m->title"
    :xp="$m->points"
    :gold="$m->gold"
    :rank="$m->rank?->value"
    :time="$m->is_overdue ? $m->scheduled_date->translatedFormat('d/m') : ($m->start_time ? substr($m->start_time, 0, 5) : null)"
    :recurring="$m->recurringTask?->frequency?->label()"
    :done="$m->is_done"
    :overdue="$m->is_overdue"
    :toggle-url="route('missoes.toggle', $m)"
    :transfer-url="route('missoes.transferir', $m)"
    :edit-url="route('missoes.edit', $m)"
    :delete-url="route('missoes.destroy', $m)" />
