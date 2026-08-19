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
