<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Extra UI sugar for Store Mode:
 * - (Hybrid only) Product list column with Sellable/Catalog badge toggle
 * - (Hybrid only) Quick Edit + Bulk Edit select
 * - (Hybrid only) Admin list filters (views + dropdown) with counts (cached)
 * - Shop/archive query filter (hide catalog products from shop loops)
 */
final class MODEP_Catalog_Ext {

    /**
     * Per-product override meta (Hybrid only):
     * - missing/empty => Sellable (default)
     * - 'catalog'     => Catalog
     * - (optional) 'sell' is allowed, but treated the same as default Sellable
     */
    private const META_OVERRIDE = '_modep_catalog_override';

    /**
     * Admin query var for filtering.
     */
    private const QV_MODE = 'modep_mode';

    /**
     * Cache group + key for counts.
     */
    private const CACHE_GROUP      = 'modep_catalog_ext';
    private const CACHE_KEY_COUNTS = 'mode_counts_v1';

    /**
     * Cache TTL (seconds).
     *
     * Counts change only when an override meta is changed.
     * We also hard-clear cache on changes, so TTL is a safety net.
     */
    private const CACHE_TTL = 120;

    /**
     * Bootstrap.
     */
    public static function init() : void {
        if ( ! class_exists( 'MODEP_Catalog_Mode' ) ) {
            return;
        }

        // Hide catalog products from shop/archive/search loops.
        add_action( 'woocommerce_product_query', [ __CLASS__, 'filter_shop_query' ], 20 );

        if ( is_admin() ) {
            // Admin UI (Hybrid only).
            add_filter( 'manage_edit-product_columns',        [ __CLASS__, 'add_product_column' ] );
            add_action( 'manage_product_posts_custom_column', [ __CLASS__, 'render_product_column' ], 10, 2 );

            add_action( 'quick_edit_custom_box', [ __CLASS__, 'quick_edit_box' ], 10, 2 );
            add_action( 'bulk_edit_custom_box',  [ __CLASS__, 'bulk_edit_box' ], 10, 2 );
            add_action( 'save_post_product',     [ __CLASS__, 'save_from_edit' ], 10, 2 );

            add_filter( 'views_edit-product',    [ __CLASS__, 'add_views_links' ] );
            add_action( 'restrict_manage_posts', [ __CLASS__, 'add_dropdown_filter' ] );
            add_action( 'pre_get_posts',         [ __CLASS__, 'filter_admin_products_query' ] );

            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
            add_action( 'wp_ajax_modep_toggle_mode', [ __CLASS__, 'ajax_toggle_mode' ] );
        }
    }

    /**
     * Safely read a scalar value from POST data.
     * * Uses filter_input and sanitize_text_field to satisfy 
     * WordPress.Security.NonceVerification and ValidatedSanitizedInput warnings.
     *
     * @param string $key The POST key to read.
     * @return string Sanitize string value or empty string.
     */
    private static function post_scalar( string $key ) : string {
        // Use filter_input to avoid direct $_POST access warnings.
        $raw_value = filter_input( INPUT_POST, $key, FILTER_DEFAULT );

        if ( null === $raw_value || false === $raw_value || ! is_scalar( $raw_value ) ) {
            return '';
        }

        // Unslash and sanitize to ensure data integrity and security.
        return trim( sanitize_text_field( wp_unslash( (string) $raw_value ) ) );
    }
    /**
     * Helper: is global store mode Hybrid?
     */
    private static function is_hybrid_store() : bool {
        $s = MODEP_Catalog_Mode::get_settings();
        return isset( $s['global_mode'] ) && 'hybrid' === (string) $s['global_mode'];
    }

    /**
     * Helper: is global store mode Catalog?
     */
    private static function is_global_catalog() : bool {
        $s = MODEP_Catalog_Mode::get_settings();
        return isset( $s['global_mode'] ) && 'catalog' === (string) $s['global_mode'];
    }

    /**
     * Resolve product mode in Hybrid based on meta only:
     * - default Sellable
     * - Catalog if meta says 'catalog'
     */
    private static function get_hybrid_product_mode( int $post_id ) : string {
        $val = get_post_meta( $post_id, self::META_OVERRIDE, true );
        $val = is_string( $val ) ? $val : '';
        $val = sanitize_key( $val );

        return ( 'catalog' === $val ) ? 'catalog' : 'sell';
    }

    /**
     * Clear cached counts for Views/Dropdown.
     */
    private static function clear_counts_cache() : void {
        wp_cache_delete( self::CACHE_KEY_COUNTS, self::CACHE_GROUP );
    }

