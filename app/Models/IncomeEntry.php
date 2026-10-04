<?php

namespace App\Models;

use App\Enums\IncomeType;
use Carbon\CarbonInterface;
use Database\Factories\IncomeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rendimentos: dividendos de FII, juros, JCP. */
class IncomeEntry extends Model
{
    /** @use HasFactory<IncomeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'reference_month',
        'payment_date',
        'amount',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => IncomeType::class,
            'reference_month' => 'date',
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    // Relacionamentos

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    // Scopes

    /** Rendimentos pagos num mês (pela data de pagamento). */
    public function scopePaidInMonth(Builder $query, CarbonInterface $month): void
    {
        $query->whereBetween('payment_date', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ]);
    }

    public function scopePaidInYear(Builder $query, int $year): void
    {
        $query->whereYear('payment_date', $year);
    }
}
