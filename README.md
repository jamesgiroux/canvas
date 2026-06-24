# Canvas

A WordPress plugin starter framework with a React admin interface, REST API patterns, and a modern, single-responsibility PHP architecture. Targets **WordPress 7.0** and **PHP 8.3**.

Canvas is a *scaffold*, not an end-user plugin. Copy it, run the initialization script to rebrand it to your own plugin, and build from there.

## Features

- **Full-page React admin** — dark sidebar, white content area, hiding the standard admin UI.
- **Single-responsibility PHP** — a thin `Plugin` loader wires focused components (`Admin\Menu`, `Admin\Assets`, `API\Router`, `Database\Migration_Runner`) that each register their own hooks via a `Registrable` contract.
- **REST API** — base controller with capability checks, pagination, and sanitization helpers.
- **Data layer** — abstract models with CRUD, multisite isolation, JSON columns, an SQL-injection allowlist, a shared WHERE builder, LIKE search, and **object-cache-backed** caching (Redis/Memcached safe).
- **Single source of truth for settings** — option name, defaults, REST schema, and sanitization all live in one `Settings` class.
- **Modern PHP 8.3** — `declare(strict_types=1)` everywhere, backed enums, `match`, transactions via a callback.
- **Tooling & guardrails** — PHPCS (WordPress standards), PHPStan (level 5 + WP stubs), PHPUnit, `wp-env`, GitHub Actions CI, and Dependabot.
- **One-command initialization** — `bin/canvas-init.py` renames the scaffold to your plugin.

## Requirements

- PHP 8.3+
- WordPress 7.0+
- Node.js 20+
- Composer 2.0+

## Quick start

1. Copy Canvas into your plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/your-repo/canvas.git my-plugin
   cd my-plugin
   ```

2. **Initialize it as your own plugin** (rewrites all identifiers, renames the main file, then self-deletes):
   ```bash
   python3 bin/canvas-init.py
   # or non-interactively:
   python3 bin/canvas-init.py --name="My Plugin" --author="Jane Doe" --yes
   ```

3. Install dependencies and build:
   ```bash
   composer install
   npm install && npm run build
   ```

4. Activate the plugin in the WordPress admin.

To develop against a throwaway WordPress install, run `npm run env:start` (requires Docker) — it boots WordPress 7.0 on PHP 8.3 with the plugin mounted.

## The initialization script

`bin/canvas-init.py` is a standalone Python CLI tool (Python 3.8+) — it needs neither WordPress, Composer, nor npm, so it can run before anything is installed. It prompts for (or accepts as flags) the plugin name and derives sensible defaults for every other identifier:

| Input | Default (from "My Plugin") | Replaces |
|-------|----------------------------|----------|
| Display name | `My Plugin` | `Plugin Name:`, menu labels, docs |
| Slug / text domain | `my-plugin` | text domain, admin slug, asset handles, REST namespace, store names |
| Namespace | `My_Plugin` | `Canvas\` PHP namespace |
| Prefix | `my_plugin` / `MY_PLUGIN` | functions, options, tables, hooks, constants, capabilities |
| JS global | `myPlugin` | `canvasData` |

Flags: `--name`, `--slug`, `--namespace`, `--prefix`, `--js`, `--author`, `--email`, `--uri`, `--author-uri`, `--desc`, and `--yes` (skip prompts/confirmation). After rewriting every file and renaming `canvas.php` → `<slug>.php`, the script deletes itself.

## Development commands

```bash
# JavaScript / React (build output → build/)
npm start            # dev build with watch
npm run build        # production build
npm run lint         # ESLint + Stylelint (CI runs this)
npm run format       # Prettier
npm run env:start    # local WordPress via wp-env
npm run env:stop

