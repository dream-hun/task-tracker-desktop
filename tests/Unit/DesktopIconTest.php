<?php

declare(strict_types=1);

/**
 * NativePHP copies `public/icon.*` into the Electron build directory on every
 * `native:run` / `native:build`, and electron-builder picks them up from there
 * as the installer, executable and dock icons. They are therefore asserted as
 * files rather than through the application.
 *
 * @see Native\Desktop\Drivers\Electron\Traits\InstallsAppIcon
 */
function desktopIcon(string $file): string
{
    return dirname(__DIR__, 2).'/public/'.$file;
}

/**
 * @return list<array{width: int, size: int}>
 */
function icoEntries(string $path): array
{
    $binary = (string) file_get_contents($path);
    $header = unpack('vreserved/vtype/vcount', $binary);

    expect($header['reserved'])->toBe(0)
        ->and($header['type'])->toBe(1);

    $entries = [];

    for ($index = 0; $index < $header['count']; $index++) {
        $entry = unpack(
            'Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vsize/Voffset',
            $binary,
            6 + ($index * 16)
        );

        $entries[] = [
            'width' => $entry['width'] === 0 ? 256 : $entry['width'],
            'size' => $entry['size'],
        ];
    }

    return $entries;
}

test('every icon the desktop installer needs is present', function (string $file): void {
    expect(desktopIcon($file))->toBeFile();
})->with(['icon.png', 'icon.ico', 'icon.icns', 'IconTemplate.png', 'IconTemplate@2x.png']);

test('the app icon is a 1024px png', function (): void {
    [$width, $height, $type] = (array) getimagesize(desktopIcon('icon.png'));

    expect($type)->toBe(IMAGETYPE_PNG)
        ->and($width)->toBe(1024)
        ->and($height)->toBe(1024);
});

test('the windows icon carries the 256px entry electron-builder requires', function (): void {
    $widths = array_column(icoEntries(desktopIcon('icon.ico')), 'width');

    expect($widths)->toContain(256)
        ->and($widths)->toContain(16, 32, 48);
});

test('the macOS icon is an icns declaring its retina sizes', function (): void {
    $binary = (string) file_get_contents(desktopIcon('icon.icns'));
    $header = unpack('a4magic/Nlength', $binary);

    expect($header['magic'])->toBe('icns')
        ->and($header['length'])->toBe(filesize(desktopIcon('icon.icns')));

    // ic10 is the 1024px (512@2x) variant macOS uses for Finder and the dock.
    expect($binary)->toContain('ic10');
});

test('the menu bar template icons are the exact pair macOS expects', function (string $file, int $expected): void {
    [$width, $height] = (array) getimagesize(desktopIcon($file));

    expect($width)->toBe($expected)->and($height)->toBe($expected);
})->with([
    ['IconTemplate.png', 16],
    ['IconTemplate@2x.png', 32],
]);

test('the icons are our own artwork rather than the NativePHP placeholders', function (string $file): void {
    $placeholder = dirname(__DIR__, 2).'/vendor/nativephp/desktop/resources/build/'.$file;

    if (! is_file($placeholder)) {
        $this->markTestSkipped('The NativePHP placeholder icons are not installed.');
    }

    expect(md5_file(desktopIcon($file)))->not->toBe(md5_file($placeholder));
})->with(['icon.png', 'icon.ico', 'icon.icns', 'IconTemplate.png', 'IconTemplate@2x.png']);
