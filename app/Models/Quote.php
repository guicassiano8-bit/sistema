<?php

namespace App\Models;

use App\Enums\QuoteSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'asset_id',
        'date',
        'price',
        'source',
    ];

    protected $attributes = [
        'source' => 'manual',
    ];

    protected function casts(): array
    {
        return [
            'source' => QuoteSource::class,
            'date' => 'date',
            'price' => 'decimal:6',
        ];
    }

    // Relacionamentos

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
