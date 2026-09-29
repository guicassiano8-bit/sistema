{{--
  Converte um Model Missao no <x-sys.mission-card>. Use: @include('missoes._card', ['m' => $missao])
  Campos esperados em Missao: id, titulo, xp, ouro, horario (H:i|null), rank (E–S|null),
  recorrencia (null|diaria|semanal|mensal), concluida (bool), data (date) + accessor "atrasada" (data < hoje && !concluida)
--}}
@php
$rotulosRecorrencia = ['diaria' => 'Diária', 'semanal' => 'Semanal', 'mensal' => 'Mensal'];
@endphp
<x-sys.mission-card
    :title="$m->titulo"
    :xp="$m->xp"
    :gold="$m->ouro ?: null"
    :time="$m->atrasada ? $m->data->translatedFormat('d/m') : ($m->horario ? substr($m->horario, 0, 5) : null)"
    :rank="$m->rank"
    :recurring="$rotulosRecorrencia[$m->recorrencia] ?? null"
    :done="$m->concluida"
    :overdue="$m->atrasada"
    :toggle-url="route('missoes.toggle', $m)"
    :transfer-url="route('missoes.transferir', $m)"
    :edit-url="route('missoes.edit', $m)"
    :delete-url="route('missoes.destroy', $m)" />
