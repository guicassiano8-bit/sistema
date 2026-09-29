<?php

namespace App\Services;

/**
 * Curva de nível e rank do jogador. Nível não é salvo no banco: é sempre
 * calculado a partir de users.xp_total.
 */
class LevelService
{
    /** XP necessário para passar do nível informado para o próximo. */
    public static function xpParaProximoNivel(int $nivel): int
    {
        return 100 * $nivel;
    }

    public static function rankDoNivel(int $nivel): string
    {
        return match (true) {
            $nivel >= 50 => 'S',
            $nivel >= 40 => 'A',
            $nivel >= 30 => 'B',
            $nivel >= 20 => 'C',
            $nivel >= 10 => 'D',
            default => 'E',
        };
    }

    /**
     * @return array{nivel: int, xp: int, xp_proximo: int, rank: string}
     */
    public static function calcular(int $xpTotal): array
    {
        $nivel = 1;
        $resto = $xpTotal;

        while ($resto >= self::xpParaProximoNivel($nivel)) {
            $resto -= self::xpParaProximoNivel($nivel);
            $nivel++;
        }

        return [
            'nivel' => $nivel,
            'xp' => $resto,
            'xp_proximo' => self::xpParaProximoNivel($nivel),
            'rank' => self::rankDoNivel($nivel),
        ];
    }
}
