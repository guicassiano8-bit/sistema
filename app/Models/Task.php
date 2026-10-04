<?php

namespace App\Models;

use App\Enums\TaskRank;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'recurring_task_id',
        'title',
        'description',
        'points',
        'gold',
        'rank',
        'scheduled_date',
        'occurrence_date',
        'original_date',
        'start_time',
        'end_time',
        'status',
        'completed_at',
        'rescheduled_count',
    ];

    protected $attributes = [
        'status' => 'pending',
        'rescheduled_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'points' => 'integer',
            'gold' => 'integer',
            'rank' => TaskRank::class,
            'scheduled_date' => 'date',
            'occurrence_date' => 'date',
            'original_date' => 'date',
            'completed_at' => 'datetime',
            'rescheduled_count' => 'integer',
        ];
    }

    // Relacionamentos

    public function recurringTask(): BelongsTo
    {
        return $this->belongsTo(RecurringTask::class);
    }

    /** Itens do Inventário a comprar nesta missão ("Fazer Compras"). */
    public function shoppingItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class);
    }

    public function pointTransactions(): MorphMany
    {
        return $this->morphMany(PointTransaction::class, 'source');
    }

    // Scopes

    /** Tarefas de um intervalo de datas. Usado pelo endpoint do FullCalendar. */
    public function scopeBetweenDates(Builder $query, string $start, string $end): void
    {
        $query->whereBetween('scheduled_date', [$start, $end]);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', TaskStatus::Pending);
    }

    /** Pendentes de dias anteriores a hoje (para o botão "trazer para hoje"). */
    public function scopeOverdue(Builder $query): void
    {
        $query->pending()->where('scheduled_date', '<', today());
    }

    // Accessors

    protected function isRecurring(): Attribute
    {
        return Attribute::get(fn () => $this->recurring_task_id !== null);
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(
            fn () => $this->status === TaskStatus::Pending && $this->scheduled_date->isBefore(today())
        );
    }

    protected function isDone(): Attribute
    {
        return Attribute::get(fn () => $this->status === TaskStatus::Done);
    }
}