    /**
     * Enqueue admin assets for inline toggle on Products list (Hybrid only).
     */
    public static function enqueue_admin_assets() : void {
        if ( ! self::is_hybrid_store() ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return;
        }

        wp_enqueue_style(
            'modep-admin-dashboard',
            plugins_url( 'admin/admin-dashboard.css', MODEP_PLUGIN_FILE ),
            [],
            defined( 'MODEP_VERSION' ) ? MODEP_VERSION : false
        );

        // WooCommerce help tips (TipTip).
        wp_enqueue_script( 'jquery-tiptip' );

        wp_register_script(
            'modep-catalog-toggle',
            plugins_url( 'assets/js/catalog-toggle.js', MODEP_PLUGIN_FILE ),
            [ 'jquery', 'jquery-tiptip' ],
            defined( 'MODEP_VERSION' ) ? MODEP_VERSION : false,
            true
        );

        wp_localize_script(
            'modep-catalog-toggle',
            'MODEPCatalogToggle',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'modep_toggle_mode' ),
                // Two-state toggle only:
                'cycle'   => [ 'sell', 'catalog' ],
                'labels'  => [
                    'sell'    => __( 'Sellable', 'modefilter-pro' ),
                    'catalog' => __( 'Catalog', 'modefilter-pro' ),
                ],
            ]
        );

        wp_enqueue_script( 'modep-catalog-toggle' );
    }

    /**
     * AJAX: toggle product sellable/catalog (Hybrid only).
     */
    public static function ajax_toggle_mode() : void {
        check_ajax_referer( 'modep_toggle_mode', 'nonce' );

        if ( ! self::is_hybrid_store() ) {
            wp_send_json_error(
                [ 'message' => __( 'This action is only available in Hybrid store mode.', 'modefilter-pro' ) ]
            );
        }

        // Only read required keys (do not process whole $_POST).
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_ajax_referer() above.
        $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_ajax_referer() above.
        $mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( (string) $_POST['mode'] ) ) : '';

        if ( ! $post_id || ! current_user_can( 'edit_product', $post_id ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Permission denied.', 'modefilter-pro' ) ]
            );
        }

        if ( ! in_array( $mode, [ 'sell', 'catalog' ], true ) ) {
            $mode = 'sell';
        }

        // Default sell: keep DB clean (delete meta for sell).
        if ( 'sell' === $mode ) {
            delete_post_meta( $post_id, self::META_OVERRIDE );
        } else {
            update_post_meta( $post_id, self::META_OVERRIDE, 'catalog' );
        }

        // Counts changed, clear cache.
        self::clear_counts_cache();

        $labels = [
            'sell'    => __( 'Sellable', 'modefilter-pro' ),
            'catalog' => __( 'Catalog', 'modefilter-pro' ),
        ];

        wp_send_json_success(
            [
                'mode'  => $mode,
                'label' => $labels[ $mode ] ?? $labels['sell'],
                'class' => $mode,
            ]
        );
    }

    /**
     * Hide catalog products from shop-like queries.
     *
     * If global store mode = catalog => hide everything from normal Woo loops
     * (the idea is you’ll show catalog items via your own catalog widget/grid).
     *
     * If global store mode = sell => do nothing.
     * If hybrid => hide only products toggled to Catalog.
     *
     * @param WP_Query $q Query object.
     */
    public static function filter_shop_query( $q ) : void {
        if ( is_admin() || ! $q instanceof WP_Query || ! $q->is_main_query() ) {
            return;
        }

        $is_shop_like =
            $q->is_post_type_archive( 'product' )
            || $q->is_tax( [ 'product_cat', 'product_tag', 'product_brand' ] )
            || ( $q->is_search() && 'product' === $q->get( 'post_type' ) );

        if ( ! $is_shop_like ) {
            return;
        }

        // Global Catalog: hide everything from Woo loops.
        if ( self::is_global_catalog() ) {
            add_filter(
                'posts_results',
                static function ( $posts ) {
                    return [];
                },
                20,
                1
            );
            return;
        }

        // Global Sell: don't hide anything.
        if ( ! self::is_hybrid_store() ) {
            return;
        }

        // Hybrid: hide only product-level Catalog overrides.
        $hash = spl_object_hash( $q );

        add_filter(
            'posts_results',
            static function ( $posts, $query ) use ( $hash ) {
                if ( ! $query instanceof WP_Query || spl_object_hash( $query ) !== $hash ) {
                    return $posts;
                }

                if ( empty( $posts ) ) {
                    return $posts;
                }

                $filtered = [];

                foreach ( $posts as $post ) {
                    $post_id = isset( $post->ID ) ? (int) $post->ID : 0;
                    if ( ! $post_id ) {
                        continue;
                    }

                    // Only hide if explicitly catalog.
                    if ( 'catalog' === self::get_hybrid_product_mode( $post_id ) ) {
                        continue;
                    }

                    $filtered[] = $post;
                }

                return $filtered;
            },
            20,
            2
        );
    }

    /** ---------- Admin column (Hybrid only) ---------- */

    public static function add_product_column( array $columns ) : array {
        if ( ! self::is_hybrid_store() ) {
            // In Sell/Catalog global modes, do not show per-product mode UI.
            return $columns;
        }

        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;

            if ( in_array( $key, [ 'price', 'product_cat', 'product_tag' ], true ) ) {
                $new['modep_catalog_mode'] = sprintf(
                    '%1$s %2$s',
                    esc_html__( 'Product Mode', 'modefilter-pro' ),
                    '<span class="woocommerce-help-tip" data-tip="' .
                        esc_attr__(
                            'Hybrid store mode: click to switch this product between Sellable and Catalog.',
                            'modefilter-pro'
                        ) .
                    '"></span>'
                );
            }
        }

        return $new;
    }

    public static function render_product_column( string $column, int $post_id ) : void {
        if ( 'modep_catalog_mode' !== $column ) {
            return;
        }

        if ( ! self::is_hybrid_store() ) {
            return;
        }

        $mode = self::get_hybrid_product_mode( $post_id );

        $labels = [
            'sell'    => __( 'Sellable', 'modefilter-pro' ),
            'catalog' => __( 'Catalog', 'modefilter-pro' ),
        ];

        printf(
            '<button type="button" class="modep-mode-badge modep-mode-%1$s" data-modep-toggle="1" data-mode="%1$s" data-post-id="%2$d">%3$s</button>',
            esc_attr( $mode ),
            (int) $post_id,
            esc_html( $labels[ $mode ] ?? $labels['sell'] )
        );
    }

    /** ---------- Admin list: Views + Dropdown (Hybrid only) ---------- */

    private static function get_mode_counts() : array {
        $cached = wp_cache_get( self::CACHE_KEY_COUNTS, self::CACHE_GROUP );
        if ( is_array( $cached ) && isset( $cached['sell'], $cached['catalog'] ) ) {
            return [
                'sell'    => (int) $cached['sell'],
                'catalog' => (int) $cached['catalog'],
            ];
        }

        global $wpdb;

        // Published counts are consistent with WP view-count behaviour.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sell = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(p.ID)
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                   AND pm.meta_key = %s
                 WHERE p.post_type = 'product'
                   AND p.post_status = 'publish'
                   AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = 'sell')",
                self::META_OVERRIDE
            )
        );

        $catalog = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(p.ID)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                 WHERE p.post_type = 'product'
                   AND p.post_status = 'publish'
                   AND pm.meta_key = %s
                   AND pm.meta_value = 'catalog'",
                self::META_OVERRIDE
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        $data = [
            'sell'    => $sell,
            'catalog' => $catalog,
        ];

        wp_cache_set( self::CACHE_KEY_COUNTS, $data, self::CACHE_GROUP, self::CACHE_TTL );

        return $data;
    }

    public static function add_views_links( array $views ) : array {
        if ( ! self::is_hybrid_store() ) {
            return $views;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return $views;
        }

        $counts = self::get_mode_counts();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filter UI only.
        $current = isset( $_GET[ self::QV_MODE ] ) ? sanitize_key( wp_unslash( $_GET[ self::QV_MODE ] ) ) : '';

        $base_url = remove_query_arg( [ self::QV_MODE, 'paged' ] );

        $views['modep_sell'] = sprintf(
            '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
            esc_url( add_query_arg( self::QV_MODE, 'sell', $base_url ) ),
            ( 'sell' === $current ) ? ' class="current"' : '',
            esc_html__( 'Sellable', 'modefilter-pro' ),
            (int) $counts['sell']
        );

        $views['modep_catalog'] = sprintf(
            '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
            esc_url( add_query_arg( self::QV_MODE, 'catalog', $base_url ) ),
            ( 'catalog' === $current ) ? ' class="current"' : '',
            esc_html__( 'Catalog', 'modefilter-pro' ),
            (int) $counts['catalog']
        );

        return $views;
    }

    public static function add_dropdown_filter( string $post_type ) : void {
        if ( 'product' !== $post_type || ! self::is_hybrid_store() ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filter UI only.
        $current = isset( $_GET[ self::QV_MODE ] ) ? sanitize_key( wp_unslash( $_GET[ self::QV_MODE ] ) ) : '';

        echo '<label class="screen-reader-text" for="modep_mode_filter">' . esc_html__( 'Filter by Product Mode', 'modefilter-pro' ) . '</label>';
        echo '<select name="' . esc_attr( self::QV_MODE ) . '" id="modep_mode_filter">';
        echo '<option value="">' . esc_html__( 'All Product Modes', 'modefilter-pro' ) . '</option>';
        echo '<option value="sell"' . selected( $current, 'sell', false ) . '>' . esc_html__( 'Sellable', 'modefilter-pro' ) . '</option>';
        echo '<option value="catalog"' . selected( $current, 'catalog', false ) . '>' . esc_html__( 'Catalog', 'modefilter-pro' ) . '</option>';
        echo '</select>';
    }

    public static function filter_admin_products_query( WP_Query $query ) : void {
        if ( ! is_admin() || ! $query->is_main_query() || ! self::is_hybrid_store() ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filter UI only.
        $mode = isset( $_GET[ self::QV_MODE ] ) ? sanitize_key( wp_unslash( $_GET[ self::QV_MODE ] ) ) : '';
        if ( ! in_array( $mode, [ 'sell', 'catalog' ], true ) ) {
            return;
        }

        $meta_query = (array) $query->get( 'meta_query', [] );

        if ( 'catalog' === $mode ) {
            $meta_query[] = [
                'key'     => self::META_OVERRIDE,
                'value'   => 'catalog',
                'compare' => '=',
            ];
        } else {
            // Sellable = meta not exists OR empty OR 'sell'
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => self::META_OVERRIDE,
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => self::META_OVERRIDE,
                    'value'   => '',
                    'compare' => '=',
                ],
                [
                    'key'     => self::META_OVERRIDE,
                    'value'   => 'sell',
                    'compare' => '=',
                ],
            ];
        }

        $query->set( 'meta_query', $meta_query );
    }

    /** ---------- Quick / Bulk edit (Hybrid only) ---------- */

    public static function quick_edit_box( string $column, string $post_type ) : void {
        if ( 'modep_catalog_mode' !== $column || 'product' !== $post_type || ! self::is_hybrid_store() ) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'Product Mode', 'modefilter-pro' ); ?></span>
                    <select name="modep_catalog_override">
                        <option value="sell"><?php esc_html_e( 'Sellable', 'modefilter-pro' ); ?></option>
                        <option value="catalog"><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></option>
                    </select>
                </label>
                <?php wp_nonce_field( 'modep_catalog_edit', 'modep_catalog_edit_nonce' ); ?>
            </div>
        </fieldset>
        <?php
    }

    public static function bulk_edit_box( string $column, string $post_type ) : void {
        if ( 'modep_catalog_mode' !== $column || 'product' !== $post_type || ! self::is_hybrid_store() ) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'Product Mode', 'modefilter-pro' ); ?></span>
                    <select name="modep_catalog_override_bulk">
                        <option value=""><?php esc_html_e( '— No change —', 'modefilter-pro' ); ?></option>
                        <option value="sell"><?php esc_html_e( 'Set to Sellable', 'modefilter-pro' ); ?></option>
                        <option value="catalog"><?php esc_html_e( 'Set to Catalog', 'modefilter-pro' ); ?></option>
                    </select>
                </label>
                <?php wp_nonce_field( 'modep_catalog_edit', 'modep_catalog_edit_nonce' ); ?>
            </div>
        </fieldset>
        <?php
    }

    public static function save_from_edit( int $post_id, $post ) : void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $post instanceof WP_Post && 'product' !== $post->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $nonce = self::post_scalar( 'modep_catalog_edit_nonce' );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'modep_catalog_edit' ) ) {
            return;
        }

        $changed = false;

        // Quick edit: direct field.
        if ( isset( $_POST['modep_catalog_override'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
            $raw = self::post_scalar( 'modep_catalog_override' );
            $val = sanitize_key( $raw );

            if ( 'catalog' === $val ) {
                update_post_meta( $post_id, self::META_OVERRIDE, 'catalog' );
                $changed = true;
            } else {
                // Default sellable => keep clean.
                delete_post_meta( $post_id, self::META_OVERRIDE );
                $changed = true;
            }
        }

        // Bulk edit.
        if ( isset( $_POST['modep_catalog_override_bulk'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
            $bulk_raw = self::post_scalar( 'modep_catalog_override_bulk' );

            if ( '' !== $bulk_raw ) {
                $val = sanitize_key( $bulk_raw );

                if ( 'catalog' === $val ) {
                    update_post_meta( $post_id, self::META_OVERRIDE, 'catalog' );
                    $changed = true;
                } elseif ( 'sell' === $val ) {
                    delete_post_meta( $post_id, self::META_OVERRIDE );
                    $changed = true;
                }
            }
        }

        if ( $changed ) {
            self::clear_counts_cache();
        }
    }
}