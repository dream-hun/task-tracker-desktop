---
paths:
  - eslint.config.js
  - composer.json
  - package.json
---

# General

## ESLint must ignore .claude — agent worktrees are full repo checkouts
A leftover worktree under `.claude/worktrees/` is a complete checkout, carrying its own `vendor/` and `nativephp/`. The root ignores (`vendor`, `nativephp/**/*`) are relative to the repo root, so they do not match those nested copies: one stale worktree produced 25,500 ESLint errors and broke `composer ci:check` outright.

`.claude` is in the ignores for that reason — do not remove it. The same worktree is also why `.claude` is in `nativephp.cleanup_exclude_files` (it once added 871MB to the package).

## Back up php.js and electron-builder.mjs before any composer operation
`post-update-cmd` runs `native:install --force --quiet --publish`, which recreates `nativephp/electron` and overwrites BOTH patched files: `php.js` loses the raw-inflate fix (back to `readStream.pipe(writeStream)`) and `electron-builder.mjs` goes back to `target: ['AppImage', 'deb']`. Confirmed by a `composer remove` in Aug 2026.

Copy both aside first, then diff and restore afterwards:

    md5sum nativephp/electron/php.js nativephp/electron/electron-builder.mjs

Two things that make this easy to miss. `native:install --quiet` can exit 1 during the composer run *without* republishing, so an apparently failed hook may have left the files alone — check, do not assume either way. And re-running the command by hand to see the error succeeds and *does* republish, so diagnosing it clobbers the files.

The `DesktopPhpBinaryExtractionTest` source guards catch the php.js half; nothing catches the electron-builder.mjs half, so verify that one by eye.

## Run npm run build before native:build — it does not build the frontend
`native:build` runs `npm ci` and electron-vite for the Electron wrapper, but it never runs the app's own Vite build. It copies `public/build` exactly as it finds it, so whatever assets are sitting there get packaged.

Change a `.tsx` file, package without `npm run build`, and the .deb ships the previous UI while the bundled `resources/js` shows the new source — the two disagree and the app runs the old one. Caught this shipping a sidebar change that never reached the bundle.

Always: `npm run build` first, then `native:build`, then the electron-builder step. Verify by grepping `public/build/assets/` for a string you just changed before packaging.
