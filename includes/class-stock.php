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
        add_action( 'admin_post_modep_unsubscribe_stock', [ __CLASS__, 'unsubscribe' ] );
        add_action( 'admin_post_nopriv_modep_unsubscribe_stock', [ __CLASS__, 'unsubscribe' ] );
        add_filter( 'wp_privacy_personal_data_exporters', [ __CLASS__, 'register_exporter' ] );
        add_filter( 'wp_privacy_personal_data_erasers', [ __CLASS__, 'register_eraser' ] );
    }

    /**
     * AJAX: Subscribe an email to back-in-stock notifications.
     */
    public static function subscribe() : void {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_nonce'] ) ), self::NONCE_ACTION ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'modefilter-pro' ) ], 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        $honeypot = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';
        if ( '' !== $honeypot ) {
            wp_send_json_success( [ 'message' => __( 'Thanks. Your request has been received.', 'modefilter-pro' ) ] );
        }

        if ( ! $product_id || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Please provide a valid email address.', 'modefilter-pro' ) ] );
        }

        $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $rate_key    = 'modep_stock_rate_' . md5( $product_id . '|' . strtolower( $email ) . '|' . $remote_addr );
        if ( get_transient( $rate_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Please wait a moment before trying again.', 'modefilter-pro' ) ], 429 );
        }
        set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

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
            $email = sanitize_email( $email );
            if ( ! is_email( $email ) ) {
                continue;
            }
            $unsubscribe_url = self::unsubscribe_url( $product_id, $email );
            $mail_message    = $message . "\n\n" . sprintf( __( 'Unsubscribe: %s', 'modefilter-pro' ), $unsubscribe_url );
            if ( wp_mail( $email, $subject, $mail_message ) ) {
                // Keep failed deliveries for a later retry rather than losing the subscriber.
                $wpdb->delete( $table_name, [ 'product_id' => $product_id, 'email' => $email ], [ '%d', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            }
        }

        do_action( 'modep_stock_notified', $product_id, $subs );
    }

    private static function unsubscribe_url( int $product_id, string $email ) : string {
        $token = hash_hmac( 'sha256', $product_id . '|' . strtolower( $email ), wp_salt( 'nonce' ) );
        return add_query_arg(
            [
                'action'     => 'modep_unsubscribe_stock',
                'product_id' => $product_id,
                'email'      => $email,
                'token'      => $token,
            ],
            admin_url( 'admin-post.php' )
        );
    }

    public static function unsubscribe() : void {
        $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
        $email      = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
        $token      = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        $expected   = hash_hmac( 'sha256', $product_id . '|' . strtolower( $email ), wp_salt( 'nonce' ) );
        if ( ! $product_id || ! is_email( $email ) || ! hash_equals( $expected, $token ) ) {
            wp_die( esc_html__( 'This unsubscribe link is invalid.', 'modefilter-pro' ), '', [ 'response' => 400 ] );
        }
        global $wpdb;
        $wpdb->delete( modep_table_name(), [ 'product_id' => $product_id, 'email' => $email ], [ '%d', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        wp_die( esc_html__( 'You have been unsubscribed from this stock notification.', 'modefilter-pro' ), esc_html__( 'Unsubscribed', 'modefilter-pro' ), [ 'response' => 200 ] );
    }

    public static function register_exporter( array $exporters ) : array {
        $exporters['modefilter-pro'] = [
            'exporter_friendly_name' => __( 'ModeFilter Pro stock notifications', 'modefilter-pro' ),
            'callback'               => [ __CLASS__, 'export_personal_data' ],
        ];
        return $exporters;
    }

    public static function export_personal_data( string $email_address, int $page = 1 ) : array {
        unset( $page );
        global $wpdb;
        $table = modep_table_name();
        $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, product_id, subscribed_at FROM {$table} WHERE email = %s", $email_address ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $data  = [];
        foreach ( (array) $rows as $row ) {
            $data[] = [
                'group_id'    => 'modefilter-pro-stock',
                'group_label' => __( 'Stock notifications', 'modefilter-pro' ),
                'item_id'     => 'modep-stock-' . absint( $row->id ),
                'data'        => [
                    [ 'name' => __( 'Product', 'modefilter-pro' ), 'value' => get_the_title( absint( $row->product_id ) ) ],
                    [ 'name' => __( 'Subscribed at', 'modefilter-pro' ), 'value' => sanitize_text_field( $row->subscribed_at ) ],
                ],
            ];
        }
        return [ 'data' => $data, 'done' => true ];
    }

    public static function register_eraser( array $erasers ) : array {
        $erasers['modefilter-pro'] = [
            'eraser_friendly_name' => __( 'ModeFilter Pro stock notifications', 'modefilter-pro' ),
            'callback'             => [ __CLASS__, 'erase_personal_data' ],
        ];
        return $erasers;
    }

    public static function erase_personal_data( string $email_address, int $page = 1 ) : array {
        unset( $page );
        global $wpdb;
        $deleted = $wpdb->delete( modep_table_name(), [ 'email' => sanitize_email( $email_address ) ], [ '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return [
            'items_removed'  => false !== $deleted && $deleted > 0,
            'items_retained' => false,
            'messages'       => [],
            'done'           => true,
        ];
    }
}
