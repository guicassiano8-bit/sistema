<?php

namespace App\Models;

use Database\Factories\AssetBalanceUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Saldo lançado à mão, com o valor que o banco mostra. */
class AssetBalanceUpdate extends Model
{
    /** @use HasFactory<AssetBalanceUpdateFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'reference_date',
        'gross_balance',
        'net_balance',
    ];

    protected function casts(): array
    {
        return [
            'reference_date' => 'date',
            'gross_balance' => 'decimal:2',
            'net_balance' => 'decimal:2',
        ];
    }

    // Relacionamentos

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
