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
 * @return list<array{width: int, size: int, offset: int}>
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
            'offset' => $entry['offset'],
        ];
    }

    return $entries;
}

/**
 * The md5 of each image embedded in an `.ico`, keyed by its pixel width.
 *
 * The `'8bit'` encoding on every `mb_*` call below is load-bearing: Pint's
 * `mb_str_functions` rule rewrites `substr`/`strlen` here, and the multibyte
 * versions count UTF-8 characters unless told otherwise, which silently
 * mangles the offsets into binary icon data.
 *
 * @return array<int, string>
 */
function icoFrames(string $path): array
{
    $binary = (string) file_get_contents($path);
    $frames = [];

    foreach (icoEntries($path) as $entry) {
        $frames[$entry['width']] = md5(mb_substr($binary, $entry['offset'], $entry['size'], '8bit'));
    }

    return $frames;
}

/**
 * The md5 of each image embedded in an `.icns`, keyed by its four-character
 * chunk type (`ic10` is the 1024px variant, `ic13` the 256px, and so on).
 *
 * @return array<string, string>
 */
function icnsChunks(string $path): array
{
    $binary = (string) file_get_contents($path);
    $chunks = [];
    $offset = 8;

    while ($offset < mb_strlen($binary, '8bit') - 8) {
        $chunk = unpack('a4type/Nlength', $binary, $offset);

        if ($chunk['length'] < 8) {
            break;
        }

        $chunks[$chunk['type']] = md5(mb_substr($binary, $offset + 8, $chunk['length'] - 8, '8bit'));
        $offset += $chunk['length'];
    }

    return $chunks;
}

/**
 * NativePHP ships placeholder artwork for these three files and no others — the
 * whitelist in the package's own `resources/build/.gitignore` covers `icon.png`,
 * `IconTemplate.png` and `IconTemplate@2x.png`, but neither `icon.ico` nor
 * `icon.icns`.
 *
 * The hashes are pinned here instead of being read back out of `vendor/` because
 * `resources/build/` is a staging directory that `native:build` overwrites with
 * copies of this app's own icons. Comparing against it skipped silently on a
 * clean install (where the `.ico`/`.icns` never exist) and compared our icons
 * against stale copies of themselves on a machine that had run a build.
 *
 * Verified against nativephp/desktop 2.2.1 (552cdd7).
 *
 * @return array<string, string>
 */
function nativePhpPlaceholderMd5(): array
{
    return [
        'icon.png' => '2bb370f9c43ae4a09c76bd8f79ba1f59',
        'IconTemplate.png' => '94933ff729793bd2de26f881cb6b8873',
        'IconTemplate@2x.png' => '94741ab505d03a9b42f146d4bb712952',
    ];
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
    expect(md5_file(desktopIcon($file)))->not->toBe(nativePhpPlaceholderMd5()[$file]);
})->with(array_keys(nativePhpPlaceholderMd5()));

/**
 * NativePHP ships no `.ico`/`.icns` placeholder to compare against, so these two
 * inherit the guarantee above by carrying the very same rasterisations as
 * `icon.png`, which the placeholder test does cover.
 */
test('the macOS icon embeds our own 1024px app icon', function (): void {
    expect(icnsChunks(desktopIcon('icon.icns')))
        ->toHaveKey('ic10', md5_file(desktopIcon('icon.png')));
});

test('the windows icon is rasterised from the same artwork as the macOS icon', function (): void {
    $ico = icoFrames(desktopIcon('icon.ico'));
    $icns = icnsChunks(desktopIcon('icon.icns'));

    expect($ico[16])->toBe($icns['icp4'])
        ->and($ico[32])->toBe($icns['ic11'])
        ->and($ico[64])->toBe($icns['ic12'])
        ->and($ico[128])->toBe($icns['ic07'])
        ->and($ico[256])->toBe($icns['ic13']);
});
