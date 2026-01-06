<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Back-in-stock subscription + notifications for ModeFilter Pro.
 *
 * Handles:
 * - AJAX subscription endpoint.
 * - Notification emails when a product comes back in stock.
 */
final class MODEP_Stock {

    /**
     * Nonce action/key used by the plugin.
     */
    private const NONCE_ACTION = 'modep_nonce';

    /**
     * Cache group for stock subscriptions.
     */
    private const CACHE_GROUP = 'modep_stock';

    /**
     * Canonical table suffix (without prefix).
     */
    private const TABLE_SUFFIX = 'modep_stock';

    /**
     * Bootstrap.
     */
    public static function init() : void {
        add_action( 'wp_ajax_modep_subscribe_stock',        [ __CLASS__, 'subscribe' ] );
        add_action( 'wp_ajax_nopriv_modep_subscribe_stock', [ __CLASS__, 'subscribe' ] );

        add_action( 'transition_post_status', [ __CLASS__, 'notify_on_restock' ], 10, 3 );
    }

    /**
     * Build the literal SQL table name using WP's prefix + known suffix.
     *
     * This is safe because TABLE_SUFFIX is a class constant and $wpdb->prefix is controlled by WP.
     */
    private static function table_name() : string {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no query, only string build
        return (string) $wpdb->prefix . self::TABLE_SUFFIX;
    }

    /**
     * AJAX: subscribe an email to back-in-stock notifications for a product.
     *
     * Expected POST fields:
     * - product_id (int)
     * - email      (string)
     * - _nonce      (string)
     */
    public static function subscribe() : void {
        check_ajax_referer( self::NONCE_ACTION, '_nonce' );

        // Only read required POST keys (do NOT process the whole input).
        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! $product_id || ! $email || ! is_email( $email ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Invalid request.', 'modefilter-pro' ) ]
            );
        }

        $can_subscribe = apply_filters( 'modep_can_subscribe_stock', true, $product_id, $email );
        if ( ! $can_subscribe ) {
            wp_send_json_error(
                [ 'message' => __( 'Subscription is not allowed for this product.', 'modefilter-pro' ) ]
            );
        }

        global $wpdb;

        // Cache: already subscribed?
        $cache_key = 'exists_' . $product_id . '_' . md5( strtolower( $email ) );
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached && ! empty( $cached ) ) {
            wp_send_json_success(
                [ 'message' => __( 'You are already subscribed.', 'modefilter-pro' ) ]
            );
        }

        $table = self::table_name();

        // IMPORTANT: Do not interpolate table name inside $wpdb->prepare().
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( 
            $wpdb->prepare( 
                "SELECT id FROM `{$table}` WHERE product_id = %d AND email = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $product_id, 
                $email 
            ) 
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if ( $exists ) {
            wp_cache_set( $cache_key, 1, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );

            wp_send_json_success(
                [ 'message' => __( 'You are already subscribed.', 'modefilter-pro' ) ]
            );
        }

        // Insert subscription row (wpdb::insert handles the data formatting).
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->insert(
            $table,
            [
                'product_id' => $product_id,
                'email'      => $email,
            ],
            [ '%d', '%s' ]
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

        if ( ! $inserted ) {
            wp_send_json_error(
                [ 'message' => __( 'Could not save your subscription. Please try again.', 'modefilter-pro' ) ]
            );
        }

        wp_cache_set( $cache_key, 1, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        wp_cache_delete( 'subs_' . $product_id, self::CACHE_GROUP );

        do_action( 'modep_stock_subscribed', $product_id, $email );

        wp_send_json_success(
            [ 'message' => __( 'You will be notified when this product is back in stock.', 'modefilter-pro' ) ]
        );
    }

    /**
     * Notify subscribers when product is back in stock.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     */
    public static function notify_on_restock( $new_status, $old_status, $post ) : void {
        if ( ! ( $post instanceof WP_Post ) ) {
            return;
        }

        if ( 'product' !== $post->post_type ) {
            return;
        }

        if ( 'publish' !== $new_status ) {
            return;
        }

        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return;
        }

        if ( 'instock' !== $product->get_stock_status() ) {
            return;
        }

        $product_id = absint( $post->ID );
        if ( ! $product_id ) {
            return;
        }

        global $wpdb;

        $list_cache_key = 'subs_' . $product_id;
        $subs           = wp_cache_get( $list_cache_key, self::CACHE_GROUP );

        if ( false === $subs ) {
            $table = self::table_name();

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $subs = $wpdb->get_results( 
                $wpdb->prepare( 
                    "SELECT id, email FROM `{$table}` WHERE product_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $product_id 
                ) 
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            wp_cache_set( $list_cache_key, $subs, self::CACHE_GROUP, 2 * MINUTE_IN_SECONDS );
        }

        if ( empty( $subs ) || ! is_array( $subs ) ) {
            return;
        }

        $product_name = wp_strip_all_tags( (string) $product->get_name() );
        $product_url  = get_permalink( $product_id );

        $subject = sprintf(
            /* translators: %s: Product name. */
            __( 'Product Back in Stock: %s', 'modefilter-pro' ),
            $product_name
        );

        $message = sprintf(
            /* translators: 1: Product name, 2: Product URL. */
            __(
                "Good news!\n\nThe product \"%1\$s\" is now back in stock.\n\nView it here: %2\$s",
                'modefilter-pro'
            ),
            $product_name,
            (string) $product_url
        );

        $subject = apply_filters( 'modep_stock_email_subject', $subject, $product );
        $message = apply_filters( 'modep_stock_email_message', $message, $product );

        foreach ( $subs as $row ) {
            $row_email = isset( $row->email ) ? sanitize_email( (string) $row->email ) : '';
            if ( ! $row_email || ! is_email( $row_email ) ) {
                continue;
            }

            wp_mail( $row_email, (string) $subject, (string) $message );
        }

        // Delete all subscriptions for this product.
        $table = self::table_name();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(
            $table,
            [ 'product_id' => $product_id ],
            [ '%d' ]
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        wp_cache_delete( $list_cache_key, self::CACHE_GROUP );
        wp_cache_delete( 'exists_' . $product_id, self::CACHE_GROUP ); // best-effort; not all emails cached anyway

        do_action( 'modep_stock_notified', $product_id, $product, $subs );
    }
}