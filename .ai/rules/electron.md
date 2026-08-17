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
