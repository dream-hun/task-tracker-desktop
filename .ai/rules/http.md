---
paths:
  - 'app/Http/**'
  - 'app/Providers/**'
  - 'routes/**'
  - config/fortify.php
---

# HTTP layer

## PHPStan runs at level max, so nullable auth must be handled, not assumed
`$request->user()` is `App\Models\User|null` even on routes behind `auth` middleware — larastan has no way to know the middleware ran. Resolve it once per action and work from the local variable:

    $user = $request->user() ?? throw new AuthenticationException;

Do not chain off `$request->user()` repeatedly (it re-resolves the guard and re-triggers the error at every call site), and do not reach for `@var`, `assert()`, `@phpstan-ignore`, or a baseline — `phpstan.neon` deliberately has none, so a suppression there would be the only one in the repo. The same guard belongs in a FormRequest that needs the user, e.g. `ProfileUpdateRequest::rules()`.

## Prefer the typed accessors over `input()` / `config()`
At level max, `$request->input(...)`, `config(...)`, and `env(...)` are all `mixed`, so passing their result to a string parameter fails. Use `$request->string(...)` (a `Stringable`, so `->lower()->toString()` chains) and `config()->string('app.url')` — inside config files the `config()` helper still works because the repository is bound before the files load, while the `Config` facade is not yet available.

## `Route::inertia()` is untyped, so it cannot be named
Inertia registers `inertia` as a `Router` macro whose closure declares no return type. Larastan resolves the call as `mixed`, so chaining `->name(...)` fails at level max. Write the route out instead:

    Route::get('settings/appearance', fn () => Inertia::render('settings/appearance'))->name('appearance.edit');

Closure routes are already the norm here (`routes/settings.php` has one for the passkey well-known endpoint), so `route:cache` is not in play.
