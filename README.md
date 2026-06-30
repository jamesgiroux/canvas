# Canvas

A starter framework for building a **wp-admin area as a React single-page app** — a dark sidebar with a content area, using `@wordpress/components` and WordPress design language, talking to PHP over the REST API. It targets **WordPress 7.0** and **PHP 8.3**.

Canvas is a *scaffold*, not a finished plugin. You copy it, run one script to rebrand every identifier to your own plugin, and build from there.

---

## What it is

The core of Canvas is the admin UI and the plumbing that serves it:

- A **full-page React admin** — sidebar navigation + content area, rendered into a single root element, routed by the `page` query arg and the URL hash.
- **Single-responsibility PHP** — a thin `Plugin` loader wires focused components (admin menu, asset enqueue, REST router, migration runner), each implementing a `Registrable` contract and owning its own hooks.
- A **REST base controller** with capability checks, pagination, and sanitization helpers.
- **Settings** persisted via the Options API, with one class as the single source of truth for keys, defaults, schema, and sanitization.
- **Tooling & guardrails** — PHPCS, PHPStan, PHPUnit, `wp-env`, GitHub Actions CI, Dependabot, and a one-command initialization script.

It also ships **one example domain object** — `Item` — to demonstrate the full stack: a custom database table, a cached/multisite-aware model, REST CRUD, a list page, and a dashboard. This is a deliberate "we've set you up with a correct data layer" carve-out. **`Item`, its controller, its page, and the `canvas_items` table are meant to be replaced wholesale with your own domain.** If you only need a settings panel, delete them.

### Requirements

- PHP 8.3+
- WordPress 7.0+
- Node.js 20+
- Composer 2.0+

---

## Getting started

### 1. Copy and rename

