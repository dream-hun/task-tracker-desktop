<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Native\Desktop\Commands\FreshCommand;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->renameNativeFreshCommand();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Give NativePHP's migrate:fresh override the name it is registered under.
     *
     * Native\Desktop\Commands\FreshCommand declares #[AsCommand('native:migrate:fresh')]
     * but inherits `$signature = 'migrate:fresh ...'` from Laravel's base command, and
     * Laravel's constructor lets the signature win. The console loader then indexes it
     * as `native:migrate:fresh` while the instance answers to `migrate:fresh`, which
     * breaks `artisan list` and `artisan tinker` outright and shadows the real
     * `migrate:fresh`. Renaming the resolved instance restores both commands.
     */
    private function renameNativeFreshCommand(): void
    {
        $this->app->extend(
            FreshCommand::class,
            fn (FreshCommand $command): FreshCommand => $command->setName('native:migrate:fresh'),
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
