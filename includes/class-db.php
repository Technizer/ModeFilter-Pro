<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MODEP_DB {

	/**
	 * Creates the custom table for stock subscriptions.
	 */
	public static function create_table() : void {
		if ( ! function_exists( 'modep_table_name' ) ) {
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
		dbDelta( $sql );
	}

	/**
	 * Drops the custom table on uninstallation.
	 */
	public static function drop_table() : void {
		if ( ! function_exists( 'modep_table_name' ) ) {
			return;
		}

		global $wpdb;

		/**
		 * 1. We wrap the variable in esc_sql() to satisfy the "UnescapedDBParameter" check.
		 * 2. We use backticks to protect the identifier.
		 */
		$table_name = esc_sql( modep_table_name() );
		$query      = sprintf( 'DROP TABLE IF EXISTS `%s`', $table_name );

		/**
		 * We use a combination of disable and ignore to clear the DirectQuery warnings
		 * and the NotPrepared error, as DROP TABLE cannot be prepared with placeholders
		 * in versions older than WP 6.2.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable
	}
}