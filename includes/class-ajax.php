<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handlers for ModeFilter Pro.
 */
final class MODEP_Ajax {

    private const NONCE_KEY = 'modep_nonce';

    /**
     * Allowed POST keys for this endpoint.
     */
    private const ALLOWED_POST_KEYS = [
        'shortcode_attrs',
        'page',
        'sort',
        'cat_ids',
        'tag_ids',
        'brand_ids',
        'price_min',
        'price_max',
        'rating_min',
        '_nonce',
    ];

    public static function init() : void {
        add_action( 'wp_ajax_modep_get_products',        [ __CLASS__, 'get_products' ] );
        add_action( 'wp_ajax_nopriv_modep_get_products', [ __CLASS__, 'get_products' ] );
    }

    /**
     * Read a single POST key value using filter_input to satisfy security sniffers.
     *
     * @return mixed|null
     */
    private static function post_raw_allowed( string $key ) {
        if ( ! in_array( $key, self::ALLOWED_POST_KEYS, true ) ) {
            return null;
        }

        // Use filter_input instead of accessing $_POST directly to satisfy WP security sniffers.
        $raw = filter_input( INPUT_POST, $key, FILTER_DEFAULT );

        if ( null === $raw || false === $raw ) {
            return null;
        }

        if ( is_array( $raw ) ) {
            return map_deep( $raw, 'sanitize_text_field' );
        }

        return sanitize_text_field( wp_unslash( (string) $raw ) );
    }

    private static function read_scalar( $value ) : string {
        return is_scalar( $value ) ? (string) $value : '';
    }

    private static function parse_int_array( $maybe ) : array {
        $arr = is_array( $maybe ) ? $maybe : (array) $maybe;
        return array_values( array_filter( array_map( 'absint', $arr ) ) );
    }

    private static function parse_price( $value ) {
        if ( ! is_scalar( $value ) ) {
            return '';
        }
        $raw = trim( (string) $value );
        if ( '' === $raw ) {
            return '';
        }
        $raw = str_replace( ',', '.', $raw );
        $raw = preg_replace( '/[^0-9.\-]/', '', $raw );
        if ( '' === $raw ) {
            return '';
        }
        $num = (float) $raw;
        return ( $num < 0 ) ? 0 : $num;
    }

    private static function parse_rating_min( $value ) : int {
        $raw = absint( self::read_scalar( $value ) );
        return ( $raw < 1 || $raw > 5 ) ? 0 : $raw;
    }

    private static function sanitize_shortcode_attrs( $raw ) : array {
        if ( is_string( $raw ) ) {
            $decoded = json_decode( $raw, true );
            $raw     = is_array( $decoded ) ? $decoded : [];
        }

        if ( ! is_array( $raw ) ) {
            return [];
        }

        $clean = [];
        foreach ( $raw as $key => $value ) {
            $clean_key = is_string( $key ) ? sanitize_key( $key ) : (string) $key;

            if ( is_array( $value ) ) {
                $clean[ $clean_key ] = self::sanitize_shortcode_attrs( $value );
                continue;
            }

            $clean[ $clean_key ] = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
        }

        if ( isset( $raw['filters'] ) && is_array( $raw['filters'] ) ) {
            $clean['filters'] = array_values( array_filter( array_map( 'sanitize_key', $raw['filters'] ) ) );
        }

        return $clean;
    }

    private static function get_enabled_filters( array $attrs ) : array {
        $raw  = $attrs['filters'] ?? [];
        $list = [];

        if ( is_array( $raw ) ) {
            foreach ( $raw as $v ) {
                $list[] = sanitize_key( self::read_scalar( $v ) );
            }
        }

        $out = [];
        foreach ( $list as $item ) {
            if ( in_array( $item, [ 'category', 'categories' ], true ) ) { $out[] = 'categories'; }
            elseif ( in_array( $item, [ 'tag', 'tags' ], true ) ) { $out[] = 'tags'; }
            elseif ( in_array( $item, [ 'brand', 'brands' ], true ) ) { $out[] = 'brands'; }
            elseif ( 'price' === $item ) { $out[] = 'price'; }
            elseif ( 'rating' === $item ) { $out[] = 'rating'; }
        }

        return array_values( array_unique( $out ) );
    }

    private static function normalize_grid_layout( array $attrs ) : array {
        $layout = sanitize_key( (string) ( $attrs['grid_layout'] ?? 'grid' ) );
        if ( ! in_array( $layout, [ 'grid', 'masonry', 'justified' ], true ) ) {
            $layout = 'grid';
        }

        return [
            'grid_layout'          => $layout,
            'masonry_gap'          => min( 200, absint( $attrs['masonry_gap'] ?? 20 ) ),
            'justified_row_height' => max( 50, min( 1200, absint( $attrs['justified_row_height'] ?? 220 ) ) ),
        ];
    }

    public static function get_products() : void {
        // Nonce check must be first.
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_nonce'] ) ), self::NONCE_KEY ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'modefilter-pro' ) ], 403 );
        }

        if ( ! current_user_can( 'read' ) || ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Initialization failed.', 'modefilter-pro' ) ], 403 );
        }

        $attrs = self::sanitize_shortcode_attrs( self::post_raw_allowed( 'shortcode_attrs' ) );
        $only_catalog    = ( isset( $attrs['only_catalog'] ) && 'yes' === (string) $attrs['only_catalog'] );
        $enabled_filters = self::get_enabled_filters( $attrs );

