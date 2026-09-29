<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'finance_category_id',
        'month',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Garante que month seja sempre o dia 01, qualquer que seja a data recebida.
        static::saving(function (Budget $budget) {
            $budget->month = $budget->month->copy()->startOfMonth();
        });
    }

    // Relacionamentos

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }

    // Scopes

    public function scopeForMonth(Builder $query, CarbonInterface $month): void
    {
        $query->whereDate('month', $month->copy()->startOfMonth());
    }
}
