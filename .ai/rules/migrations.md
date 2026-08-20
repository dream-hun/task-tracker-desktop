---
paths:
  - 'database/migrations/**'
---

# Migrations

## Bump nativephp.version whenever you add a migration
NativePHP only migrates when the app version changed:

    shouldMigrateDatabase = store.get('migrated_version') !== app.getVersion()

It writes `migrated_version` into the user's data dir (`~/.config/<app>/nativephp.json`) after a successful migrate. Ship new migrations under a version that is already installed and they never run — the app boots against the old schema and every query on a new table 500s, while the rest of the app looks fine.

This actually happened: `documents`/`document_items` shipped while the version stayed at the `1.0.0` default, so installs that had already migrated 1.0.0 skipped them and the Billing page 500'd with "no such table: documents". Reinstalling does not help; only a version change does.

`tests/Feature/DesktopBundleIdentityTest.php` fails if the version is still the 1.0.0 default. To recover an install that already skipped a migration, delete `migrated_version` from that JSON file or bump the version and reinstall.