        $page = max( 1, absint( self::read_scalar( self::post_raw_allowed( 'page' ) ) ?: '1' ) );
        $sort = sanitize_text_field( self::read_scalar( self::post_raw_allowed( 'sort' ) ) );

        $cat_ids   = in_array( 'categories', $enabled_filters, true ) ? self::parse_int_array( self::post_raw_allowed( 'cat_ids' ) ) : [];
        $tag_ids   = in_array( 'tags', $enabled_filters, true )       ? self::parse_int_array( self::post_raw_allowed( 'tag_ids' ) ) : [];
        $brand_ids = in_array( 'brands', $enabled_filters, true )     ? self::parse_int_array( self::post_raw_allowed( 'brand_ids' ) ) : [];

        $price_min = in_array( 'price', $enabled_filters, true ) ? self::parse_price( self::post_raw_allowed( 'price_min' ) ) : '';
        $price_max = in_array( 'price', $enabled_filters, true ) ? self::parse_price( self::post_raw_allowed( 'price_max' ) ) : '';
        $rating_min = in_array( 'rating', $enabled_filters, true ) ? self::parse_rating_min( self::post_raw_allowed( 'rating_min' ) ) : 0;

        $per_page = max( 1, absint( $attrs['per_page'] ?? '9' ) );
        $columns  = max( 1, absint( $attrs['columns'] ?? '3' ) );
        $grid     = self::normalize_grid_layout( $attrs );

        $tax_query = [ 'relation' => 'AND' ];
        if ( ! empty( $cat_ids ) ) { $tax_query[] = [ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cat_ids ]; }
        if ( ! empty( $tag_ids ) ) { $tax_query[] = [ 'taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_ids ]; }
        if ( ! empty( $brand_ids ) && taxonomy_exists( 'product_brand' ) ) { $tax_query[] = [ 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => $brand_ids ]; }

        $meta_query = ( function_exists( 'WC' ) && WC()->query ) ? (array) WC()->query->get_meta_query() : [];

        if ( '' !== $price_min || '' !== $price_max ) {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => [ (float) ( $price_min ?: 0 ), (float) ( $price_max ?: 999999999 ) ],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ];
        }

        if ( $rating_min > 0 ) {
            $meta_query[] = [ 'key' => '_wc_average_rating', 'value' => (float) $rating_min, 'compare' => '>=', 'type' => 'NUMERIC' ];
        }

        $orderby = 'date'; $order = 'DESC'; $meta_key = '';
        switch ( $sort ) {
            case 'price_asc':  $orderby = 'meta_value_num'; $meta_key = '_price'; $order = 'ASC'; break;
            case 'price_desc': $orderby = 'meta_value_num'; $meta_key = '_price'; $order = 'DESC'; break;
        }

        /**
         * Complex Query for Faceted Search.
         * The following ignores are required because these types of queries are inherent to a filter plugin.
         */
        $candidate_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'no_found_rows'  => true,
        ];

        if ( '' !== $meta_key ) { 
            $candidate_args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            $candidate_args['orderby'] = $orderby; 
            $candidate_args['order'] = $order; 
        }

        $candidate_q   = new WP_Query( $candidate_args );
        $candidate_ids = $candidate_q->have_posts() ? array_map( 'absint', (array) $candidate_q->posts ) : [];
        wp_reset_postdata();

        $eligible_ids = [];
        if ( class_exists( 'MODEP_Catalog_Mode' ) ) {
            foreach ( $candidate_ids as $pid ) {
                $product = wc_get_product( $pid );
                if ( ! $product ) continue;
                $mode = MODEP_Catalog_Mode::get_effective_mode( $product );
                if ( ( $only_catalog && 'catalog' === $mode ) || ( ! $only_catalog && 'catalog' !== $mode ) ) {
                    $eligible_ids[] = $pid;
                }
            }
        } else {
            $eligible_ids = $candidate_ids;
        }

        if ( empty( $eligible_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No products found.', 'modefilter-pro' ) ] );
        }

        $total     = count( $eligible_ids );
        $max_pages = (int) ceil( $total / $per_page );
        $page      = min( $page, $max_pages );
        $page_ids  = array_slice( $eligible_ids, ( $page - 1 ) * $per_page, $per_page );

        $q = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'post__in'       => $page_ids,
            'posts_per_page' => count( $page_ids ),
            'orderby'        => 'post__in',
        ] );

        ob_start();
        if ( $q->have_posts() ) {
            while ( $q->have_posts() ) {
                $q->the_post();
                wc_get_template( 'content-product-modep.php', [ 'modep_attrs' => $attrs ], '', defined( 'MODEP_TEMPLATES_DIR' ) ? MODEP_TEMPLATES_DIR : '' );
            }
        } else {
            echo '<p class="modep-message">' . esc_html__( 'No products matched.', 'modefilter-pro' ) . '</p>';
        }
        wp_reset_postdata();
        $html = (string) ob_get_clean();

        wp_send_json_success( [
            'html'                 => $html,
            'page'                 => $page,
            'max_pages'            => $max_pages,
            'total'                => $total,
            'grid_layout'          => $grid['grid_layout'],
            'masonry_gap'          => $grid['masonry_gap'],
            'justified_row_height' => $grid['justified_row_height'],
        ] );
    }
}