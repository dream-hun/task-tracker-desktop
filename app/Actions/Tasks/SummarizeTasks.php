<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\User;

class SummarizeTasks
{
    /**
     * Count the user's tasks per status, plus totals and overdue tasks.
     *
     * @return array<string, int>
     */
    public function handle(User $user): array
    {
        $summary = ['total' => $user->tasks()->count()];

        foreach (TaskStatus::cases() as $status) {
            $summary[$status->value] = $user->tasks()->withStatus($status)->count();
        }

        $summary['overdue'] = $user->tasks()->overdue()->count();

        return $summary;
    }
}
