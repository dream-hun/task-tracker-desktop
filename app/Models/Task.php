<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read bool $is_overdue
 * @property-read User $user
 */
#[Fillable(['title', 'description', 'status', 'priority', 'due_date'])]
#[Appends(['is_overdue'])]
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => TaskStatus::Pending->value,
        'priority' => TaskPriority::Medium->value,
    ];

    /**
     * Get the user the task belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Keep the completion timestamp in sync with the task status.
     */
    protected static function booted(): void
    {
        self::saving(function (Task $task): void {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status->isCompleted() ? now() : null;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Determine whether the task is past its due date.
     *
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => ! $this->status->isCompleted()
            && $this->due_date !== null
            && $this->due_date->isBefore(today()));
    }

    /**
     * Scope the query to tasks matching the given search term.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Scope the query to tasks with the given status.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, TaskStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope the query to tasks with the given priority.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withPriority(Builder $query, TaskPriority $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * Scope the query to tasks that are not completed yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNot('status', TaskStatus::Completed);
    }

    /**
     * Scope the query to open tasks that are past their due date.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNot('status', TaskStatus::Completed)
            ->whereDate('due_date', '<', today());
    }

    /**
     * Scope the query to show unfinished, soonest due and most important tasks first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function orderByUrgency(Builder $query): void
    {
        $query->orderByRaw('completed_at is not null')
            ->orderByRaw('due_date is null')
            ->oldest('due_date')
            ->orderByRaw('case priority when ? then 3 when ? then 2 when ? then 1 else 0 end desc', [
                TaskPriority::High->value,
                TaskPriority::Medium->value,
                TaskPriority::Low->value,
            ])
            ->latest();
    }
}
