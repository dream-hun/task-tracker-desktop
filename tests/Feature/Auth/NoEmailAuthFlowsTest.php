<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;

/**
 * This is a desktop app: it is served from http://127.0.0.1 on a port picked at
 * launch, so any link mailed out of it resolves only on the machine that sent
 * it, only while the app is running, and only on that launch's port. Emailed
 * verification and password-reset flows were dropped rather than shipped
 * broken, which also means the app needs no mail credentials at all.
 */
test('emailed verification is not enabled', function (): void {
    expect(Features::enabled(Features::emailVerification()))->toBeFalse();
});

test('emailed password reset is not enabled', function (): void {
    expect(Features::enabled(Features::resetPasswords()))->toBeFalse();
});

test('the user model does not require email verification', function (): void {
    expect(new User)->not->toBeInstanceOf(Illuminate\Contracts\Auth\MustVerifyEmail::class);
});

test('a user with an unverified email reaches the dashboard', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('a user with an unverified email reaches the documents and tasks modules', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('tasks.index'))->assertOk();
    $this->actingAs($user)->get(route('documents.index'))->assertOk();
});

test('a user with an unverified email reaches profile settings', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
});

test('the verification and password reset endpoints are gone', function (string $routeName): void {
    expect(Route::has($routeName))->toBeFalse();
})->with([
    'verification.notice',
    'verification.verify',
    'verification.send',
    'password.request',
    'password.email',
    'password.reset',
    'password.update',
]);
