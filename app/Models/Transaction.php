<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Ganhos e gastos. amount é sempre positivo; o sinal vem de type. */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'finance_category_id',
        'recurring_transaction_id',
        'type',
        'description',
        'amount',
        'date',
        'payment_method',
        'status',
        'installment_group_id',
        'installment_number',
        'installment_total',
        'source_type',
        'source_id',
    ];

    protected $attributes = [
        'status' => 'paid',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'date' => 'date',
            'installment_number' => 'integer',
            'installment_total' => 'integer',
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

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    /** ShoppingItem que originou o gasto, se houver. */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** Todas as parcelas da mesma compra, incluindo esta. */
    public function installments(): HasMany
    {
        return $this->hasMany(self::class, 'installment_group_id', 'installment_group_id')
            ->orderBy('installment_number');
    }

    // Scopes

    public function scopeIncome(Builder $query): void
    {
        $query->where('type', TransactionType::Income);
    }

    public function scopeExpense(Builder $query): void
    {
        $query->where('type', TransactionType::Expense);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', TransactionStatus::Paid);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', TransactionStatus::Pending);
    }

    /** Lançamentos de um mês. Aceita qualquer data dentro do mês. */
    public function scopeInMonth(Builder $query, CarbonInterface $month): void
    {
        $query->whereBetween('date', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ]);
    }

    // Accessors

    /** Valor com sinal: positivo para ganho, negativo para gasto. */
    protected function signedAmount(): Attribute
    {
        return Attribute::get(
            fn () => $this->type === TransactionType::Expense ? (string) BigDecimal::of($this->amount)->negated() : $this->amount
        );
    }

    protected function isInstallment(): Attribute
    {
        return Attribute::get(fn () => $this->installment_group_id !== null);
    }

    /** Ex.: "2/6". Nulo se não for parcelado. */
    protected function installmentLabel(): Attribute
    {
        return Attribute::get(
            fn () => $this->is_installment ? "{$this->installment_number}/{$this->installment_total}" : null
        );
    }
}
