<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Fotografia mensal de cada ativo, gravada pelo job de fim de mês. */
class PortfolioSnapshot extends Model
{
    protected $fillable = [
        'asset_id',
        'reference_month',
        'invested_amount',
        'market_value',
    ];

    protected function casts(): array
    {
        return [
            'reference_month' => 'date',
            'invested_amount' => 'decimal:2',
            'market_value' => 'decimal:2',
        ];
    }

    // Relacionamentos

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Accessors

    /** Valor de mercado − valor investido. */
    protected function gain(): Attribute
    {
        return Attribute::get(fn () => (string) BigDecimal::of($this->market_value)->minus($this->invested_amount));
    }
}
