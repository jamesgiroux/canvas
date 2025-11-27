# Canvas

A WordPress plugin starter framework with React admin interface, REST API patterns, and modern PHP architecture.

## Features

- **Full-Page React Admin**: Dark sidebar navigation with white content area, hiding WordPress admin UI
- **Modern PHP**: PHP 8.0+ with strict typing, PSR-4 autoloading
- **REST API**: Base controller with authentication, pagination, and sanitization
- **Data Layer**: Abstract models with CRUD operations, JSON column support, SQL injection protection, and transient caching
- **Database Migrations**: Version-controlled schema migrations
- **State Management**: WordPress data stores with custom hooks
- **Design System**: Consistent styling with SCSS and design tokens
- **Error Handling**: React Error Boundary for graceful error recovery
- **Accessibility**: WCAG 2.1 AA compliant with proper ARIA labels and keyboard navigation
- **Multisite Support**: Blog ID isolation for all data
- **Testing Ready**: PHPUnit and WordPress coding standards configured

## Requirements

- PHP 8.0+
- WordPress 6.4+
- Node.js 18+
- Composer 2.0+

## Installation

1. Clone or copy to your plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/your-repo/canvas.git
   ```

2. Install PHP dependencies:
   ```bash
   cd canvas
   composer install
   ```

3. Install JavaScript dependencies and build:
   ```bash
   npm install
   npm run build
   ```

4. Activate the plugin in WordPress admin.

## Development

### Start development server (with hot reload):
```bash
npm start
```

### Build for production:
```bash
npm run build
```

### Run PHP linting:
```bash
composer phpcs
```

### Run tests:
```bash
composer test
```

## What's Included

### PHP Components

| Component | Location | Description |
|-----------|----------|-------------|
| Plugin | `includes/class-plugin.php` | Main plugin class with singleton pattern, hook registration |
| Installer | `includes/class-installer.php` | Activation/deactivation, database setup, capabilities |
| Base_Model | `includes/Models/class-base-model.php` | Abstract ORM with CRUD, JSON columns, column validation |
| Item | `includes/Models/class-item.php` | Example model implementation |
| Base_Controller | `includes/API/class-base-controller.php` | REST API base with auth, pagination, sanitization |
| Items_Controller | `includes/API/class-items-controller.php` | Example CRUD endpoints |
| Settings_Controller | `includes/API/class-settings-controller.php` | Plugin settings endpoints |
| Migrator | `includes/Database/class-migrator.php` | Database migration system |

### React Components

| Component | Location | Description |
|-----------|----------|-------------|
| AppShell | `src/components/layout/AppShell.jsx` | Main app layout with routing |
| Sidebar | `src/components/layout/Sidebar.jsx` | Dark sidebar navigation |
| ErrorBoundary | `src/components/layout/ErrorBoundary.jsx` | Error catching and recovery |
| Badge | `src/components/primitives/Badge.jsx` | Status badges with variants |
| MetricCard | `src/components/primitives/MetricCard.jsx` | Dashboard metric display |

### Pages

| Page | Location | Description |
|------|----------|-------------|
| Dashboard | `src/pages/Dashboard.jsx` | Overview with metrics |
| Items | `src/pages/Items.jsx` | CRUD interface for items |
| Settings | `src/pages/Settings.jsx` | Plugin configuration |

### Data Stores

| Store | Location | Description |
|-------|----------|-------------|
| items | `src/store/items/` | Items CRUD state management |
| settings | `src/store/settings/` | Settings state management |

### Custom Hooks

| Hook | Location | Description |
|------|----------|-------------|
| useItems | `src/hooks/useItems.js` | Items CRUD operations |
| useItem | `src/hooks/useItems.js` | Single item operations |
| useSettings | `src/hooks/useSettings.js` | Settings operations |
| useSetting | `src/hooks/useSettings.js` | Single setting value |

## Directory Structure

```
canvas/
├── canvas.php              # Main plugin file
├── uninstall.php           # Cleanup on plugin deletion
├── includes/
│   ├── class-installer.php # Activation/deactivation
│   ├── class-plugin.php    # Main plugin class
│   ├── API/                # REST API controllers
│   │   ├── class-base-controller.php
│   │   ├── class-items-controller.php
│   │   └── class-settings-controller.php
│   ├── Database/           # Database layer
│   │   ├── class-migrator.php
│   │   └── migrations/
│   └── Models/             # Data models
│       ├── class-base-model.php
│       └── class-item.php
├── src/                    # React source
│   ├── index.jsx           # Entry point
│   ├── components/
│   │   ├── layout/         # AppShell, Sidebar, ErrorBoundary
│   │   └── primitives/     # Badge, MetricCard
│   ├── pages/              # Dashboard, Items, Settings
│   ├── store/              # WordPress data stores
│   ├── hooks/              # Custom React hooks
│   ├── constants/          # Design tokens and API paths
│   │   ├── index.js        # Consolidated exports
│   │   ├── api.js          # API endpoint constants
│   │   └── design.js       # Design system tokens
│   └── styles/             # SCSS stylesheets
├── build/                  # Compiled assets
├── tests/                  # PHPUnit tests
├── package.json
├── composer.json
├── phpcs.xml.dist
└── phpunit.xml.dist
```

## Configuration

### Settings Options

The plugin stores settings in `canvas_settings` option:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `site_title` | string | '' | Custom title for the installation |
| `items_per_page` | string | '20' | Default pagination size |
| `notifications_enabled` | bool | false | Enable notifications |
| `debug_mode` | bool | false | Enable debug logging |
| `log_retention` | string | '30' | Days to keep logs |
| `preserve_data_on_uninstall` | bool | false | Keep data when plugin is deleted |

### Capabilities

The plugin registers these capabilities:

| Capability | Granted To | Description |
|------------|------------|-------------|
| `manage_canvas` | Administrator | Full access including settings |
| `view_canvas` | Administrator | View the admin interface |
| `edit_canvas_content` | Administrator | Create/edit/delete items |

To grant capabilities to other roles:

```php
$role = get_role( 'editor' );
$role->add_cap( 'view_canvas' );
$role->add_cap( 'edit_canvas_content' );
```

## Multisite Support

Canvas fully supports WordPress multisite:

- All database tables include `blog_id` column
- Data is automatically isolated per site
- Capabilities are managed per-site
- Uninstall cleans up all sites

## Security

### SQL Injection Protection

The `Base_Model` class includes column whitelist validation:

```php
// In your model, define allowed columns:
protected static array $allowed_columns = array(
    'id', 'blog_id', 'title', 'status', 'created_at'
);
```

Only whitelisted columns can be used in WHERE clauses and ORDER BY.

### Capability Checks

All REST endpoints verify user capabilities:

```php
public function get_items_permissions_check( $request ): bool {
    return $this->check_permission( 'view_canvas' );
}
```

### Data Sanitization

Use the base controller's sanitization methods:

```php
$title = $this->sanitize_text( $request['title'] );
$count = $this->sanitize_int( $request['count'] );
$enabled = $this->sanitize_bool( $request['enabled'] );
```

## WordPress Hooks

### Actions

| Hook | Description |
|------|-------------|
| `canvas_daily_cleanup` | Daily cron for maintenance tasks |

### Filters

Extend with your own filters as needed.

## Accessibility

Canvas follows WCAG 2.1 AA guidelines:

### Navigation
- `aria-current="page"` on active navigation items
- `aria-label` on navigation regions
- Keyboard navigation support

### Icons
- Decorative icons use `aria-hidden="true"`
- Functional icons have appropriate labels

### Interactive Elements
- Buttons have descriptive `aria-label` attributes
- Loading states use `aria-busy` and `aria-live="polite"`
- Empty states use `role="status"`

### Lists
- Item lists use `role="list"` and `role="listitem"`
- Action buttons include item context in labels

## Testing

Canvas includes both unit and integration test examples.

### Running Tests

```bash
# Run all tests
composer test

