<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(70) ? fake()->paragraph() : null,
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 week', '+1 month') : null,
            'completed_at' => fn (array $attributes): ?CarbonInterface => $attributes['status'] === TaskStatus::Completed ? now() : null,
        ];
    }

    /**
     * Indicate that the task has not been started.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Pending,
        ]);
    }

    /**
     * Indicate that the task is being worked on.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    /**
     * Indicate that the task has been finished.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Completed,
        ]);
    }

    /**
     * Indicate that the task is unfinished and past its due date.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDays(3),
        ]);
    }

    /**
     * Indicate that the task is a high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TaskPriority::High,
        ]);
    }
}
