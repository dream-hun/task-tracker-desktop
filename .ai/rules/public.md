---
paths:
  - 'public/icon.*'
---

# Public

## Never treat vendor/nativephp/desktop/resources/build as an icon baseline
`resources/build/` is a staging directory that `native:build` overwrites with copies of this app's own `public/icon.*`, so it is not a pristine source of NativePHP's placeholder artwork. A test that diffed against it both skipped silently and passed for the wrong reason.

Two facts to rely on instead:
- The package ships placeholder artwork for `icon.png`, `IconTemplate.png` and `IconTemplate@2x.png` only — its own `resources/build/.gitignore` whitelists exactly those. There is no upstream `icon.ico`/`icon.icns`, so any check keyed on one being present skips forever on a clean install.
- Pin the stock md5s instead (see `nativePhpPlaceholderMd5()` in tests/Unit/DesktopIconTest.php, verified against nativephp/desktop 2.2.1). The `.ico`/`.icns` inherit the guarantee by embedding the same rasterisations as `icon.png` (icns `ic10` is byte-identical to `public/icon.png`).