# PHP
composer phpcs       # WordPress Coding Standards
composer phpcbf      # auto-fix coding standards
composer phpstan     # static analysis (level 5, WP stubs)
composer lint        # phpcs + phpstan
composer test        # full PHPUnit suite
composer test:unit   # unit suite only (no WordPress required)
```

Run a single test:

```bash
vendor/bin/phpunit --filter test_method_name
```

## Architecture

### Bootstrap and autoloading

`canvas.php` defines the `CANVAS_*` constants and registers two autoloaders:

1. **Composer classmap** over `includes/` — used in production and by the test/tooling layer. (A PSR-4 map cannot be used because files follow WordPress naming conventions, e.g. `class-base-model.php`.) Run `composer dump-autoload` after adding a class.
2. A **fallback** `spl_autoload_register` that resolves `Canvas\Sub\Thing_Name` to `includes/Sub/{class,interface,trait,enum}-thing-name.php`, trying each symbol-kind prefix. This finds newly added files immediately during development.

On `plugins_loaded`, the bootstrap calls `Plugin::get_instance()->register()`.

### The component model

`Plugin` is a thin loader. It builds a list of components and calls `register()` on each. Every component implements `Canvas\Contracts\Registrable` and owns exactly one concern:

| Component | Responsibility |
|-----------|----------------|
| `Admin\Menu` | Registers admin menu/submenu pages; renders the React root. Owns the capability constants. |
| `Admin\Assets` | Enqueues the SPA (plugin screens only); localizes `canvasData`. |
| `API\Router` | Instantiates REST controllers on `rest_api_init`. |
| `Database\Migration_Runner` | Runs migrations on `admin_init` when the DB version is behind. |

Add a feature area by writing a `Registrable` and appending it to the array in `Plugin::__construct()`.

### Settings — single source of truth

`Canvas\Settings\Settings` owns the option name (`canvas_settings`), the defaults, the REST argument schema, and per-key sanitization (a PHP `match`). The REST controller, installer, and asset localizer all defer to it, so the schema can never drift.

```php
use Canvas\Settings\Settings;

$all     = Settings::all();                       // merged over defaults
$perPage = Settings::get( 'items_per_page' );
Settings::update( array( 'debug_mode' => true ) );  // sanitized + persisted
```

### Data layer

Models extend `Canvas\Models\Base_Model` and set static configuration:

```php
final class My_Model extends Base_Model {
    protected static string $table       = 'canvas_things';
    protected static string $primary_key = 'id';
    protected static array  $json_columns    = array( 'meta' );
    protected static array  $allowed_columns = array(
        'id', 'blog_id', 'name', 'status', 'meta', 'created_at', 'updated_at',
    );
}
```

- **`$allowed_columns` is a security allowlist.** Columns not listed are silently dropped from WHERE/ORDER BY. A shared `build_where()` powers `find_all()` and `count()`; `find_like()` runs allowlisted LIKE searches so models never hand-write SQL.
- **Multisite isolation is automatic** — every query is scoped by `blog_id`; all tables need a `blog_id` column.
- **Caching uses the object cache** (`wp_cache_*`) with a per-model group and a `last_changed` marker in the cache key, so it is correct under external object caches and `clear_all_cache()` invalidates everything at once. `update()`/`delete()` clear the affected row.
- **Transactions** run through a callback that commits on success and rolls back (and rethrows) on failure:
  ```php
  Base_Model::transaction( function () {
      $id = Item::insert( /* … */ );
      Item::update( $id, array( 'status' => 'active' ) );
  } );
  ```

`Item` is the one worked example domain object; it uses the `Item_Status` backed enum — call `Item_Status::values()` wherever a list of valid statuses is needed. The `Item` model, `Items_Controller`, the Items page, and the `canvas_items` table are meant to be replaced wholesale with your own domain objects.

### REST layer

Controllers extend `Canvas\API\Base_Controller` (which extends `WP_REST_Controller`). The namespace comes from `Plugin::API_NAMESPACE` (`canvas/v1`). Register new controllers in `API\Router::controllers()`.

```php
final class Things_Controller extends Base_Controller {
    protected $rest_base = 'things';

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_items' ),
            'permission_callback' => array( $this, 'get_items_permissions_check' ),
        ) );
    }
}
```

### Frontend

`src/index.jsx` is the entry point. All three admin pages render the same `#canvas-root`; `AppShell.jsx` chooses the view from the `page` query arg and uses the URL hash for sub-routing. State lives in `@wordpress/data` stores under `src/store/`; REST paths are centralized in `src/constants/api.js`. Styles use Sass modules — design tokens live in `src/styles/_tokens.scss` and are consumed with `@use '…/tokens' as *` (not the deprecated `@import`).

## Project structure

```
canvas.php                       # Bootstrap: constants, autoloaders, lifecycle hooks
uninstall.php                    # Self-contained cleanup
bin/canvas-init.py               # Rename/initialize script (deletes itself)
includes/
├── class-plugin.php             # Thin component loader (singleton)
├── class-installer.php          # Activation/deactivation, tables, capabilities
├── Contracts/
│   └── interface-registrable.php
├── Admin/
│   ├── class-menu.php
│   └── class-assets.php
├── API/
│   ├── class-base-controller.php
│   ├── class-router.php
│   ├── class-items-controller.php
│   └── class-settings-controller.php
├── Settings/
│   └── class-settings.php
├── Models/
│   ├── class-base-model.php
│   ├── class-item.php
│   └── enum-item-status.php
└── Database/
    ├── class-migrator.php
    ├── class-migration-runner.php
    └── migrations/              # NNN-description.php files
src/                             # React source (see Architecture › Frontend)
tests/                           # PHPUnit (Unit + Integration)
.github/workflows/ci.yml         # CI: phpcs, phpstan, phpunit, lint, build
.wp-env.json                     # Local WordPress environment
phpstan.neon.dist                # Static analysis config
```

