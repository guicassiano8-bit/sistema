<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use App\Enums\ShoppingCategory;
use App\Enums\ShoppingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ShoppingItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'estimated_price',
        'quantity',
        'link',
        'notes',
        'status',
        'purchased_at',
        'actual_price',
    ];

    protected $attributes = [
        'status' => 'pending',
        'quantity' => 1,
    ];

    protected function casts(): array
    {
        return [
            'category' => ShoppingCategory::class,
            'status' => ShoppingStatus::class,
            'estimated_price' => 'decimal:2',
            'actual_price' => 'decimal:2',
            'quantity' => 'integer',
            'purchased_at' => 'datetime',
        ];
    }

    // Relacionamentos

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    /** O gasto criado quando o item foi comprado. */
    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'source');
    }

    // Scopes

    public function scopePending(Builder $query): void
    {
        $query->where('status', ShoppingStatus::Pending);
    }

    public function scopeInCategory(Builder $query, ShoppingCategory $category): void
    {
        $query->where('category', $category);
    }

    // Accessors

    /** Preço estimado × quantidade. Nulo se não houver preço. */
    protected function estimatedTotal(): Attribute
    {
        return Attribute::get(
            fn () => $this->estimated_price === null
                ? null
                : (string) BigDecimal::of($this->estimated_price)->multipliedBy($this->quantity)
        );
    }
}
