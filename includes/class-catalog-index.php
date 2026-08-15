<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maintains a queryable copy of each product's effective Shop/Catalog mode.
 *
 * The source of truth remains MODEP_Catalog_Mode. This index exists so public
 * filtering requests can paginate in SQL instead of instantiating every product.
 */
final class MODEP_Catalog_Index {
	public const META_KEY = '_modep_effective_mode';
	private const VERSION_OPTION = 'modep_catalog_index_version';
	private const READY_OPTION   = 'modep_catalog_index_ready';
	private const OFFSET_OPTION  = 'modep_catalog_index_offset';
	private const CACHE_VERSION_OPTION = 'modep_filter_cache_version';
	private const CRON_HOOK      = 'modep_rebuild_catalog_index';
	private const BATCH_SIZE     = 200;

	public static function init() : void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_batch' ] );
		add_action( 'save_post_product', [ __CLASS__, 'product_saved' ], 100 );
		add_action( 'set_object_terms', [ __CLASS__, 'sync_object_terms' ], 100, 6 );
		add_action( 'updated_option', [ __CLASS__, 'option_updated' ], 10, 3 );
		add_action( 'added_term_meta', [ __CLASS__, 'term_meta_changed' ], 10, 4 );
		add_action( 'updated_term_meta', [ __CLASS__, 'term_meta_changed' ], 10, 4 );
		add_action( 'deleted_term_meta', [ __CLASS__, 'term_meta_changed' ], 10, 4 );
		add_action( 'added_post_meta', [ __CLASS__, 'post_meta_changed' ], 10, 4 );
		add_action( 'updated_post_meta', [ __CLASS__, 'post_meta_changed' ], 10, 4 );
		add_action( 'deleted_post_meta', [ __CLASS__, 'post_meta_changed' ], 10, 4 );

		if ( MODEP_VERSION !== (string) get_option( self::VERSION_OPTION, '' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			if ( false === get_option( self::OFFSET_OPTION, false ) ) {
				self::queue_rebuild();
			} else {
				wp_schedule_single_event( time() + 5, self::CRON_HOOK );
			}
		}
	}

	public static function is_ready() : bool {
		return 'yes' === get_option( self::READY_OPTION, 'no' )
			&& MODEP_VERSION === (string) get_option( self::VERSION_OPTION, '' );
	}

	public static function sync_product( $product_id ) : void {
		$product_id = absint( $product_id );
		if ( ! $product_id || wp_is_post_revision( $product_id ) || ! class_exists( 'MODEP_Catalog_Mode' ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$mode = MODEP_Catalog_Mode::get_effective_mode( $product );
		// Hybrid is a store-level policy; products without an override are sellable.
		$mode = ( 'catalog' === $mode ) ? 'catalog' : 'sell';
		update_post_meta( $product_id, self::META_KEY, $mode );
	}

	public static function product_saved( $product_id ) : void {
		self::sync_product( $product_id );
		self::bump_cache_version();
	}

	public static function sync_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) : void {
		unset( $terms, $tt_ids, $append, $old_tt_ids );
		if ( 'product' === get_post_type( $object_id ) && in_array( $taxonomy, [ 'product_cat', 'product_tag', 'product_brand' ], true ) ) {
			self::sync_product( $object_id );
			self::bump_cache_version();
		}
	}

	public static function option_updated( $option, $old_value, $value ) : void {
		unset( $old_value, $value );
		if ( MODEP_Catalog_Mode::OPT === $option ) {
			self::bump_cache_version();
			self::queue_rebuild();
		}
	}

	public static function term_meta_changed( $meta_id, $term_id, $meta_key, $meta_value ) : void {
		unset( $meta_id, $term_id, $meta_value );
		if ( '_modep_catalog_default' === $meta_key ) {
			self::bump_cache_version();
			self::queue_rebuild();
		}
	}

	public static function post_meta_changed( $meta_id, $object_id, $meta_key, $meta_value ) : void {
		unset( $meta_id, $meta_value );
		if ( '_modep_catalog_override' === $meta_key && 'product' === get_post_type( $object_id ) ) {
			self::sync_product( $object_id );
			self::bump_cache_version();
		}
	}

	public static function queue_rebuild() : void {
		update_option( self::READY_OPTION, 'no', false );
		update_option( self::OFFSET_OPTION, 0, false );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		}
	}

	public static function cache_version() : int {
		return max( 1, (int) get_option( self::CACHE_VERSION_OPTION, 1 ) );
	}

	private static function bump_cache_version() : void {
		update_option( self::CACHE_VERSION_OPTION, self::cache_version() + 1, false );
	}

	public static function run_batch() : void {
		$offset = max( 0, (int) get_option( self::OFFSET_OPTION, 0 ) );
		$query  = new WP_Query(
			[
				'post_type'              => 'product',
				'post_status'            => [ 'publish', 'private', 'draft', 'pending', 'future' ],
				'posts_per_page'         => self::BATCH_SIZE,
				'offset'                 => $offset,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$ids = array_map( 'absint', (array) $query->posts );
		foreach ( $ids as $product_id ) {
			self::sync_product( $product_id );
		}

		if ( count( $ids ) === self::BATCH_SIZE ) {
			update_option( self::OFFSET_OPTION, $offset + self::BATCH_SIZE, false );
			wp_schedule_single_event( time() + 2, self::CRON_HOOK );
			return;
		}

		delete_option( self::OFFSET_OPTION );
		update_option( self::VERSION_OPTION, MODEP_VERSION, false );
		update_option( self::READY_OPTION, 'yes', false );
	}

	/**
	 * Add the effective-mode clause to an existing WooCommerce meta query.
	 */
	public static function add_mode_clause( array $meta_query, bool $only_catalog ) : array {
		$meta_query[] = [
			'key'     => self::META_KEY,
			'value'   => $only_catalog ? 'catalog' : 'sell',
			'compare' => '=',
		];
		return $meta_query;
	}
}
