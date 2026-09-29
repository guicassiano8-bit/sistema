<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PointTransactionType: string
{
    use EnumHelpers;

    case TaskCompleted = 'task_completed';
    case TaskReverted = 'task_reverted';
    case RewardRedeemed = 'reward_redeemed';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::TaskCompleted => 'Tarefa concluída',
            self::TaskReverted => 'Tarefa desmarcada',
            self::RewardRedeemed => 'Recompensa resgatada',
            self::Adjustment => 'Ajuste manual',
        };
    }

    /**
     * Sinal esperado do amount. Null = pode ser os dois (ajuste).
     * Use no service para impedir, por exemplo, um resgate com valor positivo.
     */
    public function expectedSign(): ?int
    {
        return match ($this) {
            self::TaskCompleted => 1,
            self::TaskReverted, self::RewardRedeemed => -1,
            self::Adjustment => null,
        };
    }
}
