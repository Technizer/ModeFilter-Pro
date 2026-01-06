<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handlers for ModeFilter Pro.
 *
 * - modep_get_products: returns a rendered product grid fragment for the frontend.
 *
 * Architecture notes (Pro):
 * - Chips-only UI.
 * - Only apply enabled filters from $attrs['filters'] (array of: categories,tags,brands,price,rating).
 * - Always pass $attrs into template as modep_attrs to keep Elementor controls consistent.
 */
final class MODEP_Ajax {

	private const NONCE_KEY = 'modep_nonce';

	/**
	 * Allowed POST keys for this endpoint (prevents arbitrary/dynamic $_POST access).
	 *
	 * @var string[]
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
	 * Read a single POST key value, only if the key is allowlisted.
	 *
	 * Important: uses filter_input() to avoid dynamic $_POST[$key] access which triggers PHPCS warnings.
	 *
	 * @return mixed|null
	 */
	private static function post_raw_allowed( string $key ) {
		if ( ! in_array( $key, self::ALLOWED_POST_KEYS, true ) ) {
			return null;
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			return null;
		}

		$raw = wp_unslash( $_POST[ $key ] );

		if ( is_array( $raw ) ) {
			return map_deep( $raw, 'sanitize_text_field' );
		}

		return sanitize_text_field( (string) $raw );
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

		if ( isset( $raw['includes'] ) && is_array( $raw['includes'] ) ) {
			$clean['includes'] = $raw['includes'];
		}
		if ( isset( $raw['excludes'] ) && is_array( $raw['excludes'] ) ) {
			$clean['excludes'] = $raw['excludes'];
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
		} else {
			$csv  = strtolower( trim( self::read_scalar( $raw ) ) );
			$bits = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
			foreach ( $bits as $b ) {
				$list[] = sanitize_key( $b );
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

	private static function get_effective_mode_for_product_id( int $product_id ) : string {
		if ( ! class_exists( 'MODEP_Catalog_Mode' ) ) {
			return '';
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}
		$mode = MODEP_Catalog_Mode::get_effective_mode( $product );
		return is_string( $mode ) ? $mode : '';
	}

	private static function calc_max_pages( int $total, int $per_page ) : int {
		$per_page = max( 1, $per_page );
		return max( 1, (int) ceil( $total / $per_page ) );
	}

	private static function normalize_grid_layout( array $attrs ) : array {
		$layout = sanitize_key( (string) ( $attrs['grid_layout'] ?? 'grid' ) );
		if ( ! in_array( $layout, [ 'grid', 'masonry', 'justified' ], true ) ) {
			$layout = 'grid';
		}

		$masonry_gap = absint( (string) ( $attrs['masonry_gap'] ?? '20' ) );
		if ( $masonry_gap > 200 ) {
			$masonry_gap = 200;
		}

		$justified = absint( (string) ( $attrs['justified_row_height'] ?? '220' ) );
		if ( $justified < 50 ) {
			$justified = 50;
		} elseif ( $justified > 1200 ) {
			$justified = 1200;
		}

		return [
			'grid_layout'          => $layout,
			'masonry_gap'          => $masonry_gap,
			'justified_row_height' => $justified,
		];
	}

	public static function get_products() : void {
		if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), self::NONCE_KEY ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed' ], 403 );
		}

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'WooCommerce is not active.', 'modefilter-pro' ) ] );
		}

		$attrs_raw = self::post_raw_allowed( 'shortcode_attrs' );
		$attrs     = self::sanitize_shortcode_attrs( $attrs_raw );
		$attrs     = (array) apply_filters( 'modep_ajax_sanitized_attrs', $attrs, $attrs_raw );

		$only_catalog = ( isset( $attrs['only_catalog'] ) && 'yes' === (string) $attrs['only_catalog'] );
		$enabled_filters = self::get_enabled_filters( $attrs );

		$page_raw = self::post_raw_allowed( 'page' );
		$page     = max( 1, absint( self::read_scalar( $page_raw ) ?: '1' ) );

		$sort_raw = self::post_raw_allowed( 'sort' );
		if ( null === $sort_raw ) {
			$sort_raw = (string) ( $attrs['sort'] ?? '' );
		}
		$sort = sanitize_text_field( self::read_scalar( $sort_raw ) );

		$allowed_sorts = [ '', 'price_asc', 'price_desc', 'in_stock', 'preorder', 'out_of_stock' ];
		if ( ! in_array( $sort, $allowed_sorts, true ) ) {
			$sort = '';
		}

		$cat_ids   = [];
		$tag_ids   = [];
		$brand_ids = [];

		if ( in_array( 'categories', $enabled_filters, true ) ) {
			$cat_ids = self::parse_int_array( self::post_raw_allowed( 'cat_ids' ) );
		}
		if ( in_array( 'tags', $enabled_filters, true ) ) {
			$tag_ids = self::parse_int_array( self::post_raw_allowed( 'tag_ids' ) );
		}
		if ( in_array( 'brands', $enabled_filters, true ) ) {
			$brand_ids = self::parse_int_array( self::post_raw_allowed( 'brand_ids' ) );
		}

		$price_min = '';
		$price_max = '';
		if ( in_array( 'price', $enabled_filters, true ) ) {
			$price_min = self::parse_price( self::post_raw_allowed( 'price_min' ) );
			$price_max = self::parse_price( self::post_raw_allowed( 'price_max' ) );
		}

		$rating_min = 0;
		if ( in_array( 'rating', $enabled_filters, true ) ) {
			$rating_min = self::parse_rating_min( self::post_raw_allowed( 'rating_min' ) );
		}

		$per_page = max( 1, absint( (string) ( $attrs['per_page'] ?? '9' ) ) );
		$columns  = max( 1, absint( (string) ( $attrs['columns'] ?? '3' ) ) );

		$includes = ( isset( $attrs['includes'] ) && is_array( $attrs['includes'] ) ) ? $attrs['includes'] : [];
		$ex       = ( isset( $attrs['excludes'] ) && is_array( $attrs['excludes'] ) ) ? $attrs['excludes'] : [];

		$inc_cat   = isset( $includes['cat_in'] ) ? self::parse_int_array( $includes['cat_in'] ) : [];
		$inc_tag   = isset( $includes['tag_in'] ) ? self::parse_int_array( $includes['tag_in'] ) : [];
		$inc_brand = isset( $includes['brand_in'] ) ? self::parse_int_array( $includes['brand_in'] ) : [];

		$ex_cat   = isset( $ex['cat'] ) ? self::parse_int_array( $ex['cat'] ) : [];
		$ex_tag   = isset( $ex['tag'] ) ? self::parse_int_array( $ex['tag'] ) : [];
		$ex_brand = isset( $ex['brand'] ) ? self::parse_int_array( $ex['brand'] ) : [];

		$sellable_slug = '';
		if ( isset( $attrs['sellable_set'] ) && is_array( $attrs['sellable_set'] ) && isset( $attrs['sellable_set']['cat_slug'] ) ) {
			$sellable_slug = sanitize_title( (string) $attrs['sellable_set']['cat_slug'] );
		}

		$grid = self::normalize_grid_layout( $attrs );

		$tax_query = [ 'relation' => 'AND' ];

		if ( ! $only_catalog && '' !== $sellable_slug ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => [ $sellable_slug ],
			];
		}

		if ( ! empty( $inc_cat ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $inc_cat,
			];
		}
		if ( ! empty( $inc_tag ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $inc_tag,
			];
		}
		if ( taxonomy_exists( 'product_brand' ) && ! empty( $inc_brand ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => $inc_brand,
			];
		}

		if ( in_array( 'categories', $enabled_filters, true ) && ! empty( $cat_ids ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $cat_ids,
			];
		}
		if ( in_array( 'tags', $enabled_filters, true ) && ! empty( $tag_ids ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $tag_ids,
			];
		}
		if ( taxonomy_exists( 'product_brand' ) && in_array( 'brands', $enabled_filters, true ) && ! empty( $brand_ids ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => $brand_ids,
			];
		}

		if ( ! empty( $ex_cat ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $ex_cat,
				'operator' => 'NOT IN',
			];
		}
		if ( ! empty( $ex_tag ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $ex_tag,
				'operator' => 'NOT IN',
			];
		}
		if ( taxonomy_exists( 'product_brand' ) && ! empty( $ex_brand ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => $ex_brand,
				'operator' => 'NOT IN',
			];
		}

		$meta_query = [];
		if ( function_exists( 'WC' ) && WC() && WC()->query ) {
			$meta_query = (array) WC()->query->get_meta_query();
		}

		if ( in_array( 'price', $enabled_filters, true ) && ( '' !== $price_min || '' !== $price_max ) ) {
			$low  = ( '' === $price_min ) ? 0 : (float) $price_min;
			$high = ( '' === $price_max ) ? 999999999 : (float) $price_max;

			$meta_query[] = [
				'key'     => '_price',
				'value'   => [ $low, $high ],
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			];
		}

		if ( in_array( 'rating', $enabled_filters, true ) && $rating_min > 0 ) {
			$meta_query[] = [
				'key'     => '_wc_average_rating',
				'value'   => (float) $rating_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			];
		}

		$orderby  = 'date';
		$order    = 'DESC';
		$meta_key = '';

		switch ( $sort ) {
			case 'price_asc':
				$orderby  = 'meta_value_num';
				$meta_key = '_price';
				$order    = 'ASC';
				break;

			case 'price_desc':
				$orderby  = 'meta_value_num';
				$meta_key = '_price';
				$order    = 'DESC';
				break;

			case 'in_stock':
				$meta_query[] = [ 'key' => '_stock_status', 'value' => 'instock' ];
				break;

			case 'preorder':
				$meta_query[] = [ 'key' => '_stock_status', 'value' => 'onbackorder' ];
				break;

			case 'out_of_stock':
				$meta_query[] = [ 'key' => '_stock_status', 'value' => 'outofstock' ];
				break;
		}

		$candidate_args = [
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'tax_query'              => $tax_query,   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query'             => $meta_query,  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'suppress_filters'       => false,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
		];

		if ( '' !== $meta_key ) {
			$candidate_args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$candidate_args['orderby']  = $orderby;
			$candidate_args['order']    = $order;
		}

		$candidate_args = (array) apply_filters( 'modep_ajax_candidate_ids_args', $candidate_args, $attrs );

		$candidate_q   = new WP_Query( $candidate_args );
		$candidate_ids = $candidate_q->have_posts() ? array_map( 'absint', (array) $candidate_q->posts ) : [];
		wp_reset_postdata();

		$candidate_ids = array_values( array_filter( $candidate_ids ) );

		$eligible_ids = [];

		if ( class_exists( 'MODEP_Catalog_Mode' ) ) {
			$mode_cache = [];

			foreach ( $candidate_ids as $pid ) {
				$pid = absint( $pid );
				if ( ! $pid ) {
					continue;
				}

				if ( ! isset( $mode_cache[ $pid ] ) ) {
					$mode_cache[ $pid ] = self::get_effective_mode_for_product_id( $pid );
				}
				$mode = $mode_cache[ $pid ];

				if ( $only_catalog ) {
					if ( 'catalog' === $mode ) {
						$eligible_ids[] = $pid;
					}
				} else {
					if ( 'catalog' !== $mode ) {
						$eligible_ids[] = $pid;
					}
				}
			}
		} else {
			$eligible_ids = $candidate_ids;
		}

		$eligible_ids = array_values( array_unique( array_filter( $eligible_ids ) ) );

		if ( empty( $eligible_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No products found.', 'modefilter-pro' ) ] );
		}

		$total     = count( $eligible_ids );
		$max_pages = self::calc_max_pages( $total, $per_page );
		$page      = min( $page, $max_pages );

		$offset   = ( $page - 1 ) * $per_page;
		$page_ids = array_slice( $eligible_ids, $offset, $per_page );

		if ( empty( $page_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No products found.', 'modefilter-pro' ) ] );
		}

		$paged_args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => count( $page_ids ),
			'post__in'            => $page_ids,
			'tax_query'           => $tax_query,  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'suppress_filters'    => false,
			'ignore_sticky_posts' => true,
		];

		if ( in_array( $sort, [ 'price_asc', 'price_desc' ], true ) ) {
			$paged_args['orderby']  = $orderby;
			$paged_args['order']    = $order;
			$paged_args['meta_key'] = $meta_key; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		} else {
			$paged_args['orderby'] = 'post__in';
		}

		$input = [
			'page'       => $page,
			'sort'       => $sort,
			'price_min'  => $price_min,
			'price_max'  => $price_max,
			'rating_min' => $rating_min,
			'cat_ids'    => $cat_ids,
			'tag_ids'    => $tag_ids,
			'brand_ids'  => $brand_ids,
			'filters'    => $enabled_filters,
		];

		$paged_args = (array) apply_filters( 'modep_ajax_products_query_args', $paged_args, $attrs, $input );

		$q = new WP_Query( $paged_args );

		if ( ! $q->have_posts() ) {
			wp_send_json_error( [ 'message' => __( 'No products found.', 'modefilter-pro' ) ] );
		}

		ob_start();

		while ( $q->have_posts() ) {
			$q->the_post();

			wc_get_template(
				'content-product-modep.php',
				[ 'modep_attrs' => $attrs ],
				'',
				defined( 'MODEP_TEMPLATES_DIR' ) ? MODEP_TEMPLATES_DIR : ''
			);
		}

		wp_reset_postdata();

		$html = (string) ob_get_clean();

		wp_send_json_success(
			[
				'html'      => $html,
				'page'      => $page,
				'max_pages' => $max_pages,
				'columns'   => $columns,
				'per_page'  => $per_page,
				'total'     => $total,

				'grid_layout'          => $grid['grid_layout'],
				'masonry_gap'          => $grid['masonry_gap'],
				'justified_row_height' => $grid['justified_row_height'],
			]
		);
	}
}
