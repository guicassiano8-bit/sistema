<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\IncomeEntry;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Situação de um ativo hoje: quanto foi aplicado, quanto vale e (para cotas) quantas restam.
 * `income` é a soma dos rendimentos recebidos (dividendos, juros); entra no resultado, mas não no valor atual.
 */
final readonly class AssetPosition
{
    public function __construct(
        public Asset $asset,
        public string $type,
        public bool $marketTraded,
        public BigDecimal $invested,
        public BigDecimal $current,
        public ?BigDecimal $quantity,
        public ?BigDecimal $lastPrice,
        public ?IncomeEntry $lastIncome,
        public BigDecimal $income,
    ) {}

    /** Valorização mais os rendimentos recebidos. */
    public function result(): BigDecimal
    {
        return $this->current->minus($this->invested)->plus($this->income);
    }

    /** Rendimento em % sobre o aplicado; zero quando não há nada aplicado. */
    public function resultPercent(): float
    {
        if (! $this->invested->isPositive()) {
            return 0.0;
        }

        return $this->result()->multipliedBy(100)->dividedBy($this->invested, 4, RoundingMode::HalfUp)->toFloat();
    }

    /** Último rendimento dividido pelas cotas de hoje (só faz sentido para FIIs). */
    public function lastIncomePerShare(): ?BigDecimal
    {
        if ($this->lastIncome === null || $this->quantity === null || ! $this->quantity->isPositive()) {
            return null;
        }

        return BigDecimal::of($this->lastIncome->amount)->dividedBy($this->quantity, 4, RoundingMode::HalfUp);
    }
}