# Run only unit tests (no WordPress required)
./vendor/bin/phpunit --testsuite unit

# Run integration tests (requires WordPress test suite)
./vendor/bin/phpunit --testsuite integration
```

### Test Structure

```
tests/
├── bootstrap.php           # Test environment setup
├── mocks/
│   └── wordpress-functions.php  # WP function mocks for unit tests
├── Unit/
│   └── Models/
│       └── BaseModelTest.php    # Unit test examples
└── Integration/
    └── ItemModelIntegrationTest.php  # Integration test examples
```

### Writing Unit Tests

Unit tests run without WordPress using mock functions:

```php
class MyModelTest extends TestCase {
    /**
     * @dataProvider validation_provider
     */
    public function test_validation( string $input, bool $expected ): void {
        $result = MyModel::validate( $input );
        $this->assertEquals( $expected, $result );
    }

    public function validation_provider(): array {
        return array(
            'valid input'   => array( 'test', true ),
            'invalid input' => array( '', false ),
        );
    }
}
```

### Writing Integration Tests

Integration tests require the WordPress test environment:

```php
class MyModelIntegrationTest extends WP_UnitTestCase {
    public function test_create_and_find(): void {
        $id = MyModel::insert( array( 'title' => 'Test' ) );
        $item = MyModel::find( $id );

        $this->assertNotNull( $item );
        $this->assertEquals( 'Test', $item->title );
    }
}
```

## Uninstall Behavior

When the plugin is deleted through WordPress admin:

1. Database tables are dropped (unless `preserve_data_on_uninstall` is true)
2. All options are removed
3. Capabilities are removed from roles
4. Scheduled events are cleared
5. Transients are cleaned up

## Usage Guide

### Creating a New Model

1. Create a new file in `includes/Models/`:

```php
<?php
namespace Canvas\Models;

