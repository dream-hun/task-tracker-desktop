<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\User;

final class SummarizeTasks
{
    /**
     * Count the user's tasks per status, plus totals and overdue tasks.
     *
     * @return array<string, int>
     */
    public function handle(User $user): array
    {
        $counts = $user->tasks()->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = ['total' => 0];

        foreach (TaskStatus::cases() as $status) {
            $count = $counts->get($status->value);
            $summary[$status->value] = is_numeric($count) ? (int) $count : 0;
            $summary['total'] += $summary[$status->value];
        }

        $summary['overdue'] = $user->tasks()->overdue()->count();

        return $summary;
    }
}
