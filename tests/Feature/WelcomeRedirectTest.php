<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * The packaged desktop app opens `/` on launch, so that route has to land the
 * user somewhere useful rather than on the starter kit's marketing page.
 */
test('the root route sends a guest to the login screen', function (): void {
    $this->get('/')->assertRedirect(route('login'));
});

test('the root route sends an authenticated user to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('the login screen the root route points at renders', function (): void {
    $this->followingRedirects()
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('auth/login'));
});

test('the starter kit welcome page is gone', function (): void {
    expect(resource_path('js/pages/welcome.tsx'))->not->toBeFile();
});
