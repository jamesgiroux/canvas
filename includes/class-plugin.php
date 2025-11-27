<?php
/**
 * Main Plugin Class
 *
 * The core plugin class that initializes all functionality.
 * Uses the Singleton pattern to ensure only one instance exists.
 *
 * @package Canvas
 */

namespace Canvas;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 *
 * Main entry point for the plugin. Handles:
 * - Hook registration
 * - Admin menu setup
 * - Asset enqueuing
 * - REST API registration
 * - Service initialization
 */
class Plugin {

	/**
	 * Single instance of this class.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	public const API_NAMESPACE = 'canvas/v1';

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	public const ADMIN_SLUG = 'canvas';

	/**
	 * Get the singleton instance.
	 *
	 * Creates the instance on first call, returns existing instance on subsequent calls.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce singleton pattern. Initializes all hooks.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * Registers all actions and filters for the plugin.
	 * Organized by functionality area for clarity.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Block editor integration (optional - for sidebar panels).
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// REST API registration.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Plugin initialization (after all plugins loaded).
		add_action( 'init', array( $this, 'init' ) );

		// Check for database migrations.
		add_action( 'admin_init', array( $this, 'check_migrations' ) );

		// AJAX handlers (if needed for non-REST endpoints).
		add_action( 'wp_ajax_canvas_action', array( $this, 'handle_ajax_action' ) );

		// Scheduled tasks.
		add_action( 'canvas_daily_cleanup', array( $this, 'run_daily_cleanup' ) );
	}

	/**
	 * Plugin initialization.
	 *
	 * Runs on 'init' hook. Good place for:
	 * - Registering post types
	 * - Registering taxonomies
	 * - Setting up scheduled events
	 *
	 * @return void
	 */
	public function init(): void {
		// Schedule daily cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'canvas_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'canvas_daily_cleanup' );
		}

