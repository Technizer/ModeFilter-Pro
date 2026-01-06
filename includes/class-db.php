<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ModeFilter Pro DB layer.
 *
 * Currently responsible only for the Back-in-Stock subscribers table.
 */
final class MODEP_DB {

	/**
	 * Create (or update) the Back-in-Stock subscribers table.
	 *
	 * Uses dbDelta() so schema changes can be applied safely on future versions.
	 *
	 * Table structure:
	 * - id          BIGINT UNSIGNED, primary key, auto-increment
	 * - product_id  BIGINT UNSIGNED, WooCommerce product ID
	 * - email       VARCHAR(255), subscriber email address
	 * - subscribed_at DATETIME, subscription timestamp
	 * - unique_subscriber (product_id, email) unique constraint
	 *
	 * @return void
	 */
	public static function create_table() : void {
		if ( ! function_exists( 'modep_table_name' ) ) {
			// Helpers not loaded – fail silently to avoid fatal errors.
			return;
		}

		global $wpdb;

		$table   = modep_table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			email VARCHAR(255) NOT NULL,
			subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_subscriber (product_id, email)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// dbDelta handles CREATE TABLE / ALTER TABLE as needed.
		dbDelta( $sql );
	}
}