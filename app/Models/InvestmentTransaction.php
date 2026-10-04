<?php

namespace App\Models;

use App\Enums\InvestmentTransactionType;
use Database\Factories\InvestmentTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentTransaction extends Model
{
    /** @use HasFactory<InvestmentTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'account_id',
        'type',
        'date',
        'quantity',
        'unit_price',
        'total',
        'fees',
        'average_cost',
        'realized_profit',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvestmentTransactionType::class,
            'date' => 'date',
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'total' => 'decimal:2',
            'fees' => 'decimal:2',
            'average_cost' => 'decimal:6',
            'realized_profit' => 'decimal:2',
        ];
    }

    // Relacionamentos

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** Conta que pagou o aporte ou recebeu o resgate. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes

    /** Compras e aportes: dinheiro que entrou no investimento. */
    public function scopeInflows(Builder $query): void
    {
        $query->whereIn('type', [InvestmentTransactionType::Buy, InvestmentTransactionType::Deposit]);
    }

    /** Vendas e resgates: dinheiro que saiu do investimento. */
    public function scopeOutflows(Builder $query): void
    {
        $query->whereIn('type', [InvestmentTransactionType::Sell, InvestmentTransactionType::Withdrawal]);
    }
}