		// Register any custom post types or taxonomies here.
		// $this->register_post_types();
	}

	/**
	 * Check and run database migrations.
	 *
	 * Compares stored DB version with current version.
	 * Runs migrations if an update is needed.
	 *
	 * @return void
	 */
	public function check_migrations(): void {
		$stored_version = get_option( 'canvas_db_version', '0.0.0' );

		if ( version_compare( $stored_version, CANVAS_DB_VERSION, '<' ) ) {
			// Run migrations.
			if ( class_exists( 'Canvas\\Database\\Migrator' ) ) {
				$migrator = new Database\Migrator();
				$migrator->run_migrations();
			}

			// Update stored version.
			update_option( 'canvas_db_version', CANVAS_DB_VERSION );
		}
	}

	/**
	 * Register admin menu pages.
	 *
	 * Creates the main menu item and submenus.
	 * Each page loads the same React app which routes based on page query param.
	 * Uses custom capabilities for access control.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		// Main menu page (Dashboard).
		add_menu_page(
			__( 'Canvas', 'canvas' ),           // Page title.
			__( 'Canvas', 'canvas' ),           // Menu title.
			'view_canvas',                       // Capability required.
			self::ADMIN_SLUG,                    // Menu slug.
			array( $this, 'render_admin_page' ), // Callback function.
			'dashicons-art',                     // Icon (dashicon or URL).
			30                                   // Position in menu.
		);

		// Dashboard submenu (same as main for first item).
		add_submenu_page(
			self::ADMIN_SLUG,
			__( 'Dashboard', 'canvas' ),
			__( 'Dashboard', 'canvas' ),
			'view_canvas',
			self::ADMIN_SLUG,
			array( $this, 'render_admin_page' )
		);

		// Items submenu.
		add_submenu_page(
			self::ADMIN_SLUG,
			__( 'Items', 'canvas' ),
			__( 'Items', 'canvas' ),
			'view_canvas',
			self::ADMIN_SLUG . '-items',
			array( $this, 'render_admin_page' )
		);

		// Settings submenu.
		add_submenu_page(
			self::ADMIN_SLUG,
			__( 'Settings', 'canvas' ),
			__( 'Settings', 'canvas' ),
			'manage_canvas',
			self::ADMIN_SLUG . '-settings',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 *
	 * Outputs the root element for the React application.
	 * The actual UI is rendered by JavaScript.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		// Security check.
		if ( ! current_user_can( 'view_canvas' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'canvas' ) );
		}

		// Output the React app root element.
		echo '<div id="canvas-root" class="canvas-admin-wrap"></div>';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Loads CSS and JavaScript for the admin interface.
	 * Only loads on plugin admin pages to avoid conflicts.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on our admin pages.
		if ( ! $this->is_canvas_admin_page( $hook_suffix ) ) {
			return;
		}

		// Get asset metadata from wp-scripts build.
		$asset_file = CANVAS_PATH . 'build/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array(),
				'version'      => CANVAS_VERSION,
			);

		// Enqueue the main JavaScript bundle.
		wp_enqueue_script(
			'canvas-admin',
			CANVAS_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true // Load in footer.
		);

		// Enqueue the main stylesheet.
		wp_enqueue_style(
			'canvas-admin',
			CANVAS_URL . 'build/index.css',
			array( 'wp-components' ), // WordPress components styles.
			$asset['version']
		);

		// Localize script with data for JavaScript.
		wp_localize_script(
			'canvas-admin',
			'canvasData',
			array(
				'apiUrl'      => rest_url( self::API_NAMESPACE ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'adminUrl'    => admin_url(),
				'pluginUrl'   => CANVAS_URL,
				'version'     => CANVAS_VERSION,
				'currentUser' => array(
					'id'          => get_current_user_id(),
					'displayName' => wp_get_current_user()->display_name,
					'capabilities' => array(
						'manage'  => current_user_can( 'manage_canvas' ),
						'view'    => current_user_can( 'view_canvas' ),
						'edit'    => current_user_can( 'edit_canvas_content' ),
					),
				),
				'settings'    => get_option( 'canvas_settings', array() ),
			)
		);

		// Set script translations for i18n.
		wp_set_script_translations( 'canvas-admin', 'canvas', CANVAS_PATH . 'languages' );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * Loads JavaScript for block editor integration (sidebar panels, etc.).
	 * Only loads when editing posts/pages.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
		// Get asset metadata.
		$asset_file = CANVAS_PATH . 'assets/build/editor.asset.php';

		// Skip if editor bundle doesn't exist.
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Enqueue editor JavaScript.
		wp_enqueue_script(
			'canvas-editor',
			CANVAS_URL . 'assets/build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Localize with editor-specific data.
		wp_localize_script(
			'canvas-editor',
			'canvasEditorData',
			array(
				'apiUrl' => rest_url( self::API_NAMESPACE ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Check if current page is a Canvas admin page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return bool True if this is a Canvas admin page.
	 */
	private function is_canvas_admin_page( string $hook_suffix ): bool {
		$canvas_pages = array(
			'toplevel_page_' . self::ADMIN_SLUG,
			self::ADMIN_SLUG . '_page_' . self::ADMIN_SLUG . '-items',
			self::ADMIN_SLUG . '_page_' . self::ADMIN_SLUG . '-settings',
		);

		return in_array( $hook_suffix, $canvas_pages, true );
	}

	/**
	 * Register REST API routes.
	 *
	 * Initializes all REST API controllers and registers their routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		// Register each controller.
		$controllers = array(
			new API\Items_Controller(),
			new API\Settings_Controller(),
		);

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Handle AJAX actions.
	 *
	 * Generic AJAX handler for actions that don't fit REST API pattern.
	 * Verifies nonce and dispatches to appropriate handler.
	 *
	 * @return void
	 */
	public function handle_ajax_action(): void {
		// Verify nonce.
		check_ajax_referer( 'canvas_ajax_nonce', 'nonce' );

		// Check capability.
		if ( ! current_user_can( 'view_canvas' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'canvas' ) ), 403 );
		}

		// Get the action type.
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

		// Dispatch to handler based on action type.
		switch ( $action_type ) {
			case 'example_action':
				// Handle example action.
				wp_send_json_success( array( 'message' => __( 'Action completed.', 'canvas' ) ) );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'canvas' ) ), 400 );
		}
	}

	/**
	 * Run daily cleanup tasks.
	 *
	 * Scheduled via wp_cron for maintenance tasks like:
	 * - Removing old audit log entries
	 * - Cleaning up temporary data
	 * - Generating reports
	 *
	 * @return void
	 */
	public function run_daily_cleanup(): void {
		// Example: Delete audit log entries older than retention period.
		$settings       = get_option( 'canvas_settings', array() );
		$retention_days = $settings['retention_days'] ?? 90;

		global $wpdb;
		$table_name = $wpdb->prefix . 'canvas_audit_log';
		$cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff
			)
		);
	}

	/**
	 * Get the API namespace.
	 *
	 * @return string The REST API namespace.
	 */
	public function get_api_namespace(): string {
		return self::API_NAMESPACE;
	}

	/**
	 * Get the admin slug.
	 *
	 * @return string The admin page slug.
	 */
	public function get_admin_slug(): string {
		return self::ADMIN_SLUG;
	}
}
