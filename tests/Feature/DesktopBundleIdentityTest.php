<?php

declare(strict_types=1);

/**
 * electron-builder bakes these into the installer: the app id becomes the macOS
 * bundle identifier, the Windows shortcut/uninstall key and the Linux .desktop
 * filename, so two apps left on NativePHP's placeholder collide on install.
 */
test('the desktop bundle has its own app id rather than the NativePHP placeholder', function (): void {
    expect(config('nativephp.app_id'))
        ->not->toBe('com.nativephp.app')
        ->toMatch('/^[a-z0-9]+(\.[a-z0-9-]+)+$/');
});

test('the desktop bundle names an author for the installer metadata', function (): void {
    expect(config('nativephp.author'))->toBeString()->not->toBeEmpty();
});

/**
 * NativePHP gates migrations on version equality:
 *
 *     shouldMigrateDatabase = store.get('migrated_version') !== app.getVersion()
 *
 * (`nativephp/electron/electron-plugin/src/server/php.ts`). It records the version in
 * the user's data dir after a successful migrate, so shipping new migrations under a
 * version that is already installed means they never run — the app boots against an
 * old schema and every query on a new table 500s. This is what left `documents`
 * missing on installs that had already migrated 1.0.0.
 *
 * So: bump `nativephp.version` in the same change that adds a migration. This test
 * fails if a migration is newer than the release the version number implies.
 */
test('the app version has been bumped since the migrations were last added', function (): void {
    $migrations = glob(database_path('migrations/*.php')) ?: [];

    expect($migrations)->not->toBeEmpty();

    $newest = basename((string) collect($migrations)->sortDesc()->first());

    expect(config()->string('nativephp.version'))
        ->not->toBe('1.0.0', "Version is still the 1.0.0 default while {$newest} exists; installs that already migrated 1.0.0 will skip it.")
        ->toMatch('/^\d+\.\d+\.\d+$/');
});
