---
paths:
  - app/Models/Task.php
  - 'app/Models/Document*.php'
---

# Models

## Task completed_at is derived from status, in two places
`Task::booted()` has a `saving` hook that sets `completed_at` whenever `status` changes (now() for Completed, null otherwise), so every write path stays consistent without controllers touching the column.

Model events are muted while seeding (`DatabaseSeeder` uses `WithoutModelEvents`), so `TaskFactory::definition()` mirrors the same rule in a closure. Keep the two in sync — dropping the factory line silently produces completed tasks with a null `completed_at`, which sorts them as if they were still open (`orderByUrgency` orders on `completed_at is not null`).

## Document money lives in cents, and the totals math exists twice
Amounts are stored as integer cents (`unit_price_cents`, `discount_cents`); `unit_price` and `discount` are write-only Attribute mutators that accept major units, which is what the form requests submit. Never add a `decimal` money column.

Totals are never stored. `DocumentItem::total_cents` = round(quantity × unit_price_cents), and `Document` derives `subtotal_cents` / `tax_cents` / `total_cents` from the loaded `items`, so always eager load `items` when serialising documents (the accessors are in `$appends`).

The same arithmetic is mirrored in `resources/js/lib/documents.ts` (`calculateTotals`) so the form can preview totals before saving. Change one side and you must change the other, or the preview and the stored document disagree. `tests/Unit/DocumentTotalsTest.php` pins the PHP side.
