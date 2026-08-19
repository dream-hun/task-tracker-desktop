<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard summarizes the tasks of the user', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->pending()->create(['due_date' => null]);
    Task::factory()->for($user)->completed()->create(['due_date' => null]);
    Task::factory()->completed()->create(['due_date' => null]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->where('stats.total', 2)
        ->where('stats.completed', 1)
        ->has('upcomingTasks', 1)
    );
});

test('the dashboard lists the most urgent unfinished tasks first', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->pending()->create(['title' => 'Later', 'due_date' => now()->addMonth()]);
    Task::factory()->for($user)->inProgress()->create(['title' => 'Sooner', 'due_date' => now()->addDay()]);
    Task::factory()->for($user)->count(6)->completed()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->has('upcomingTasks', 2)
        ->where('upcomingTasks.0.title', 'Sooner')
        ->where('upcomingTasks.1.title', 'Later')
    );
});
