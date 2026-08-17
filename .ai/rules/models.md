---
paths:
  - app/Models/Task.php
---

# Models

## Task completed_at is derived from status, in two places
`Task::booted()` has a `saving` hook that sets `completed_at` whenever `status` changes (now() for Completed, null otherwise), so every write path stays consistent without controllers touching the column.

Model events are muted while seeding (`DatabaseSeeder` uses `WithoutModelEvents`), so `TaskFactory::definition()` mirrors the same rule in a closure. Keep the two in sync — dropping the factory line silently produces completed tasks with a null `completed_at`, which sorts them as if they were still open (`orderByUrgency` orders on `completed_at is not null`).
