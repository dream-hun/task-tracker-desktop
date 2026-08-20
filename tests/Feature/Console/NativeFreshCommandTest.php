<?php

declare(strict_types=1);

use Illuminate\Database\Console\Migrations\FreshCommand as LaravelFreshCommand;
use Illuminate\Support\Facades\Artisan;
use Native\Desktop\Commands\FreshCommand as NativeFreshCommand;

/**
 * Native\Desktop\Commands\FreshCommand declares #[AsCommand('native:migrate:fresh')]
 * but inherits Laravel's `migrate:fresh` signature, so without the rename applied in
 * AppServiceProvider the console loader indexes it under a name the instance does not
 * answer to. Symfony then aborts every `artisan list` and `artisan tinker` run, and
 * NativePHP's command takes over the real `migrate:fresh` slot.
 */
test('the NativePHP fresh command answers to the name it is registered under', function (): void {
    expect(app(NativeFreshCommand::class)->getName())->toBe('native:migrate:fresh');
});

test('resolving every console command does not blow up on a duplicate name', function (): void {
    expect(array_keys(Artisan::all()))
        ->toContain('native:migrate:fresh')
        ->toContain('migrate:fresh');
});

test('NativePHP does not shadow the real migrate:fresh command', function (): void {
    $command = Artisan::all()['migrate:fresh'];

    expect($command)->toBeInstanceOf(LaravelFreshCommand::class)
        ->and($command)->not->toBeInstanceOf(NativeFreshCommand::class);
});
