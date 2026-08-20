<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * `nativephp/electron/php.js` is republished from vendor by `native:install --publish`
 * on every composer update, which silently reverts the raw-inflate fix this app needs.
 * The stock version pipes yauzl's decompressing stream straight to disk; that streaming
 * inflate stalls short of the end on current Node releases and leaves a truncated,
 * unrunnable PHP binary, so the packaged app dies with `spawn .../php/php ENOENT`.
 *
 * Extracting for real is the only check that catches it — a truncated binary is a
 * plausible-looking file of nearly the right size, so the test runs the result.
 *
 * @see .ai/rules/electron.md
 */
test('php.js extracts a complete, executable PHP binary', function (): void {
    $electron = base_path('nativephp/electron');
    $version = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
    $archive = base_path("vendor/nativephp/php-bin/bin/linux/x64/php-{$version}.zip");

    if (! is_dir($electron.'/node_modules/yauzl')) {
        $this->markTestSkipped("Electron toolchain is not installed: run npm install in {$electron}");
    }

    if (! is_file($archive)) {
        $this->markTestSkipped("No php-bin archive for PHP {$version} at {$archive}");
    }

    $buildPath = base_path('storage/framework/testing/php-binary-extraction');

    File::deleteDirectory($buildPath);
    File::ensureDirectoryExists($buildPath);

    $extraction = Process::path($electron)
        ->env([
            'NATIVEPHP_BUILDING' => '1',
            'NATIVEPHP_PHP_BINARY_PATH' => base_path('vendor/nativephp/php-bin/bin/'),
            'NATIVEPHP_PHP_BINARY_VERSION' => $version,
            'NATIVEPHP_BUILD_PATH' => $buildPath,
        ])
        ->timeout(120)
        ->run('node php.js --linux --x64');

    expect($extraction->successful())->toBeTrue($extraction->errorOutput());

    // php.js appends its own `php/` directory to NATIVEPHP_BUILD_PATH.
    $binary = $buildPath.'/php/php';

    expect($binary)->toBeFile()
        ->and(is_executable($binary))->toBeTrue('The extracted PHP binary is not executable.');

    $reported = Process::timeout(30)->run([$binary, '-v']);

    expect($reported->successful())->toBeTrue($reported->errorOutput())
        ->and($reported->output())->toContain("PHP {$version}");

    File::deleteDirectory($buildPath);
});

/**
 * The extraction test above only fails on a Node release that actually exhibits the
 * streaming-inflate stall (24+), so on an older toolchain a republished stock php.js
 * would sail through it. These assertions fail the moment the fix is reverted,
 * whatever Node is installed.
 */
test('php.js still inflates deflated entries in one shot', function (): void {
    $source = file_get_contents(base_path('nativephp/electron/php.js'));

    expect($source)
        ->toContain('decompress: false')
        ->toContain('inflateRaw')
        ->toContain('entry.uncompressedSize');
});

test('php.js does not pipe yauzl decompression straight to disk', function (): void {
    $source = file_get_contents(base_path('nativephp/electron/php.js'));

    expect($source)->not->toContain('readStream.pipe(writeStream)');
});
