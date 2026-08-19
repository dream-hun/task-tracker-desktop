<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Get every status value.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Determine whether the status marks a task as finished.
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