class MyModel extends Base_Model {
    protected static string $table = 'canvas_my_table';
    protected static string $primary_key = 'id';
    protected static array $json_columns = array( 'meta' );

    // Define allowed columns for query validation
    protected static array $allowed_columns = array(
        'id', 'blog_id', 'name', 'status', 'meta', 'created_at', 'updated_at'
    );

    // Add custom finder methods
    public static function find_by_status( string $status ): array {
        return self::find_all( array( 'status' => $status ) );
    }
}
```

### Creating a REST Controller

1. Create a new file in `includes/API/`:

```php
<?php
namespace Canvas\API;

class My_Controller extends Base_Controller {
    protected $rest_base = 'my-endpoint';

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            'methods' => 'GET',
            'callback' => array( $this, 'get_items' ),
            'permission_callback' => array( $this, 'get_items_permissions_check' ),
        ] );
    }

    public function get_items_permissions_check( $request ): bool {
        return $this->check_permission( 'view_canvas' );
    }

    public function get_items( $request ) {
        // Your logic here
        return $this->success_response( $data );
    }
}
```

2. Register in `class-plugin.php`:

```php
public function register_rest_routes(): void {
    $controllers = array(
        new API\Items_Controller(),
        new API\Settings_Controller(),
        new API\My_Controller(), // Add your controller
    );
    // ...
}
```

### Creating a Data Store

1. Create a new directory in `src/store/`:

```javascript
// src/store/my-store/index.js
import { createReduxStore, register } from '@wordpress/data';

export const STORE_NAME = 'canvas/my-store';

const store = createReduxStore( STORE_NAME, {
    reducer: ( state = {}, action ) => state,
    actions: {},
    selectors: {},
} );

register( store );
export default store;
```

2. Import in `src/store/index.js`:

```javascript
import './my-store';
export { STORE_NAME as MY_STORE } from './my-store';
```

### Creating a Custom Hook

```javascript
// src/hooks/useMyHook.js
import { useSelect, useDispatch } from '@wordpress/data';
import { MY_STORE } from '../store';

export function useMyHook() {
    const data = useSelect( ( select ) =>
        select( MY_STORE ).getData()
    , [] );

    const { setData } = useDispatch( MY_STORE );

    return { data, setData };
}
```

### Using API Constants

Centralized API paths prevent typos and make refactoring easier:

```javascript
// Import from constants
import { API_PATHS } from '../constants/api';

// Use in API calls
const response = await apiFetch( { path: API_PATHS.ITEMS } );
const item = await apiFetch( { path: API_PATHS.ITEM( id ) } );
const settings = await apiFetch( { path: API_PATHS.SETTINGS } );

