<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('tasks.index'));

    $response->assertRedirect(route('login'));
});

test('users only see their own tasks', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Ship the release']);
    Task::factory()->create(['title' => 'Someone else their task']);

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('tasks/index')
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', 'Ship the release')
    );
});

test('tasks can be filtered by status', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->inProgress()->create(['title' => 'Write the docs']);
    Task::factory()->for($user)->completed()->create(['title' => 'Draw the logo']);

    $response = $this->actingAs($user)->get(route('tasks.index', ['status' => 'in_progress']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', 'Write the docs')
        ->where('filters.status', 'in_progress')
    );
});

test('tasks can be filtered by priority', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->highPriority()->create(['title' => 'Fix the outage']);
    Task::factory()->for($user)->create(['title' => 'Water the plants', 'priority' => 'low']);

    $response = $this->actingAs($user)->get(route('tasks.index', ['priority' => 'high']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', 'Fix the outage')
    );
});

test('tasks can be searched by title and description', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Renew the domain', 'description' => null]);
    Task::factory()->for($user)->create(['title' => 'Call the bank', 'description' => 'Ask about the domain transfer']);
    Task::factory()->for($user)->create(['title' => 'Buy milk', 'description' => null]);

    $response = $this->actingAs($user)->get(route('tasks.index', ['search' => 'domain']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('tasks.data', 2)
        ->where('filters.search', 'domain')
    );
});

test('an invalid filter value is ignored', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(2)->create();

    $response = $this->actingAs($user)->get(route('tasks.index', ['status' => 'not-a-status']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('tasks.data', 2)
        ->where('filters.status', null)
    );
});

test('the task list summarizes the tasks of the user', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->pending()->count(2)->create(['due_date' => null]);
    Task::factory()->for($user)->inProgress()->create(['due_date' => null]);
    Task::factory()->for($user)->completed()->create(['due_date' => null]);
    Task::factory()->for($user)->overdue()->create();
    Task::factory()->overdue()->create();

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('stats.total', 5)
        ->where('stats.pending', 3)
        ->where('stats.in_progress', 1)
        ->where('stats.completed', 1)
        ->where('stats.overdue', 1)
    );
});

test('unfinished tasks are listed before completed ones', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->completed()->create(['title' => 'Already done', 'due_date' => null]);
    Task::factory()->for($user)->pending()->create(['title' => 'Due tomorrow', 'due_date' => now()->addDay()]);
    Task::factory()->for($user)->overdue()->create(['title' => 'Was due last week', 'due_date' => now()->subWeek()]);

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('tasks.data.0.title', 'Was due last week')
        ->where('tasks.data.1.title', 'Due tomorrow')
        ->where('tasks.data.2.title', 'Already done')
    );
});

test('the task list is paginated', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(12)->create();

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('tasks.data', 10)
        ->where('tasks.total', 12)
        ->where('tasks.last_page', 2)
    );
});
