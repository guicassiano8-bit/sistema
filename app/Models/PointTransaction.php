<?php

namespace App\Models;

use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Livro-razão de pontos. Registros nunca são alterados nem apagados:
 * correções são feitas com um novo lançamento (estorno ou ajuste).
 */
class PointTransaction extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'currency',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PointTransactionType::class,
            'currency' => PointCurrency::class,
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Lançamentos de pontos não podem ser alterados. Crie um estorno.'));
        static::deleting(fn () => throw new LogicException('Lançamentos de pontos não podem ser apagados. Crie um estorno.'));
    }

    // Relacionamentos

    /** Task ou RewardRedemption. Nulo em ajustes manuais. */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    // Consultas

    /** Saldo atual de uma moeda (xp ou gold). */
    public static function balance(PointCurrency $currency): int
    {
        return (int) static::query()->where('currency', $currency)->sum('amount');
    }
}
