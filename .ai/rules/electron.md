---
paths:
  - nativephp/electron/php.js
  - 'nativephp/electron/**'
---

# Electron

## Never use streaming inflate to extract the PHP binary
php.js must read deflated zip entries raw (`decompress: false`) and inflate them in one shot via `zlib.inflateRaw`. Streaming inflate (`zlib.createInflateRaw()`, which is what yauzl does internally when it decompresses for you) stalls ~127KB short of the end on Node 24+/26 and never emits `end`. The failure is silent: you get a truncated, non-executable PHP binary and Electron then dies with `spawn .../php/php ENOENT`.

Two related traps in the same file:
- yauzl's entry streams are classic streams. Draining them with `stream/consumers` or async iteration hangs; use plain `data`/`end` listeners.
- The extraction must be awaited, otherwise the process exits before `chmod` runs and the binary is left mode 664 (`permission denied`).

Keep the post-extract size assertion against `entry.uncompressedSize` — it's what turns this class of silent truncation into a loud failure.

## "Electron uninstall" means the binary was never extracted
The repo root `.npmrc` sets `ignore-scripts=true`, so Electron's `postinstall` (`node install.js`, which downloads and extracts the ~114MB binary) never runs on `npm install`. Result: `node_modules/electron/dist` is empty, `path.txt` is missing, and electron-vite fails at `getElectronPath` with `Error: Electron uninstall`.

Fix without touching `.npmrc` (it is intentional):

    cd nativephp/electron/node_modules/electron && node install.js

