# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Canvas is a **WordPress plugin starter framework** targeting **WordPress 7.0 / PHP 8.3**, not a finished product. Its identity is a **wp-admin React SPA** (sidebar + content area) using `@wordpress/components`, backed by REST + the Options API, with a custom-table data layer kept as a deliberate, swappable example. The `Item` model + controller + Items page are that example — copy/replace them for your domain. The `README.md` is an extensive usage guide with copy-paste recipes; consult it before inventing new patterns.

**To start a new plugin from this scaffold, run the init script** (see below) rather than hand-editing identifiers.

## Initialize a new plugin (rename script)

`python3 bin/canvas-init.py` is a standalone Python CLI (Python 3.8+, no WordPress/Composer/npm needed) that rebrands the scaffold to your plugin: it rewrites every Canvas identifier across the tree, renames `canvas.php` → `<slug>.php`, and then deletes itself.

```bash
python3 bin/canvas-init.py                      # interactive
python3 bin/canvas-init.py --name="My Plugin" --yes   # non-interactive
```

It replaces, via an ordered single-pass regex (longest token wins, mirroring PHP's `strtr`) in `bin/canvas-init.py`: `Canvas\` namespace, `canvas` text domain/slug, `canvas_`/`CANVAS_` prefixes, `canvas/v1` REST namespace, `canvas/items` store names, `canvasData` JS global, and the `manage_canvas`/`view_canvas`/`edit_canvas_content` capabilities. If you add new Canvas-derived tokens to the codebase, add a matching rule there.

## Commands

```bash
# JS/React (build output → build/ at repo root)
npm install
npm start            # dev build + watch
npm run build        # production build
npm run lint         # lint:js + lint:css (CI runs this; must be clean)
npm run env:start    # one-command local WordPress 7.0 via wp-env (Docker)

# PHP
composer install
composer phpcs       # WordPress Coding Standards (canvas.php, uninstall.php, includes/)
composer phpcbf      # auto-fix phpcs
composer phpstan     # static analysis (level 5, WP stubs); runs with --memory-limit=1G
composer lint        # phpcs + phpstan
composer test:unit   # PHPUnit unit suite (no WordPress required; uses mocks)
composer test        # full suite (integration needs the WP test library)
vendor/bin/phpunit --filter test_name   # single test
```

There is **no PHP entry point to "run"** — it loads inside WordPress. Use `npm run env:start` for a live environment.

## Architecture

### Bootstrap & autoloading (`canvas.php`)
`canvas.php` defines the `CANVAS_*` constants, registers the Composer autoloader (a **classmap** over `includes/` — PSR-4 would not work because files use WordPress naming like `class-base-model.php`), and a **fallback** `spl_autoload_register` that maps `Canvas\Sub\Thing_Name` → `includes/Sub/{class,interface,trait,enum}-thing-name.php` (it tries each prefix). On `plugins_loaded` it calls `Plugin::get_instance()->register()`. **Adding a class with a new symbol kind requires the right file prefix** (`interface-`, `trait-`, `enum-`), and adding any new file means `composer dump-autoload` for the classmap to pick it up (the fallback autoloader finds it immediately in dev).

### SRP component model (the key structural pattern)
`Plugin` is a **thin loader**, not a god-class. It builds a list of components and calls `register()` on each. Every component implements `Canvas\Contracts\Registrable` (`register(): void`) and owns one concern by adding its own hooks:
- `Admin\Menu` — admin menu pages + React root render. Holds the capability constants (`VIEW_CAP`, `MANAGE_CAP`).
- `Admin\Assets` — enqueues the SPA (plugin screens only) and localizes `canvasData`.
- `API\Router` — instantiates REST controllers on `rest_api_init`. **Register new controllers here.**
- `Database\Migration_Runner` — runs migrations on `admin_init` when the stored DB version is behind.

To add a feature area, write a `Registrable` and add it to the array in `Plugin::__construct()`.

### Settings — single source of truth (`Settings\Settings`)
**All** settings config (option name `canvas_settings`, defaults, REST arg schema, per-key sanitization via a PHP `match`) lives in `Settings`. The REST `Settings_Controller`, `Installer`, and `Admin\Assets` all defer to it. Never hardcode a settings key or default elsewhere — this class exists specifically because the keys used to diverge three ways.

### Data layer (`Models\Base_Model`)
Abstract static-method ORM over `$wpdb`. Subclasses set `$table`, `$primary_key`, `$json_columns`, `$allowed_columns`. Key behaviors:
- **`$allowed_columns` is a security allowlist** — columns not listed are silently dropped from WHERE/ORDER BY. A shared `build_where()` powers both `find_all()` and `count()`; `find_like()` does allowlisted LIKE searches (so models never hand-write SQL).
- **Multisite isolation is automatic** — every query injects `blog_id`; all tables need a `blog_id` column.
- **Caching uses the object cache, not transients** — `wp_cache_*` with a per-model group and a `last_changed` marker baked into the cache key, so `clear_all_cache()` invalidates everything at once and it works under Redis/Memcached. `update()`/`delete()` call `clear_cache($id)`. Don't reintroduce transient-based caching.
- Use `Base_Model::transaction(fn)` for atomic work (commits, or rolls back and rethrows). The old `begin_transaction`/`commit`/`rollback` trio was replaced.

`Item` demonstrates the pattern and uses the `Item_Status` backed enum (`enum-item-status.php`) — use `Item_Status::values()` wherever a list of valid statuses is needed (REST `enum`, sanitization) instead of duplicating arrays. The `Item`/`Items_Controller`/Items page/`canvas_items` table are the one example domain object; replace them wholesale for your own data.

### REST layer (`API\Base_Controller`)
Extends `WP_REST_Controller`; namespace comes from `Plugin::API_NAMESPACE`. Provides `check_permission()`, `sanitize_*()` helpers, and pagination (`get_pagination_params`/`paginated_response` with `X-WP-Total` headers). Note: do **not** name a response-shaping method `prepare_item_for_response` returning an array — that violates the parent's `WP_REST_Response|WP_Error` contract (PHPStan catches it); `Items_Controller` uses a private `prepare_item_data()` instead.

### Frontend (`src/`)
Single React app (`src/index.jsx` is the entry — wp-scripts 32 resolves `.jsx`). All three admin pages render the same `#canvas-root`; `AppShell.jsx` switches on the `page` query arg and uses the URL hash for sub-routing. State via `@wordpress/data` stores (`src/store/`), API paths centralized in `src/constants/api.js`. Styles use **Sass modules**: `src/styles/_tokens.scss` holds the design tokens; `main.scss` and each partial pull them in with `@use '…/tokens' as *` (not the deprecated `@import`).

## Conventions & gotchas

- **`declare(strict_types=1)` in every PHP file.** PHP 8.3+ idioms (enums, `match`, `??=`, first-class callable) are expected.
- **CI must stay green** (`.github/workflows/ci.yml`): PHPCS, PHPStan, PHPUnit on PHP 8.3 & 8.4, plus `npm run lint` and `npm run build`. Run `composer lint` and `npm run lint` before declaring PHP/JS work done.
- **PHPCS** (`phpcs.xml.dist`): WordPress + WordPress-Extra, PHP 8.3 compat, text domain `canvas`. Custom capabilities and the data layer's dynamic-but-prepared SQL are allowlisted there. `bin/` is excluded (the init script is a plain CLI tool).
- **PHPStan**: needs `--memory-limit=1G` (large WP stubs) — already in the composer script. `CANVAS_*` constants are provided to analysis via `.phpstan/bootstrap.php`.
- **Settings keys** are owned by `Settings`; `uninstall.php` is deliberately self-contained (WordPress loads it in isolation, so it uses literal option/table names — keep it in sync with `Settings::OPTION` and the `canvas_*` options).
- **Empty placeholder dir**: `includes/Database/migrations/` (add `NNN-description.php` files defining `canvas_migration_NNN()`); `languages/` for translations.
- **Renaming**: use `bin/canvas-init.py`. If you must hand-rename, mirror the ordered token rules in that script.
