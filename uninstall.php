<?php
/**
 * Uninstall Canvas
 *
 * Runs when the plugin is deleted through the WordPress admin.
 * Removes all plugin data including database tables, options, and capabilities.
 *
 * @package Canvas
 */

// Exit if not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * This function removes:
 * - Database tables
 * - Options
 * - Capabilities from roles
 * - Scheduled events
 * - Transients
 */
function canvas_uninstall_cleanup() {
	global $wpdb;

	// Check if we should delete data (allow option to preserve).
	$settings = get_option( 'canvas_settings', array() );
	if ( ! empty( $settings['preserve_data_on_uninstall'] ) ) {
		return;
	}

	// Tables to remove.
	$tables = array(
		$wpdb->prefix . 'canvas_items',
		$wpdb->prefix . 'canvas_audit_log',
	);

	// Drop tables.
	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Options to remove.
	$options = array(
		'canvas_settings',
		'canvas_db_version',
		'canvas_activated_at',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Remove capabilities from roles.
	$capabilities = array(
		'manage_canvas',
		'view_canvas',
		'edit_canvas_content',
	);

	$roles = array( 'administrator', 'editor' );

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $capabilities as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	// Clear scheduled events.
	$scheduled_hooks = array(
		'canvas_daily_cleanup',
	);

	foreach ( $scheduled_hooks as $hook ) {
		$timestamp = wp_next_scheduled( $hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}

	// Clear transients.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_canvas_%' OR option_name LIKE '_transient_timeout_canvas_%'"
	);

	// Clear any cached data.
	wp_cache_flush();
}

// Run cleanup.
canvas_uninstall_cleanup();

// If multisite, run for all sites.
if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		canvas_uninstall_cleanup();
		restore_current_blog();
	}
}