Copy Canvas into your plugins directory, then run the initialization script (see [The initialization script](#the-initialization-script)) to rebrand it:

```bash
cd wp-content/plugins
git clone https://github.com/your-repo/canvas.git my-plugin
cd my-plugin
python3 bin/canvas-init.py            # interactive
# or: python3 bin/canvas-init.py --name "My Plugin" --yes
```

### 2. Install and build

```bash
composer install
npm install && npm run build
```

### 3. Activate

Activate the plugin in the WordPress admin. A new top-level **Canvas** menu (renamed to your plugin) appears.

### Local development

To work against a throwaway WordPress install (requires Docker), Canvas ships a `wp-env` config:

```bash
npm run env:start    # boots WordPress 7.0 on PHP 8.3 with the plugin mounted
npm start            # rebuilds the React app on change
npm run env:stop
```

### Commands

| Command | What it does |
|---------|--------------|
| `npm start` | Dev build with watch |
| `npm run build` | Production build (→ `build/`) |
| `npm run lint` | ESLint + Stylelint |
| `npm run format` | Prettier |
| `npm run env:start` / `env:stop` | Local WordPress via `wp-env` |
| `composer phpcs` / `phpcbf` | WordPress Coding Standards check / auto-fix |
| `composer phpstan` | Static analysis (level 5, WP stubs) |
| `composer lint` | `phpcs` + `phpstan` |
| `composer test` | Full PHPUnit suite |
| `composer test:unit` | Unit suite only (no WordPress needed) |

Run a single test with `vendor/bin/phpunit --filter test_method_name`.

---

## How it fits together

Understanding the data flow makes extending Canvas straightforward:

```
React SPA (src/)  ──fetch──►  REST controllers (includes/API/)  ──►  Models / Settings
   AppShell                     Base_Controller                      Base_Model (custom tables)
   stores + hooks               canvas/v1 namespace                  Settings (options)
```

- **`canvas.php`** defines constants, registers the autoloaders, and on `plugins_loaded` calls `Plugin::get_instance()->register()`.
- **`Plugin`** is a thin loader. It builds a list of components and calls `register()` on each:

  | Component | Responsibility |
  |-----------|----------------|
  | `Admin\Menu` | Registers admin pages; renders the React root. |
  | `Admin\Assets` | Enqueues the SPA on plugin screens; localizes `canvasData`. |
  | `API\Router` | Registers REST controllers on `rest_api_init`. |
  | `Database\Migration_Runner` | Runs schema migrations when the DB version is behind. |

- **The React app** mounts into `#canvas-root`. `AppShell.jsx` picks the view from the `page` query arg; state lives in `@wordpress/data` stores under `src/store/`, accessed through hooks in `src/hooks/`.

### Project layout

```
canvas.php                       # Bootstrap: constants, autoloaders, lifecycle hooks
uninstall.php                    # Self-contained data cleanup
bin/canvas-init.py               # Rename/initialize script (deletes itself)
includes/
├── class-plugin.php             # Thin component loader
├── class-installer.php          # Activation: tables, capabilities, options
├── Contracts/interface-registrable.php
├── Admin/{class-menu, class-assets}.php
├── API/                         # Base controller, Router, Items + Settings controllers
├── Settings/class-settings.php  # Single source of truth for settings
├── Models/                      # Base_Model, Item (example), Item_Status enum
└── Database/                    # Migrator, Migration_Runner, migrations/
src/
├── index.jsx                    # Entry point
├── components/{layout,primitives}/
├── pages/                       # Dashboard, Items, Settings
├── store/                       # @wordpress/data stores
├── hooks/ · constants/ · styles/
tests/                           # PHPUnit (Unit + Integration)
```

---

## Extending Canvas

The fastest way to learn the patterns is to copy the `Item` example and adapt it. Common recipes:

### Add an admin page

A new page touches four spots (all `'canvas'` references become your slug after init):

1. **Create the component** — `src/pages/Reports.jsx`:
   ```jsx
   import { __ } from '@wordpress/i18n';

   export default function Reports() {
       return (
           <div className="canvas-page-content">
               <div className="canvas-page-header">
                   <h1 className="canvas-page-title">{ __( 'Reports', 'canvas' ) }</h1>
               </div>
           </div>
       );
   }
   ```
2. **Register the menu page** in `Admin\Menu::register_pages()` — add to the `$subpages` array:
   ```php
   array( $slug . '-reports', __( 'Reports', 'canvas' ), self::VIEW_CAP ),
   ```
3. **Allow assets to load there** — add the screen to `Admin\Assets::is_plugin_screen()`:
   ```php
   $slug . '_page_' . $slug . '-reports',
   ```
4. **Route and link it** — add a branch in `AppShell.jsx`'s `renderContent()` and a nav item in `Sidebar.jsx`.

### Add a setting

Settings are owned entirely by `Settings`. Add the key in three places, then a control:

```php
// includes/Settings/class-settings.php
public static function defaults(): array {
    return array( /* … */, 'enable_widget' => false );
}
// in sanitize(): add 'enable_widget' to the bool arm
// in rest_args(): add 'enable_widget' => array( 'type' => 'boolean' )
```

Then add a `ToggleControl` to `src/pages/Settings.jsx`. The store and REST controller pick it up automatically.

### Add a model (custom table)

```php
// includes/Models/class-widget.php
namespace Canvas\Models;

final class Widget extends Base_Model {
    protected static string $table       = 'canvas_widgets';
    protected static array  $json_columns    = array( 'meta' );
    protected static array  $allowed_columns = array(  // SQL-injection allowlist
        'id', 'blog_id', 'name', 'status', 'meta', 'created_at', 'updated_at',
    );

    public static function find_active( int $limit = 50 ): array {
        return self::find_all( array( 'status' => 'active' ), 'created_at', 'DESC', $limit );
    }
}
```

Register its table in `Installer::create_tables()` (every table needs a `blog_id` column) and bump `CANVAS_DB_VERSION` in `canvas.php`. You get CRUD, object-cache-backed `find()`, `find_like()` search, multisite isolation, and `transaction()` for free.

### Add a REST controller

```php
// includes/API/class-widgets-controller.php
namespace Canvas\API;

final class Widgets_Controller extends Base_Controller {
    protected $rest_base = 'widgets';

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_items' ),
            'permission_callback' => array( $this, 'get_items_permissions_check' ),
        ) );
    }

    public function get_items( $request ): \WP_REST_Response {
        return $this->success_response( /* … */ );
    }
}
```

Register it in `API\Router::controllers()`:

```php
return array(
    new Items_Controller(),
    new Settings_Controller(),
    new Widgets_Controller(),
);
```

### Add a data store + hook

```js
// src/store/widgets/index.js
import { createReduxStore, register } from '@wordpress/data';
export const STORE_NAME = 'canvas/widgets';
const store = createReduxStore( STORE_NAME, { reducer: ( s = {} ) => s, actions: {}, selectors: {} } );
register( store );
export default store;
```

Import it in `src/store/index.js` (`import './widgets';`) and wrap it in a hook under `src/hooks/` following `useItems` / `useSettings`. Use the centralized REST paths in `src/constants/api.js` rather than hardcoding strings.

### Add a database migration

Create `includes/Database/migrations/001-add-index.php` defining a `canvas_migration_001()` function, then bump `CANVAS_DB_VERSION`. `Migration_Runner` runs it on the next admin request and records it so it never re-runs.

---

## The initialization script

`bin/canvas-init.py` is a standalone Python CLI (Python 3.8+) — it needs neither WordPress, Composer, nor npm, so it runs before anything is installed. It rewrites every Canvas identifier across the codebase, renames `canvas.php` → `<slug>.php`, and then **deletes itself**.

```bash
python3 bin/canvas-init.py                                   # interactive prompts
python3 bin/canvas-init.py --name "My Plugin" --yes          # non-interactive
python3 bin/canvas-init.py --name "My Plugin" --slug my-plugin \
    --namespace My_Plugin --prefix my_plugin --js myPlugin \
    --author "Jane Doe" --email jane@example.com \
    --uri https://example.com/my-plugin --desc "..." --yes
```

It prompts for the plugin name and derives sensible defaults for everything else:

| Input | Default (from "My Plugin") | Replaces |
|-------|----------------------------|----------|
| Display name | `My Plugin` | `Plugin Name:`, menu labels, docs |
| Slug / text domain (`--slug`) | `my-plugin` | text domain, admin slug, asset handles, REST namespace, store names |
| Namespace (`--namespace`) | `My_Plugin` | the `Canvas\` PHP namespace |
| Prefix (`--prefix`) | `my_plugin` / `MY_PLUGIN` | functions, options, tables, hooks, constants, capabilities |
| JS global (`--js`) | `myPlugin` | the `canvasData` global |

Optional metadata flags: `--author`, `--email`, `--uri`, `--author-uri`, `--desc`. Pass `--yes` (or `-y`) to skip prompts and the confirmation.

---

## Reference

### Capabilities

Added to the administrator role on activation; routes and pages gate on these (not `manage_options`):

| Capability | Purpose |
|------------|---------|
| `manage_canvas` | Full access including settings |
| `view_canvas` | View the admin interface |
| `edit_canvas_content` | Create/edit/delete items |

### Settings (`canvas_settings` option)

| Key | Type | Default |
|-----|------|---------|
| `site_title` | string | `''` |
| `items_per_page` | enum (`10`/`20`/`50`/`100`) | `20` |
| `notifications_enabled` | bool | `false` |
| `debug_mode` | bool | `false` |
| `preserve_data_on_uninstall` | bool | `false` |

### `Base_Model`

| Method | Description |
|--------|-------------|
| `find( int $id, bool $skip_cache = false )` | Find by primary key (object-cached). |
| `find_all( $where, $order_by, $order, $limit, $offset )` | Find with allowlisted conditions. |
| `find_like( $column, $term, … )` | Allowlisted LIKE search. |
| `count( $where )` | Count matching rows. |
| `insert()` / `update()` / `delete()` | CRUD (auto-clears cache). |
| `transaction( callable $cb )` | Run `$cb` atomically (commit / rollback + rethrow). |
| `clear_cache()` / `clear_all_cache()` | Invalidate cached rows. |

### `Base_Controller`

| Method | Description |
|--------|-------------|
| `check_permission( $cap )` | Capability check → `true` / `WP_Error`. |
| `sanitize_text/textarea/int/bool/email/url/enum()` | Input sanitization. |
| `get_pagination_params()` / `paginated_response()` | Pagination with `X-WP-Total` headers. |
| `success_response()` / `error_response()` | Standard responses. |

### `Settings`

| Method | Description |
|--------|-------------|
| `all()` / `get( $key, $fallback )` | Read settings (merged over defaults). |
| `update( array $updates )` | Sanitize + persist provided keys. |
| `defaults()` / `rest_args()` / `install_defaults()` | Schema and activation seeding. |

### Security, multisite & uninstall

- **Security** — column allowlist on dynamic SQL, `$wpdb->prepare()` throughout, capability checks on every route, and the controller's `sanitize_*()` helpers.
- **Multisite** — every query is scoped by `blog_id`; activation and uninstall iterate all sites.
- **Uninstall** (`uninstall.php`) drops tables, options, and capabilities unless `preserve_data_on_uninstall` is set. It is deliberately self-contained (WordPress loads it in isolation), so keep its literal names in sync with `Settings`.

### Continuous integration

`.github/workflows/ci.yml` runs PHPCS, PHPStan, and PHPUnit on PHP 8.3 & 8.4 plus `npm run lint` and `npm run build` on every push/PR. Dependabot opens weekly dependency-update PRs.

---

## License

GPL-2.0-or-later
