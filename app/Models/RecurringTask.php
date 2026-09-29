<?php

namespace App\Models;

use App\Enums\Frequency;
use Database\Factories\RecurringTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Molde de tarefa recorrente. As ocorrências reais ficam em tasks,
 * geradas pelo job diário.
 */
class RecurringTask extends Model
{
    /** @use HasFactory<RecurringTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'points',
        'frequency',
        'interval',
        'days_of_week',
        'day_of_month',
        'start_time',
        'end_time',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => Frequency::class,
            'points' => 'integer',
            'interval' => 'integer',
            'days_of_week' => 'array',
            'day_of_month' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // Relacionamentos

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Scopes

    /** Ativas e ainda dentro do período de vigência. */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_on', '<=', today())
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', today()));
    }
}
