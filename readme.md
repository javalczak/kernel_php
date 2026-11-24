# Pure Kernel

PHP 8.3 micro-framework, FTP only. Built for cheap virtual hosting: no Composer on the server, no SSH, no Docker. Upload the files, fill in the credentials, done.

## Requirements

PHP 8.3+, MySQL 5.7+ / MariaDB, `pdo_mysql`.

## Quick start

```bash
git clone git@github.com:javalczak/kernel_php.git
cd kernel_php
```

Fill in `config/database.php`, then:

```bash
php tools/reset-db.php --force
php -S localhost:8000 router.php
```

`http://localhost:8000`, example CRUD under `/items`.

## Architecture

Front controller plus MVC, twelve engine classes and one interface, zero dependencies.

```
index.php -> Kernel::handle() -> Router::match() -> Controller::action() -> Response::send()
                                                          |
                                                  Repository -> Entity
```

| Class | Responsibility |
|---|---|
| `Kernel` | Bootstrap, services, routing, CSRF enforcement, controller call |
| `Router` | Routes with `{parameters}` |
| `Request` | Wrapper over `$_GET`, `$_POST`, `$_FILES`, `$_SERVER`, `$_COOKIE` |
| `Response` | HTTP response |
| `Controller` | `render()`, `redirect()`, `json()`, `db()`, `trans()` |
| `Database` | PDO: `fetchAll`, `fetchOne`, `insert`, `update`, `delete`, `transaction`, `identifier` |
| `Repository` | CRUD over entities of one table |
| `Entity` | Interface: `fromRow()`, `toRow()`, `getId()`, `withId()` |
| `ServiceContainer` | `bind()`, `singleton()` |
| `Translator` | Translations with `%placeholders%`, locale from the `locale` cookie |
| `Environment` | dev or prod, HTTP or HTTPS, where secrets and logs live |
| `ErrorHandler` | Uncaught exceptions and PHP errors: 500 page plus a log entry |
| `Csrf` | Token in the session |

Autoloader: `autoload.php` maps `Engine\` to `engine/` and `App\` to `src/`.

## Routes

`config/module.php`, first match wins, so `/items/create` goes before `/items/{id}`:

```php
['method' => 'GET',  'path' => '/items/{id}',        'controller' => ExampleController::class, 'action' => 'show'],
['method' => 'POST', 'path' => '/items/{id}/delete', 'controller' => ExampleController::class, 'action' => 'delete'],
```

An application can add its own `src/routes.php` in the same format. `'csrf' => false` on a route skips the token check, for webhooks and nothing else.

## Configuration

`config/app.php`:

| Key | Meaning |
|---|---|
| `env` | `dev` shows error details, `prod` hides them. `null` autodetects, localhost is `dev` |
| `private_dir` | Directory no visitor can reach. `null` autodetects: `../kernel-private`, else `var/` |
| `trusted_proxy` | Trust `X-Forwarded-Proto` when the host terminates HTTPS on a proxy |
| `hsts` | Send `Strict-Transport-Security`. Only once the site is HTTPS for good |

`config/database.php` holds the credentials. A copy of that file in the private directory wins, which keeps the password out of the web root without a code change.

## Security

- PDO with `ATTR_EMULATE_PREPARES = false`. Table and column names go through `Database::identifier()`, `ORDER BY` has its own validation, `UPDATE` and `DELETE` without `WHERE` throw.
- CSRF token verified by the kernel on every request other than GET, HEAD and OPTIONS, before the controller runs. Deleting is POST only.
- Session: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode, cookie only.
- Headers: nosniff, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, and a CSP limited to our own origin. Inline `<style>` and `<script>` carry a per request nonce.
- Errors in production: a plain 500, details go to `var/log/error-log.php`. That file starts with `<?php exit; ?>`, so a browser gets nothing even without `.htaccess`.
- Output escaped with `htmlspecialchars()`, locale cookie whitelisted, template names validated.

No authentication, no uploads, no rate limiting. Those go in per project.

## Deployment

1. Upload everything except `tools/`, `router.php`, `.ai/`, `CLAUDE.md`, `.idea/`, `.vscode/`, `.git/`.
2. Fill in `config/database.php`.
3. Check that `.htaccess` made it, FTP clients like to hide it.
4. Set `'env' => 'prod'` in `config/app.php`.
5. If the account has a directory above the web root, create `kernel-private/` there and move `database.php` into it.
6. Make `var/` writable, the error log goes there.
7. Create the tables locally and import a dump, or upload `tools/` for a minute and delete it after.

## Tools

```bash
php tools/reset-db.php --force   # drop + create + fixtures
php tools/smoke-test.php         # 24 checks, runs against the real database
```

`reset-db.php` also runs in a browser (`/tools/reset-db.php?token=...`), localhost and token only.