If that exits 0 but still leaves `dist/` with only a stray `locales/` entry, its `extract-zip` dependency hit the same Node streaming-inflate bug described in the php.js rule. The zip is already cached and valid, so extract it directly:

    unzip -q ~/.cache/electron/*/electron-v<version>-linux-x64.zip -d dist
    printf 'electron' > path.txt

On Linux with `kernel.apparmor_restrict_unprivileged_userns=1`, Electron then aborts on the SUID sandbox. Fix once per install: `sudo chown root:root dist/chrome-sandbox && sudo chmod 4755 dist/chrome-sandbox`.

## Root ESLint lints php.js and nothing else under nativephp/
Everything in `nativephp/electron` is published from `vendor/nativephp/desktop/resources/electron` and is overwritten by `native:install --force --publish` (composer `post-update-cmd`), so app style rules must not apply to it — root `eslint.config.js` ignores `nativephp/**/*`. `php.js` is the one file we patch, so it is un-ignored and given `globals.node` (the root config only supplies browser globals, otherwise every `process`/`Buffer` use is `no-undef`).

Flat-config gotcha: the `!nativephp/electron/` line is load-bearing. ESLint never traverses into an ignored directory, so un-ignoring the directory has to come before un-ignoring the file, or `php.js` is silently skipped as "ignored by a matching pattern".

Prettier (`resources/` only) and `tsc` (`resources/js/**` only) already exclude this directory — ESLint was the odd one out. Note that a republish overwrites `php.js` with the vendor copy: CI will fail on import order again, which is a useful signal that the inflate fix above was lost.

## Never run two native:build processes at once
electron-builder's AppImage target stages into a fixed path, `nativephp/electron/dist/__appImage-x64`, and `createStageDirPath` does an `rm -rf` on it before every build. Starting a second `php artisan native:build` while one is still running deletes the first run's staging directory out from under it.

The first run then dies with a misleading error:
`cannot execute cause=fork/exec .../appimage-12.0.1/linux-x64/mksquashfs: no such file or directory`

The mksquashfs binary is fine — the missing directory is the `workingDir` in that message, not the binary. Do not clear the electron-builder cache in response; just let one build finish. Linux packaging takes ~15 minutes (the mksquashfs pass alone is ~10 for a 630 MB payload).

## native:build fails at the packaging target with a bogus ENOENT — run electron-builder directly
`php artisan native:build linux x64` packages `dist/linux-unpacked` fine, then dies at the packaging target with ENOENT on the packaging tool: `mksquashfs ... no such file or directory` for AppImage, `fpm process failed ENOENT` for deb.

Both tools are fine. Verify before chasing it: `~/.cache/electron-builder/appimage/appimage-12.0.1/linux-x64/mksquashfs -version` and `~/.cache/electron-builder/fpm@2.1.4/*/fpm --version` both run standalone, and spawning fpm from Node with `{env: process.env}` works. Do not clear the electron-builder cache or reinstall fpm — the cache is intact. Root cause is unresolved; the trigger is something about the environment Laravel's Process passes to the npm script, not a missing binary.

Workaround that produces the artifact: let `native:build` run first (it does the copy-to-build-dir, env cleaning, icon install, vendor prune, and populates `vendor/nativephp/desktop/resources/build`), then re-run just the builder from `nativephp/electron` with the same env vars it passes:

    APP_PATH=<repo> APP_URL=<app.url> NATIVEPHP_BUILDING=1 \
    NATIVEPHP_PHP_BINARY_PATH=<repo>/vendor/nativephp/php-bin/bin/ \
    NATIVEPHP_BUILD_PATH=<repo>/vendor/nativephp/desktop/resources/build \
    NATIVEPHP_APP_NAME=... NATIVEPHP_APP_ID=... NATIVEPHP_APP_VERSION=... \
    NATIVEPHP_APP_FILENAME=... NATIVEPHP_APP_AUTHOR=... NATIVEPHP_UPDATER_ENABLED=true \
    NATIVEPHP_UPDATER_CONFIG='{"provider":"spaces",...}' \
    npm run build:linux-x64

Because AppImage is first in `linux.target`, its failure aborts the run before deb is ever attempted. To get only the deb, temporarily set `target: ['deb']` in `electron-builder.mjs` and restore it after (that file is vendor-published).

(`php artisan tinker` and `php artisan list` used to be broken by a duplicate `native:migrate:fresh` registration, so older notes say you cannot dump the build env vars that way. That is fixed — see the `AppServiceProvider` rule.)

## native:build exits 0 even when the build fails — always check dist/
`php artisan native:build` returns exit code 0 even when electron-builder aborts. Never trust the exit code; verify `nativephp/electron/dist/` actually contains the artifact you asked for, and grep the log for `⨯`.

Known failure: the `deb` target dies with `spawn ~/.cache/electron-builder/fpm@.../fpm ENOENT` when invoked through `native:build`, even though that same fpm runs fine standalone and from a direct electron-builder call (root cause not established — it is not the sandbox, the cwd, or the env, all of which were ruled out). Workaround that produced a good package: let `native:build` finish packaging into `dist/linux-unpacked`, then run electron-builder directly against it:

    cd nativephp/electron && APP_URL=… APP_PATH=<repo> NATIVEPHP_BUILD_PATH=<repo>/build \
      NATIVEPHP_APP_ID=… NATIVEPHP_APP_NAME=… NATIVEPHP_APP_AUTHOR=… NATIVEPHP_APP_FILENAME=… \
      NATIVEPHP_APP_VERSION=… NATIVEPHP_DEEPLINK_SCHEME=… NATIVEPHP_BUILDING=true \
      npx electron-builder -p never --linux deb --x64 --prepackaged dist/linux-unpacked --config electron-builder.mjs

Omitting any of those env vars fails with a confusing schema error (`protocols.schemes[0] should be a string`) or `"path" argument must be of type string`.

`electron-builder.mjs` is vendor-published like `php.js`, so edits to it (e.g. `linux.target: ['deb']`) are reverted by `native:install --force --publish` on composer update — and unlike `php.js` no lint check catches the loss.

## Tests, not Node, are what catch a reverted php.js inflate fix
The raw-inflate patch in php.js was silently lost once already when `native:install --force --publish` (composer post-update-cmd) republished the vendor copy.

Do not rely on hitting the bug to notice. On Node 22 the stock streaming-inflate version extracts a perfectly good binary, so the failure only appears on Node 24+ — i.e. possibly first on a colleague's machine or in a release.

`tests/Feature/DesktopPhpBinaryExtractionTest.php` covers both angles: it extracts the real archive and runs the resulting binary (proof it is not truncated on whatever Node you have), and it asserts the source still contains `decompress: false`, `inflateRaw` and `entry.uncompressedSize` and no longer contains `readStream.pipe(writeStream)`. That second pair is what actually fails on a republish. If those tests fail after a composer update, restore php.js rather than editing the test.
