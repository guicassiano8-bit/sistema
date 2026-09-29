<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'shopping_item_id',
        'name',
        'description',
        'cost',
        'is_active',
        'is_repeatable',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'is_active' => 'boolean',
            'is_repeatable' => 'boolean',
        ];
    }

    // Relacionamentos

    public function shoppingItem(): BelongsTo
    {
        return $this->belongsTo(ShoppingItem::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    // Scopes

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // Regras

    /** Ativa e, se não for repetível, ainda não resgatada. */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->is_repeatable || ! $this->redemptions()->exists();
    }

    public function isAffordableWith(int $balance): bool
    {
        return $balance >= $this->cost;
    }
}
