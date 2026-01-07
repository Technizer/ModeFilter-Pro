<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Back-in-stock subscription + notifications for ModeFilter Pro.
 */
final class MODEP_Stock {

    private const NONCE_ACTION = 'modep_nonce';
    private const CACHE_GROUP = 'modep_stock';

    public static function init() : void {
        add_action( 'wp_ajax_modep_subscribe_stock',        [ __CLASS__, 'subscribe' ] );
        add_action( 'wp_ajax_nopriv_modep_subscribe_stock', [ __CLASS__, 'subscribe' ] );
        add_action( 'transition_post_status', [ __CLASS__, 'notify_on_restock' ], 10, 3 );
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'handle_wc_stock_change' ], 10, 3 );
    }

    /**
     * AJAX: Subscribe an email to back-in-stock notifications.
     */
    public static function subscribe() : void {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), self::NONCE_ACTION ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'modefilter-pro' ) ], 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! $product_id || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Please provide a valid email address.', 'modefilter-pro' ) ] );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || $product->is_in_stock() ) {
            wp_send_json_error( [ 'message' => __( 'This product is already in stock.', 'modefilter-pro' ) ] );
        }

        global $wpdb;
        $table_name = modep_table_name(); 

        /**
         * Fix for Line 65:
         * We build the query format string using sprintf to avoid direct interpolation.
         * Then we pass it to prepare for value sanitization.
         */
        $query_format = sprintf( 'SELECT id FROM `%s` WHERE product_id = %%d AND email = %%s LIMIT 1', esc_sql( $table_name ) );
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare(
            $query_format, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $product_id,
            $email
        ) ); 
        // phpcs:enable

        if ( $exists ) {
            wp_send_json_success( [ 'message' => __( 'You are already on the waitlist for this item.', 'modefilter-pro' ) ] );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $inserted = $wpdb->insert(
            $table_name,
            [
                'product_id'    => $product_id,
                'email'         => $email,
                'subscribed_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            wp_send_json_error( [ 'message' => __( 'Database error. Please try again later.', 'modefilter-pro' ) ] );
        }

        do_action( 'modep_stock_subscribed', $product_id, $email );
        wp_send_json_success( [ 'message' => __( 'Success! We will email you when it returns.', 'modefilter-pro' ) ] );
    }

    public static function handle_wc_stock_change( $product_id, $status, $product ) : void {
        if ( 'instock' === $status ) {
            $post = get_post( $product_id );
            if ( $post ) {
                self::notify_on_restock( 'publish', '', $post );
            }
        }
    }

    /**
     * Notify subscribers when product is back in stock.
     */
    public static function notify_on_restock( $new_status, $old_status, $post ) : void {
        if ( ! $post || 'product' !== $post->post_type || 'publish' !== $new_status ) {
            return;
        }

        $product = wc_get_product( $post->ID );
        if ( ! $product || ! $product->is_in_stock() ) {
            return;
        }

        global $wpdb;
        $table_name = modep_table_name();
        $product_id = absint( $post->ID );

        /**
         * Fix for Line 128:
         * Using sprintf to construct the query format without variable interpolation.
         */
        $query_format = sprintf( 'SELECT email FROM `%s` WHERE product_id = %%d', esc_sql( $table_name ) );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $subs = $wpdb->get_col( $wpdb->prepare(
            $query_format, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $product_id
        ) ); 
        // phpcs:enable

        if ( empty( $subs ) ) {
            return;
        }

        $product_name = $product->get_name();
        $product_url  = get_permalink( $product_id );
        
        /* translators: %s: Product name */
        $subject = sprintf( __( 'Back in Stock: %s', 'modefilter-pro' ), $product_name );
        
        /* translators: 1: Product name, 2: Product URL */
        $message = sprintf( 
            __( "Hello!\n\nThe item you were waiting for, %1\$s, is now back in stock.\n\nShop now: %2\$s", 'modefilter-pro' ),
            $product_name,
            $product_url
        );

        foreach ( $subs as $email ) {
            wp_mail( sanitize_email( $email ), $subject, $message );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $table_name, [ 'product_id' => $product_id ], [ '%d' ] );

        do_action( 'modep_stock_notified', $product_id, $subs );
    }
}