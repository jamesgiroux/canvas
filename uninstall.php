<?php
/**
 * Uninstall Canvas
 *
 * Runs when the plugin is deleted through the WordPress admin. Removes all
 * plugin data unless the `preserve_data_on_uninstall` setting is enabled.
 *
 * Self-contained by design: WordPress loads this file in isolation, so it does
 * not rely on the plugin's autoloader or classes.
 *
 * @package Canvas
 */

declare(strict_types=1);

// Exit if not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data for the current site.
 *
 * @return void
 */
function canvas_uninstall_cleanup(): void {
	global $wpdb;

	// Respect the preserve-data setting.
	$settings = get_option( 'canvas_settings', array() );
	if ( is_array( $settings ) && ! empty( $settings['preserve_data_on_uninstall'] ) ) {
		return;
	}

	// Drop custom tables.
	$tables = array(
		$wpdb->prefix . 'canvas_items',
	);
	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Remove options.
	$options = array(
		'canvas_settings',
		'canvas_db_version',
		'canvas_activated_at',
		'canvas_completed_migrations',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Remove custom capabilities from every role that may have them.
	$capabilities = array( 'manage_canvas', 'view_canvas', 'edit_canvas_content' );
	foreach ( wp_roles()->role_objects as $role ) {
		foreach ( $capabilities as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	// Drop any legacy transients left by older versions.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_canvas_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_canvas_' ) . '%'
		)
	);
}

// Run for the current site (single-site or the main site of a network).
canvas_uninstall_cleanup();

// On multisite, repeat for every site, then clear the object cache once.
if ( is_multisite() ) {
	$canvas_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $canvas_site_ids as $canvas_site_id ) {
		switch_to_blog( (int) $canvas_site_id );
		canvas_uninstall_cleanup();
		restore_current_blog();
	}
}

wp_cache_flush();