## Capabilities

Custom capabilities are added to the administrator role on activation:

| Capability | Purpose |
|------------|---------|
| `manage_canvas` | Full access including settings |
| `view_canvas` | View the admin interface |
| `edit_canvas_content` | Create/edit/delete items |

Routes and admin pages gate on these (not `manage_options`). Grant them to other roles as needed:

```php
get_role( 'editor' )->add_cap( 'view_canvas' );
```

## Settings

Stored in the `canvas_settings` option (defaults and schema owned by `Settings`):

| Key | Type | Default |
|-----|------|---------|
| `site_title` | string | `''` |
| `items_per_page` | enum (`10`/`20`/`50`/`100`) | `20` |
| `notifications_enabled` | bool | `false` |
| `debug_mode` | bool | `false` |
| `preserve_data_on_uninstall` | bool | `false` |

## Multisite, security, and uninstall

- **Multisite** — all tables carry `blog_id`; queries are isolated per site; activation and uninstall iterate every site.
- **Security** — column allowlist on dynamic SQL, `$wpdb->prepare()` everywhere, capability checks on every route, and the base controller's `sanitize_*()` helpers.
- **Uninstall** (`uninstall.php`) drops tables, options (`canvas_settings`, `canvas_db_version`, `canvas_activated_at`, `canvas_completed_migrations`), capabilities, and scheduled events — unless `preserve_data_on_uninstall` is set. It is deliberately self-contained (WordPress loads it in isolation), so it uses literal names; keep them in sync with `Settings`.

## Database migrations

`Migration_Runner` runs pending migrations on `admin_init` when the stored `canvas_db_version` is behind `CANVAS_DB_VERSION` (bump it in `canvas.php` when the schema changes). Add migration files to `includes/Database/migrations/` named `NNN-description.php`, each defining a `canvas_migration_NNN()` function. Initial tables are created with `dbDelta()` in `Installer::create_tables()`.

## Testing

```bash
composer test:unit                              # no WordPress required (mocks)
vendor/bin/phpunit --testsuite integration      # requires the WP test library
```

Unit tests run against `tests/mocks/wordpress-functions.php`. Data providers use the `#[DataProvider]` attribute (required by PHPUnit 12).

## Continuous integration

`.github/workflows/ci.yml` runs on every push/PR:

- **PHP** (8.3 and 8.4): `composer phpcs`, `composer phpstan`, `composer test:unit`.
- **JavaScript**: `npm run lint` and `npm run build`.

Dependabot (`.github/dependabot.yml`) opens weekly update PRs for Composer, npm (with `@wordpress/*` grouped), and GitHub Actions.

## API reference

### `Base_Model`

| Method | Description |
|--------|-------------|
| `find( int $id, bool $skip_cache = false )` | Find by primary key (object-cached). |
| `find_all( array $where, string $order_by, string $order, int $limit, int $offset )` | Find with allowlisted conditions. |
| `find_like( string $column, string $term, … )` | Allowlisted LIKE search. |
| `count( array $where )` | Count matching rows. |
| `insert( array $data )` / `update( int $id, array $data )` / `delete( int $id )` | CRUD. |
| `transaction( callable $cb )` | Run `$cb` atomically (commit / rollback + rethrow). |
| `clear_cache( int $id )` / `clear_all_cache()` | Invalidate cached rows. |
| `encode_json()` / `decode_json()` | JSON helpers. |

### `Base_Controller`

| Method | Description |
|--------|-------------|
| `check_permission( string $cap )` | Capability check returning `true`/`WP_Error`. |
| `sanitize_text/textarea/int/bool/email/url/enum()` | Input sanitization. |
| `get_pagination_params()` / `paginated_response()` | Pagination with `X-WP-Total` headers. |
| `success_response()` / `error_response()` | Standard responses. |

### `Settings`

| Method | Description |
|--------|-------------|
| `all()` | All settings merged over defaults. |
| `get( string $key, $fallback = null )` | One value. |
| `update( array $updates )` | Sanitize + persist provided keys. |
| `defaults()` / `rest_args()` | Defaults and REST schema. |
| `install_defaults()` | Seed the option on activation. |

## License

GPL-2.0-or-later
