<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends Model
{
    protected $fillable = [
        'name',
        'is_market_traded',
    ];

    protected function casts(): array
    {
        return [
            'is_market_traded' => 'boolean',
        ];
    }

    // Relacionamentos

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
