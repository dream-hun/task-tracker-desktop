<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

test('a task can be created', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->post(route('tasks.store'), [
            'title' => 'Prepare the release notes',
            'description' => 'Summarize everything that shipped this week.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2026-09-01',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

    $task = $user->tasks()->sole();

    expect($task->title)->toBe('Prepare the release notes');
    expect($task->status)->toBe(TaskStatus::Pending);
    expect($task->priority)->toBe(TaskPriority::High);
    expect($task->due_date->toDateString())->toBe('2026-09-01');
    expect($task->completed_at)->toBeNull();
});

test('a task can be created without optional details', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->post(route('tasks.store'), [
            'title' => 'Book the venue',
            'description' => '',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => '',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

    $task = $user->tasks()->sole();

    expect($task->description)->toBeNull();
    expect($task->due_date)->toBeNull();
});

test('creating a task validates the submitted details', function (array $payload, string $invalidField): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->post(route('tasks.store'), array_merge([
            'title' => 'A valid title',
            'status' => 'pending',
            'priority' => 'medium',
        ], $payload));

    $response->assertSessionHasErrors($invalidField);

    expect($user->tasks()->count())->toBe(0);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'long title' => [['title' => str_repeat('a', 256)], 'title'],
    'unknown status' => [['status' => 'archived'], 'status'],
    'unknown priority' => [['priority' => 'urgent'], 'priority'],
    'invalid due date' => [['due_date' => 'next thursday'], 'due_date'],
]);

test('a task can be updated', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->pending()->create(['title' => 'Old title']);

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->patch(route('tasks.update', $task), [
            'title' => 'New title',
            'description' => 'With more context',
            'status' => 'in_progress',
            'priority' => 'low',
            'due_date' => '2026-10-05',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

    $task->refresh();

    expect($task->title)->toBe('New title');
    expect($task->status)->toBe(TaskStatus::InProgress);
    expect($task->priority)->toBe(TaskPriority::Low);
    expect($task->due_date->toDateString())->toBe('2026-10-05');
});

test('a task cannot be updated by another user', function (): void {
    $task = Task::factory()->create(['title' => 'Not yours']);

    $response = $this
        ->actingAs(User::factory()->create())
        ->patch(route('tasks.update', $task), [
            'title' => 'Hijacked',
            'status' => 'completed',
            'priority' => 'high',
        ]);

    $response->assertForbidden();

    expect($task->refresh()->title)->toBe('Not yours');
});

test('completing a task records when it was finished', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->pending()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->patch(route('tasks.status.update', $task), ['status' => 'completed']);

    $response->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Completed);
    expect($task->completed_at)->not->toBeNull();
});

test('reopening a task clears when it was finished', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->completed()->create();

    $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->patch(route('tasks.status.update', $task), ['status' => 'pending'])
        ->assertSessionHasNoErrors();

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Pending);
    expect($task->completed_at)->toBeNull();
});

test('the status of a task cannot be changed by another user', function (): void {
    $task = Task::factory()->pending()->create();

    $response = $this
        ->actingAs(User::factory()->create())
        ->patch(route('tasks.status.update', $task), ['status' => 'completed']);

    $response->assertForbidden();

    expect($task->refresh()->status)->toBe(TaskStatus::Pending);
});

test('a task can be deleted', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $response = $this
        ->actingAs($user)
        ->from(route('tasks.index'))
        ->delete(route('tasks.destroy', $task));

    $response->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

    $this->assertModelMissing($task);
});

test('a task cannot be deleted by another user', function (): void {
    $task = Task::factory()->create();

    $response = $this
        ->actingAs(User::factory()->create())
        ->delete(route('tasks.destroy', $task));

    $response->assertForbidden();

    $this->assertModelExists($task);
});

test('deleting a user deletes their tasks', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $user->delete();

    $this->assertModelMissing($task);
});
