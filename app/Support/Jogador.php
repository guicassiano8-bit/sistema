<?php

// app/Support/Jogador.php

namespace App\Support;

use App\Models\User;

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

        // curva de nível: cada nível pede um pouco mais que o anterior
        $nivel = 1;
        $resto = $user->xp_total;
        while ($resto >= self::xpParaPassar($nivel)) {
            $resto -= self::xpParaPassar($nivel);
            $nivel++;
        }

        $this->nivel = $nivel;
        $this->xp = $resto;
        $this->xp_proximo = self::xpParaPassar($nivel);
        $this->rank = self::rankDoNivel($nivel);
    }

    public static function xpParaPassar(int $nivel): int
    {
        return 100 + ($nivel - 1) * 50;   // nível 1 → 100 XP, nível 2 → 150, nível 27 → 1.400…
    }

    public static function rankDoNivel(int $nivel): string
    {
        return match (true) {
            $nivel >= 70 => 'S',
            $nivel >= 50 => 'A',
            $nivel >= 35 => 'B',
            $nivel >= 20 => 'C',
            $nivel >= 10 => 'D',
            default => 'E',
        };
    }
}
