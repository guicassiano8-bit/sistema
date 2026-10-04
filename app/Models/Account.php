<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O saldo da conta não fica aqui: é calculado pelo AccountBalanceService,
 * somando lançamentos, transferências e aportes.
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'institution',
        'initial_balance',
        'closing_day',
        'due_day',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'closing_day' => 'integer',
            'due_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relacionamentos

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function investmentTransactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    // Scopes

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeCreditCards(Builder $query): void
    {
        $query->where('type', AccountType::CreditCard);
    }

    // Regras

    public function isCreditCard(): bool
    {
        return $this->type->isCreditCard();
    }
}
