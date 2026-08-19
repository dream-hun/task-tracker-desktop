<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('appearance.edit'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the appearance page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('settings/appearance'));
});
