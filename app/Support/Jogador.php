<?php

// app/Support/Jogador.php

namespace App\Support;

use App\Models\User;
use App\Services\LevelService;

class Jogador
{
    public readonly string $nome;

    public readonly int $ouro;

    public readonly int $nivel;

    public readonly int $xp;          // XP dentro do nível atual

    public readonly int $xp_proximo;  // XP necessário para passar de nível

    public readonly string $rank;

    public function __construct(User $user)
    {
        $this->nome = $user->name;
        $this->ouro = $user->ouro;

        $calculo = LevelService::calcular($user->xp_total);

        $this->nivel = $calculo['nivel'];
        $this->xp = $calculo['xp'];
        $this->xp_proximo = $calculo['xp_proximo'];
        $this->rank = $calculo['rank'];
    }

    /** Formato do "player" no contrato JSON dos endpoints de toggle (ver AGENTS.md). */
    public function toArray(): array
    {
        return [
            'xp' => $this->xp,
            'xp_max' => $this->xp_proximo,
            'level' => $this->nivel,
            'gold' => $this->ouro,
            'rank' => $this->rank,
        ];
    }
}
