<?php

declare(strict_types=1);

use Native\Desktop\Builder\Builder;

/**
 * NativePHP copies the project's .env into the packaged app and strips only the
 * keys matching nativephp.cleanup_env_keys, so anything left in that file ships
 * to every end user. These tests run the real builder cleanup rather than
 * reimplementing its glob matching, so a package upgrade that changes the
 * semantics fails here instead of in a release.
 */
function cleanedBuildEnv(string $contents): string
{
    $envPath = tempnam(sys_get_temp_dir(), 'nativephp-env-');

    file_put_contents($envPath, $contents);

    try {
        Builder::make()->cleanEnvFile($envPath);

        return (string) file_get_contents($envPath);
    } finally {
        @unlink($envPath);
    }
}

test('the packaged .env does not ship mail credentials', function (): void {
    $cleaned = cleanedBuildEnv(<<<'ENV'
        APP_KEY=base64:ZmFrZWFwcGtleWZvcnRlc3Rpbmdvbmx5MDAwMDA=
        MAIL_MAILER=resend
        RESEND_API_KEY=re_fake_key_for_testing
        ENV);

    expect($cleaned)
        ->not->toContain('RESEND_API_KEY')
        ->not->toContain('re_fake_key_for_testing');
});

test('the packaged .env keeps the app key the runtime needs to boot', function (): void {
    $cleaned = cleanedBuildEnv(<<<'ENV'
        APP_KEY=base64:ZmFrZWFwcGtleWZvcnRlc3Rpbmdvbmx5MDAwMDA=
        RESEND_API_KEY=re_fake_key_for_testing
        ENV);

    expect($cleaned)->toContain('APP_KEY=base64:ZmFrZWFwcGtleWZvcnRlc3Rpbmdvbmx5MDAwMDA=');
});

/**
 * The build copies the project tree into the app, pruning any path matching
 * nativephp.cleanup_exclude_files (merged with NativePHP's internal defaults)
 * via fnmatch against the project-relative path. A stale git worktree under
 * .claude once added 871MB to the package, carrying its own .env with it.
 */
function isPrunedFromBuild(string $relativePath): bool
{
    $patterns = array_unique(array_merge(
        config()->array('nativephp-internal.cleanup_exclude_files', []),
        config()->array('nativephp.cleanup_exclude_files', []),
    ));

    foreach ($patterns as $pattern) {
        if (fnmatch((string) $pattern, $relativePath)) {
            return true;
        }
    }

    return false;
}

test('developer tooling directories are pruned from the packaged app', function (string $relativePath): void {
    expect(isPrunedFromBuild($relativePath))->toBeTrue();
})->with([
    '.claude',
    '.agents',
    '.ai',
    '.github',
    'tests',
    'node_modules',
]);

test('directories the app needs at runtime are not pruned', function (string $relativePath): void {
    expect(isPrunedFromBuild($relativePath))->toBeFalse();
})->with([
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'vendor',
]);

test('the packaged .env does not ship database credentials', function (): void {
    $cleaned = cleanedBuildEnv(<<<'ENV'
        APP_KEY=base64:ZmFrZWFwcGtleWZvcnRlc3Rpbmdvbmx5MDAwMDA=
        DB_PASSWORD=hunter2
        REDIS_PASSWORD=hunter3
        ENV);

    expect($cleaned)
        ->not->toContain('hunter2')
        ->not->toContain('hunter3');
});

/**
 * Electron passes APP_ENV=production and APP_DEBUG=false to the PHP process, but
 * Laravel's .env load overwrites inherited environment variables, so a value left
 * in the shipped file wins. Debug on also moves NativePHP's SQLite database out of
 * NATIVEPHP_DATABASE_PATH and into the installed bundle, which is not writable.
 */
test('the packaged .env leaves the environment for the runtime to set', function (): void {
    $cleaned = cleanedBuildEnv(<<<'ENV'
        APP_KEY=base64:ZmFrZWFwcGtleWZvcnRlc3Rpbmdvbmx5MDAwMDA=
        APP_ENV=local
        APP_DEBUG=true
        ENV);

    expect($cleaned)
        ->not->toContain('APP_ENV')
        ->not->toContain('APP_DEBUG')
        ->toContain('APP_KEY=');
});

/**
 * cleanEnvFile only processes `.env` itself, so any sibling copy left in the project
 * ships with every key intact.
 */
test('stray env file copies are pruned from the packaged app', function (string $relativePath): void {
    expect(isPrunedFromBuild($relativePath))->toBeTrue();
})->with([
    '.env.before_lerd',
    '.env.backup',
    '.env.example',
]);

test('the env file the runtime boots from is not pruned', function (): void {
    expect(isPrunedFromBuild('.env'))->toBeFalse();
});