// Available constants
API_NAMESPACE    // 'canvas/v1'
API_PATHS.ITEMS  // '/canvas/v1/items'
API_PATHS.ITEM( id )  // '/canvas/v1/items/{id}'
API_PATHS.SETTINGS    // '/canvas/v1/settings'
```

### Adding a New Page

1. Create component in `src/pages/`:

```jsx
// src/pages/MyPage.jsx
import { __ } from '@wordpress/i18n';

export default function MyPage( { onNavigate } ) {
    return (
        <div className="canvas-page-content">
            <div className="canvas-page-header">
                <div className="canvas-page-header-content">
                    <h1 className="canvas-page-title">
                        { __( 'My Page', 'canvas' ) }
                    </h1>
                </div>
            </div>
            {/* Content */}
        </div>
    );
}
```

2. Add to `AppShell.jsx` routing:

```jsx
// In renderContent()
if ( page === 'canvas-mypage' ) {
    return <MyPage onNavigate={ navigate } />;
}
```

3. Add to `Sidebar.jsx` navigation:

```jsx
const mainItems = [
    // ...existing items
    {
        id: 'canvas-mypage',
        label: __( 'My Page', 'canvas' ),
        icon: 'admin-generic',
    },
];
```

4. Register admin page in `class-plugin.php`:

```php
add_submenu_page(
    self::ADMIN_SLUG,
    __( 'My Page', 'canvas' ),
    __( 'My Page', 'canvas' ),
    'view_canvas',
    self::ADMIN_SLUG . '-mypage',
    array( $this, 'render_admin_page' )
);
```

## Customization

### Plugin Name

1. Rename `canvas.php` and update plugin header
2. Update namespace in all PHP files (find/replace `Canvas\` → `YourPlugin\`)
3. Update text domain in PHP and JS files (find/replace `'canvas'` → `'your-plugin'`)
4. Update `package.json` name and `composer.json` name
5. Update table prefixes in models and installer
6. Update store names (`canvas/items` → `your-plugin/items`)

### Database Tables

1. Update `Installer::create_tables()` with your schema
2. Create migration files in `includes/Database/migrations/`
3. Update `CANVAS_DB_VERSION` when schema changes

### Capabilities

Modify `Installer::add_capabilities()` for your permission requirements.

## API Reference

### Base_Model Methods

| Method | Description |
|--------|-------------|
| `find( int $id )` | Find record by primary key |
| `find_all( array $where, string $order_by, string $order, int $limit, int $offset )` | Find with conditions |
| `count( array $where )` | Count matching records |
| `insert( array $data )` | Insert new record, returns ID |
| `update( int $id, array $data )` | Update record |
| `delete( int $id )` | Delete record |
| `begin_transaction()` | Start transaction |
| `commit()` | Commit transaction |
| `rollback()` | Rollback transaction |
| `encode_json( mixed $data )` | Encode data as JSON |
| `decode_json( string $json )` | Decode JSON string |
| `clear_cache( int $id )` | Clear cache for a specific record |
| `clear_all_cache()` | Clear all cached records for this model |

### Base_Model Caching

Models include built-in transient caching for `find()` operations:

```php
class MyModel extends Base_Model {
    // Enable/disable caching (default: true)
    protected static bool $cache_enabled = true;

    // Cache TTL in seconds (default: 1 hour)
    protected static int $cache_ttl = HOUR_IN_SECONDS;
}

// Cache is automatically used
$item = MyModel::find( 123 );

// Bypass cache when needed
$item = MyModel::find( 123, true ); // skip_cache = true

// Cache is automatically cleared on update/delete
MyModel::update( 123, $data ); // Clears cache
MyModel::delete( 123 );        // Clears cache

// Manually clear cache
MyModel::clear_cache( 123 );
MyModel::clear_all_cache();
```

### Base_Controller Methods

| Method | Description |
|--------|-------------|
| `check_permission( string $capability )` | Check user capability |
| `sanitize_text( mixed $value )` | Sanitize text input |
| `sanitize_int( mixed $value )` | Sanitize integer input |
| `sanitize_bool( mixed $value )` | Sanitize boolean input |
| `get_pagination_params( $request )` | Get pagination from request |
| `paginated_response( array $items, int $total, int $page, int $per_page )` | Create paginated response |
| `success_response( mixed $data, int $status )` | Create success response |
| `error_response( string $message, string $code, int $status )` | Create error response |

## License

GPL-2.0-or-later
