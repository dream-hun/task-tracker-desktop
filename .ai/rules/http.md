---
paths:
  - 'app/Http/**'
  - 'app/Providers/**'
  - 'routes/**'
  - config/fortify.php
  - config/nativephp.php
  - routes/web.php
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

## No emailed auth flows — this app is served from loopback
Do not re-enable `Features::emailVerification()` or `Features::resetPasswords()`, and do not put `MustVerifyEmail` back on `App\Models\User` or a `verified` middleware back on routes.

NativePHP serves the packaged app from `http://127.0.0.1:<port>`, where the port is picked fresh at each launch from 8100-9000 (`nativephp/electron/electron-plugin/src/server/php.ts`). Any link Laravel mails out therefore resolves only on the machine that sent it, only while the app is running, only on that launch's port, and only after a second login in an external browser. Verification also hard-gated the whole app, so a packaged install was unusable after registration.

Recovery is passkeys (and 2FA recovery codes), both already enabled. Dropping these flows is also why the app needs no mail credentials at all — see the `cleanup_env_keys` rule for `config/nativephp.php`. `tests/Feature/Auth/NoEmailAuthFlowsTest.php` pins this; `email_verified_at` and the factory's `unverified()` state were deliberately kept so verification could be restored if a hosted version ever ships.

## Everything in .env ships to users unless cleanup_env_keys strips it
`native:build` copies the project `.env` into the packaged app and removes only keys matching `nativephp.cleanup_env_keys` (`vendor/nativephp/desktop/.../ManagesEnvFile.php::cleanEnvFile`). Anything left is shipped to every end user — plaintext in an unbundled build, inside the encrypted bundle otherwise, which is obfuscation and not secrecy.

So any new credential must be covered by a pattern here before it goes in `.env`. `*_API_KEY` is deliberately narrow: `*_KEY` would also strip `APP_KEY`, which the runtime needs to boot.

`tests/Feature/DesktopBundleSecretsTest.php` runs the real builder cleanup against a temp `.env` rather than reimplementing the glob matching, so a package upgrade that changes the semantics fails there instead of in a release.

## Strip APP_ENV and APP_DEBUG from the packaged .env
Electron passes `APP_ENV=production` and `APP_DEBUG=false` to the PHP process (`electron-plugin/src/server/php.ts`), but Laravel's .env load overwrites inherited environment variables — verified: with `APP_DEBUG=false` in the real environment and `APP_DEBUG=true` in .env, `env('APP_DEBUG')` returns `true`. The shipped file wins, so both keys must be in `cleanup_env_keys`. Once stripped, `config/app.php` defaults (`production`, `false`) apply.

This is a functional bug, not polish. With debug on, `NativeServiceProvider::rewriteDatabase()` ignores `NATIVEPHP_DATABASE_PATH` and points SQLite at `database_path()` inside the installed bundle, which is read-only under `/opt` after a .deb install. Debug off also restores the production password rules in `AppServiceProvider`.

`cleanup_exclude_files` needs `.env.*` too: `cleanEnvFile` only ever processes `.env`, so a sibling copy (a `.env.before_lerd` backup, say) ships with every key intact.

## NativePHP's FreshCommand is renamed at resolve time — leave it in place
`Native\Desktop\Commands\FreshCommand` declares `#[AsCommand('native:migrate:fresh')]` but inherits `$signature = 'migrate:fresh ...'` from Laravel's base command, and Laravel's constructor lets the signature win. The console loader then indexes it as `native:migrate:fresh` while the instance answers to `migrate:fresh`.

Two consequences, both fixed by the `$this->app->extend(...)->setName(...)` call in `register()`: Symfony aborted *every* `artisan list` and `artisan tinker` run with "registered under multiple names", and NativePHP's command occupied the real `migrate:fresh` slot.

The extend is idempotent, so it stays harmless if NativePHP fixes this upstream. `tests/Feature/Console/NativeFreshCommandTest.php` pins all three facts.

## `/` redirects to login — the desktop app has no landing page
The packaged app opens `APP_URL` on launch, so `/` is the first thing a user sees. It redirects: guests to `login`, authenticated users to `dashboard`. The starter kit's `welcome.tsx` (Laravel/Laracasts/Cloud marketing links) was deleted along with its `name === 'welcome'` case in `resources/js/app.tsx`.

Keep the route named `home` — the auth layouts link their logo at `home()`, and Fortify redirects to `fortify.home` after login. Do not reintroduce a landing page here without checking what the Electron window should open on.

`tests/Feature/WelcomeRedirectTest.php` pins both redirects. Note `tests/Feature/ExampleTest.php` has to follow redirects now.
