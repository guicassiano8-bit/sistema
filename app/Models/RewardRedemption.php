<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RewardRedemption extends Model
{
    protected $fillable = [
        'reward_id',
        'cost_paid',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'cost_paid' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    // Relacionamentos

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /** O débito de pontos gerado por este resgate. */
    public function pointTransaction(): MorphOne
    {
        return $this->morphOne(PointTransaction::class, 'source');
    }
}
