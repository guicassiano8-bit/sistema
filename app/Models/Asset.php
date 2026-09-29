<?php

namespace App\Models;

use App\Enums\Indexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = [
        'asset_type_id',
        'name',
        'ticker',
        'institution',
        'indexer',
        'rate',
        'maturity_date',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'indexer' => Indexer::class,
            'rate' => 'decimal:4',
            'maturity_date' => 'date',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // Relacionamentos

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function balanceUpdates(): HasMany
    {
        return $this->hasMany(AssetBalanceUpdate::class);
    }

    public function incomeEntries(): HasMany
    {
        return $this->hasMany(IncomeEntry::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }

    /** Cotação mais recente (FIIs, ações). */
    public function latestQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->latestOfMany('date');
    }

    /** Saldo informado mais recente (CDBs e renda fixa). */
    public function latestBalanceUpdate(): HasOne
    {
        return $this->hasOne(AssetBalanceUpdate::class)->latestOfMany('reference_date');
    }

    // Scopes

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Ativos com cotação em bolsa: os que o job de cotações atualiza. */
    public function scopeMarketTraded(Builder $query): void
    {
        $query->whereNotNull('ticker')
            ->whereHas('assetType', fn (Builder $q) => $q->where('is_market_traded', true));
    }

    // Accessors

    /** Ex.: "110% do CDI", "IPCA + 6,5%". Nulo para ativos sem indexador. */
    protected function rateLabel(): Attribute
    {
        return Attribute::get(
            fn () => $this->indexer && $this->rate !== null
                ? $this->indexer->formatRate((float) $this->rate)
                : null
        );
    }
}
