<?php

namespace App\Models;

use App\Enums\Frequency;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Molde de lançamento recorrente: salário, aluguel, assinaturas. */
class RecurringTransaction extends Model
{
    protected $fillable = [
        'account_id',
        'finance_category_id',
        'type',
        'description',
        'amount',
        'payment_method',
        'frequency',
        'day_of_month',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'payment_method' => PaymentMethod::class,
            'frequency' => Frequency::class,
            'amount' => 'decimal:2',
            'day_of_month' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // Relacionamentos

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_on', '<=', today())
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', today()));
    }
}
