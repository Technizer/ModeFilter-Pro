<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode renderer for ModeFilter Pro.
 *
 * - Chips-only UI in Pro.
 * - Filters are opt-in (manual) OR auto-detected (auto), but never forced by default.
 * - Shortcode renders wrapper + filters UI + empty grid; JS hydrates products via AJAX.
 */
final class MODEP_Shortcode {

	/**
	 * Register shortcode.
	 */
	public static function init() : void {
		add_shortcode( 'modep_filters', [ __CLASS__, 'render' ] );
		add_shortcode( 'modep_catalog', [ __CLASS__, 'render_catalog' ] );
	}

	/**
	 * Catalog-only wrapper.
	 *
	 * This keeps the same attribute contract as [modep_filters] but forces only_catalog="yes".
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_catalog( $atts ) : string {
		$atts = is_array( $atts ) ? $atts : [];

		// Force catalog mode no matter what.
		$atts['only_catalog'] = 'yes';
		$atts['context']      = 'catalog';

		// Sensible default button label for catalog mode (can be overridden).
		if ( empty( $atts['catalog_button_text'] ) ) {
			$atts['catalog_button_text'] = __( 'Enquire now', 'modefilter-pro' );
		}

		return self::render( $atts );
	}

	/**
	 * Render the shortcode wrapper, opt-in filter chips, and grid anchors.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) : string {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '<div class="modep-error">' . esc_html__( 'WooCommerce is not active.', 'modefilter-pro' ) . '</div>';
		}

		MODEP_Assets::enqueue();
		if ( class_exists( 'MODEP_Review' ) ) {
			MODEP_Review::mark_used();
		}

		// ------------------------------
		// 1) Parse shortcode attributes (legacy + new architecture)
		// ------------------------------
		$raw_atts = is_array( $atts ) ? $atts : [];
		if ( class_exists( 'MODEP_Template_Kits' ) ) {
			$raw_atts = MODEP_Template_Kits::apply_to_atts( $raw_atts );
		}

		$atts = shortcode_atts(
			[
				// Query / scope.
				'columns'           => 3,
				'per_page'          => 9,
				'sort'              => '',
				'cat_in'            => '',
				'tag_in'            => '',
				'brand_in'          => '',

				// Optional sellable pool source.
				'sellable_cat_slug' => '',

				'price_min'         => '',
				'price_max'         => '',
				'only_catalog'      => '',
				'context'           => '',

				// UI / layout.
				'template_kit'      => 'none',
				'preset'            => 'normal',
				'pagination'        => 'load_more',
				'load_more_text'    => __( 'Load more', 'modefilter-pro' ),
				'link_whole_card'   => 'no',
				'custom_layout'     => '',

				// NEW: Grid layout modes
				'grid_layout'          => 'grid', // grid|masonry|justified
				'masonry_gap'          => '20',
				'justified_row_height' => '220',

				// Elementor / catalog widget pass-through.
				'meta_fields'           => '',
				'link_mode'             => 'image_title',
				'custom_link_url'       => '',
				'custom_link_external'  => '',
				'custom_link_nofollow'  => '',

				// NEW FILTER ARCHITECTURE (Pro)
				'filters_mode'      => 'manual', // manual|auto
				'filters'           => '',       // csv: categories,tags,brands,price,rating
				'terms_limit'       => '12',
				'terms_orderby'     => 'count',  // count|name
				'terms_order'       => 'DESC',   // DESC|ASC
				'terms_show_more'   => 'yes',    // yes|no

				// Presentation. filter_ui remains as a backwards-compatible alias.
				'filter_style'      => 'global',
				'filter_ui'         => '',
				'category_hierarchy'=> 'global',
				'filter_position'   => 'left',
				'loader_style'      => 'global',
				'loader_image'      => '',

				// UI exclusions (taxonomy terms).
				'exclude_cat'       => '',
				'exclude_tag'       => '',
				'exclude_brand'     => '',

				// Catalog display controls.
				'show_excerpt'           => 'yes',
				'excerpt_length'         => 20,
				'excerpt_length_type'    => 'words',
				'catalog_button_text'    => __( 'Enquire Now', 'modefilter-pro' ),
				'catalog_message_enable' => 'no',
				'catalog_message'        => '',
				'inquire_action'         => 'popup',
			],
			$raw_atts,
			'modep_filters'
		);

		// Let Pro fully override layout if needed.
		$maybe_pro = apply_filters( 'modep_render_pro_layout', '', $atts );
		if ( '' !== $maybe_pro ) {
			return (string) $maybe_pro;
		}

		// ------------------------------
		// 2) Normalize + sanitize architecture attrs
		// ------------------------------
		$filters_mode = in_array( (string) $atts['filters_mode'], [ 'manual', 'auto' ], true )
			? (string) $atts['filters_mode']
			: 'manual';

		$template_kit = class_exists( 'MODEP_Template_Kits' )
			? MODEP_Template_Kits::sanitize_kit_id( (string) $atts['template_kit'] )
			: 'none';

		$presentation   = self::get_global_presentation();
		$requested_style = sanitize_key( (string) $atts['filter_style'] );
		if ( ( '' === $requested_style || 'global' === $requested_style ) && '' !== (string) $atts['filter_ui'] ) {
			$requested_style = sanitize_key( (string) $atts['filter_ui'] );
		}
		$filter_style = self::normalize_choice(
			$requested_style,
			[ 'chips', 'checkboxes', 'radios', 'toggles', 'hierarchical' ],
			(string) $presentation['filter_style']
		);
		$category_hierarchy = self::normalize_yes_no_global(
			(string) $atts['category_hierarchy'],
			(string) $presentation['category_hierarchy']
		);
		if ( 'hierarchical' === $filter_style ) {
			$category_hierarchy = 'yes';
		}
		$loader_style = self::normalize_choice(
			sanitize_key( (string) $atts['loader_style'] ),
			[ 'spinner', 'skeleton', 'dots', 'pulse', 'custom', 'none' ],
			(string) $presentation['loader_style']
		);
		$loader_image = esc_url_raw( (string) $atts['loader_image'] );
		if ( '' === $loader_image ) {
			$loader_image = esc_url_raw( (string) $presentation['loader_image'] );
		}
		if ( 'custom' === $loader_style && '' === $loader_image ) {
			$loader_style = 'spinner';
		}

		$filter_position = sanitize_key( (string) $atts['filter_position'] );
		if ( ! in_array( $filter_position, [ 'left', 'right', 'top' ], true ) ) {
			$filter_position = 'left';
		}

		$terms_limit = absint( (string) $atts['terms_limit'] );
		if ( $terms_limit < 1 ) {
			$terms_limit = 1;
		} elseif ( $terms_limit > 200 ) {
			$terms_limit = 200;
		}

		$terms_orderby = in_array( (string) $atts['terms_orderby'], [ 'count', 'name' ], true )
			? (string) $atts['terms_orderby']
			: 'count';

		$terms_order = in_array( strtoupper( (string) $atts['terms_order'] ), [ 'ASC', 'DESC' ], true )
			? strtoupper( (string) $atts['terms_order'] )
			: 'DESC';

		$show_more = ( (string) $atts['terms_show_more'] === 'yes' ) ? 'yes' : 'no';

		// Enabled filters list (opt-in).
		$enabled_filters = self::parse_filters_csv( (string) $atts['filters'] );

		// ✅ Robust only_catalog normalization (yes/1/true/on)
		$only_catalog = false;
		if ( isset( $atts['only_catalog'] ) ) {
			$v = strtolower( trim( (string) $atts['only_catalog'] ) );
			$only_catalog = in_array( $v, [ 'yes', '1', 'true', 'on' ], true );
		}

		// Keep consistent downstream.
		$atts['only_catalog'] = $only_catalog ? 'yes' : 'no';
		if ( $only_catalog ) {
			$atts['context'] = 'catalog';
		}

		// NEW: sanitize grid layout args
		$grid_layout = sanitize_key( (string) $atts['grid_layout'] );
		if ( ! in_array( $grid_layout, [ 'grid', 'masonry', 'justified' ], true ) ) {
			$grid_layout = 'grid';
		}

		$masonry_gap = absint( (string) $atts['masonry_gap'] );
		if ( $masonry_gap > 200 ) {
			$masonry_gap = 200;
		}

		$justified_row_height = absint( (string) $atts['justified_row_height'] );
		if ( $justified_row_height < 50 ) {
			$justified_row_height = 50;
		} elseif ( $justified_row_height > 1200 ) {
			$justified_row_height = 1200;
		}

		// Resolve include scopes to IDs (these remain optional constraints).
		$resolved_includes = [
			'cat_in'   => modep_resolve_terms_to_ids( (string) $atts['cat_in'], 'product_cat' ),
			'tag_in'   => modep_resolve_terms_to_ids( (string) $atts['tag_in'], 'product_tag' ),
			'brand_in' => modep_resolve_terms_to_ids( (string) $atts['brand_in'], 'product_brand' ),
		];

		// Exclusions to IDs.
		$ex = [
			'cat'   => modep_resolve_terms_to_ids( (string) $atts['exclude_cat'], 'product_cat' ),
			'tag'   => modep_resolve_terms_to_ids( (string) $atts['exclude_tag'], 'product_tag' ),
			'brand' => modep_resolve_terms_to_ids( (string) $atts['exclude_brand'], 'product_brand' ),
		];

		// ------------------------------
		// 3) Catalog display attrs (pass-through)
		// ------------------------------
		$show_excerpt        = ( (string) $atts['show_excerpt'] === 'yes' ) ? 'yes' : 'no';
		$excerpt_length_type = in_array( (string) $atts['excerpt_length_type'], [ 'words', 'chars' ], true )
			? (string) $atts['excerpt_length_type']
			: 'words';

		$excerpt_length = (int) $atts['excerpt_length'];
		if ( $excerpt_length < 1 ) {
			$excerpt_length = 1;
		} elseif ( $excerpt_length > 500 ) {
			$excerpt_length = 500;
		}

		$catalog_button_text    = sanitize_text_field( (string) $atts['catalog_button_text'] );
		$catalog_message_enable = ( (string) $atts['catalog_message_enable'] === 'yes' ) ? 'yes' : 'no';
		$catalog_message        = sanitize_textarea_field( (string) $atts['catalog_message'] );
		$inquire_action         = in_array( (string) $atts['inquire_action'], [ 'popup', 'single' ], true )
			? (string) $atts['inquire_action']
			: 'popup';

		// ------------------------------
		// 4) Determine "filter source pool"
		// ------------------------------
		$source_ids = self::get_source_product_ids(
			$only_catalog,
			(string) $atts['sellable_cat_slug']
		);
		$source_ids = self::apply_include_scopes( $source_ids, $resolved_includes );

		// ------------------------------
		// 5) Auto-detect available filters (when filters_mode=auto)
		// ------------------------------
		$available_filters = self::detect_available_filters( $source_ids );

		// Final list of filters to render.
		$filters_to_render = $enabled_filters;
		if ( 'auto' === $filters_mode ) {
			if ( empty( $filters_to_render ) ) {
				$filters_to_render = $available_filters;
			} else {
				$filters_to_render = array_values( array_intersect( $filters_to_render, $available_filters ) );
			}
		}

		// ------------------------------
		// 6) Build UI data for enabled/available filters
		// ------------------------------
		$ui_blocks = self::build_filters_ui_blocks(
			$filters_to_render,
			$source_ids,
			$terms_limit,
			$terms_orderby,
			$terms_order,
			$show_more,
			'yes' === $category_hierarchy,
			$ex
		);

		// ------------------------------
		// 7) JS payload (critical)
		// ------------------------------
		$for_js = [
			'columns'         => max( 1, absint( (string) $atts['columns'] ) ),
			'per_page'        => max( 1, absint( (string) $atts['per_page'] ) ),
			'sort'            => sanitize_text_field( (string) $atts['sort'] ),
			'price_min'       => $atts['price_min'] !== '' ? (float) $atts['price_min'] : '',
			'price_max'       => $atts['price_max'] !== '' ? (float) $atts['price_max'] : '',

			'includes'        => $resolved_includes,

			'sellable_set'    => [
				'cat_slug' => sanitize_text_field( (string) $atts['sellable_cat_slug'] ),
			],

			'only_catalog'    => $only_catalog ? 'yes' : 'no',
			'context'         => $only_catalog ? 'catalog' : sanitize_key( (string) ( $atts['context'] ?? '' ) ),

			'pagination'      => sanitize_text_field( (string) $atts['pagination'] ),
			'load_more_text'  => sanitize_text_field( (string) $atts['load_more_text'] ),

			'template_kit'    => $template_kit,
			'preset'          => sanitize_text_field( (string) $atts['preset'] ),
			'link_whole_card' => ( (string) $atts['link_whole_card'] === 'yes' ),
			'custom_layout'   => sanitize_text_field( (string) $atts['custom_layout'] ),

			'grid_layout'          => $grid_layout,
			'masonry_gap'          => $masonry_gap,
			'justified_row_height' => $justified_row_height,

			'meta_fields'          => sanitize_text_field( (string) $atts['meta_fields'] ),
			'link_mode'            => sanitize_key( (string) $atts['link_mode'] ),
			'custom_link_url'      => esc_url_raw( (string) $atts['custom_link_url'] ),
			'custom_link_external' => ( (string) $atts['custom_link_external'] === '1' ) ? '1' : '0',
			'custom_link_nofollow' => ( (string) $atts['custom_link_nofollow'] === '1' ) ? '1' : '0',

			'filters_mode'    => $filters_mode,
			'filters'         => $filters_to_render,
			'terms_limit'     => $terms_limit,
			'terms_orderby'   => $terms_orderby,
			'terms_order'     => $terms_order,
			'terms_show_more' => $show_more,
			'excludes'        => $ex,

			'filter_style'    => $filter_style,
			'filter_ui'       => $filter_style,
			'category_hierarchy' => $category_hierarchy,
			'filter_position' => $filter_position,
			'loader_style'    => $loader_style,

			'show_excerpt'           => $show_excerpt,
			'excerpt_length'         => $excerpt_length,
			'excerpt_length_type'    => $excerpt_length_type,
			'catalog_button_text'    => $catalog_button_text,
			'catalog_message_enable' => $catalog_message_enable,
			'catalog_message'        => $catalog_message,
			'inquire_action'         => $inquire_action,
		];

		// Wrapper classes.
		$preset_class = 'modep--preset-' . preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $atts['preset'] ) );
		$kit_class    = 'none' !== $template_kit ? 'modep--kit-' . $template_kit : 'modep--kit-none';
		$pos_class    = 'modep--filters-' . preg_replace( '/[^a-z0-9\-]/', '', strtolower( $filter_position ) );
		$ui_class     = 'modep--ui-' . $filter_style;
		$link_flag    = $for_js['link_whole_card'] ? '1' : '0';

		$filters_html = self::render_filters_ui( $ui_blocks, $filter_style, 'yes' === $category_hierarchy );

		$has_any_filters = ! empty( $ui_blocks );
		$show_toggle_btn = $has_any_filters && in_array( strtolower( $filter_position ), [ 'left', 'right' ], true );

		ob_start();
		?>
		<div class="modep <?php echo esc_attr( "{$kit_class} {$preset_class} {$pos_class} {$ui_class}" ); ?>"
			data-shortcode-attrs="<?php echo esc_attr( wp_json_encode( $for_js ) ); ?>"
			data-template-kit="<?php echo esc_attr( $template_kit ); ?>"
			data-link-whole-card="<?php echo esc_attr( $link_flag ); ?>">

			<?php if ( $show_toggle_btn ) : ?>
				<button class="modep-toggle-btn" type="button">
					<?php esc_html_e( 'Toggle Filters', 'modefilter-pro' ); ?>
				</button>
			<?php endif; ?>

			<?php echo wp_kses_post( $filters_html ); ?>

			<main class="modep-main">
				<div class="modep-sort-bar">
					<span class="modep-sort-label"><?php esc_html_e( 'Sort by:', 'modefilter-pro' ); ?></span>
					<select class="modep-sort" aria-label="<?php esc_attr_e( 'Sort products', 'modefilter-pro' ); ?>">
						<option value=""><?php esc_html_e( 'Default', 'modefilter-pro' ); ?></option>
						<option value="price_asc"><?php esc_html_e( 'Price: Low to High', 'modefilter-pro' ); ?></option>
						<option value="price_desc"><?php esc_html_e( 'Price: High to Low', 'modefilter-pro' ); ?></option>
						<option value="in_stock"><?php esc_html_e( 'In Stock', 'modefilter-pro' ); ?></option>
						<option value="preorder"><?php esc_html_e( 'Pre-Order', 'modefilter-pro' ); ?></option>
						<option value="out_of_stock"><?php esc_html_e( 'Out of Stock', 'modefilter-pro' ); ?></option>
					</select>
				</div>

				<ul class="modep-grid"
					style="--modep-cols: <?php echo esc_attr( (string) (int) $for_js['columns'] ); ?>;"
					aria-live="polite"
					aria-busy="false"></ul>

				<?php echo wp_kses_post( self::render_loader( $loader_style, $loader_image, (int) $for_js['columns'] ) ); ?>

				<nav class="modep-pagination" aria-label="<?php esc_attr_e( 'Products pagination', 'modefilter-pro' ); ?>"></nav>
			</main>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function get_global_presentation() : array {
		$ui           = (array) get_option( 'modep_ui', [] );
		$presentation = isset( $ui['presentation'] ) && is_array( $ui['presentation'] ) ? $ui['presentation'] : [];
		return [
			'filter_style'       => self::normalize_choice( sanitize_key( (string) ( $presentation['filter_style'] ?? 'chips' ) ), [ 'chips', 'checkboxes', 'radios', 'toggles', 'hierarchical' ], 'chips' ),
			'category_hierarchy' => 'yes' === (string) ( $presentation['category_hierarchy'] ?? 'no' ) ? 'yes' : 'no',
			'loader_style'       => self::normalize_choice( sanitize_key( (string) ( $presentation['loader_style'] ?? 'spinner' ) ), [ 'spinner', 'skeleton', 'dots', 'pulse', 'custom', 'none' ], 'spinner' ),
			'loader_image'       => esc_url_raw( (string) ( $presentation['loader_image'] ?? '' ) ),
		];
	}

	private static function normalize_choice( string $value, array $allowed, string $fallback ) : string {
		if ( '' === $value || 'global' === $value ) {
			$value = $fallback;
		}
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function normalize_yes_no_global( string $value, string $fallback ) : string {
		$value = strtolower( trim( $value ) );
		if ( '' === $value || 'global' === $value ) {
			$value = $fallback;
		}
		return in_array( $value, [ 'yes', '1', 'true', 'on' ], true ) ? 'yes' : 'no';
	}

	private static function render_loader( string $style, string $image, int $columns ) : string {
		if ( 'none' === $style ) {
			return '<div class="modep-loader modep-loader--none" hidden aria-hidden="true"></div>';
		}

		ob_start();
		?>
		<div class="modep-loader modep-loader--<?php echo esc_attr( $style ); ?>" style="--modep-cols: <?php echo esc_attr( (string) max( 1, $columns ) ); ?>;" role="status" aria-live="polite">
			<span class="screen-reader-text"><?php esc_html_e( 'Loading products…', 'modefilter-pro' ); ?></span>
			<?php if ( 'skeleton' === $style ) : ?>
				<div class="modep-loader__grid" aria-hidden="true">
					<?php for ( $modep_skeleton = 0; $modep_skeleton < 3; $modep_skeleton++ ) : ?>
						<span class="modep-skeleton-card"><span></span><i></i><b></b></span>
					<?php endfor; ?>
				</div>
			<?php elseif ( 'dots' === $style ) : ?>
				<span class="modep-loader__dots" aria-hidden="true"><i></i><i></i><i></i></span>
			<?php elseif ( 'pulse' === $style ) : ?>
				<span class="modep-loader__pulse" aria-hidden="true"></span>
			<?php elseif ( 'custom' === $style && '' !== $image ) : ?>
				<img class="modep-loader__image" src="<?php echo esc_url( $image ); ?>" alt="" aria-hidden="true" />
			<?php else : ?>
				<span class="modep-loader__spinner" aria-hidden="true"></span>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Parse filters csv into canonical IDs.
	 *
	 * Supported: categories,tags,brands,price,rating
	 *
	 * @param string $csv CSV string.
	 * @return string[]
	 */
	private static function parse_filters_csv( string $csv ) : array {
		$csv = strtolower( trim( $csv ) );
		if ( '' === $csv ) {
			return [];
		}

		$raw = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
		$out = [];

		foreach ( $raw as $item ) {
			$item = strtolower( preg_replace( '/[^a-z0-9_]/', '', $item ) );
			switch ( $item ) {
				case 'category':
				case 'categories':
					$out[] = 'categories';
					break;
				case 'tag':
				case 'tags':
					$out[] = 'tags';
					break;
				case 'brand':
				case 'brands':
					$out[] = 'brands';
					break;
				case 'price':
					$out[] = 'price';
					break;
				case 'rating':
					$out[] = 'rating';
					break;
				default:
					if ( 0 === strpos( $item, 'pa_' ) && taxonomy_exists( $item ) ) {
						$out[] = $item;
					}
					break;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Restrict filter choices to the same category/tag/brand scope as results.
	 * Terms inside one taxonomy are ORed; different taxonomies are ANDed.
	 *
	 * @param int[] $source_ids Product pool.
	 * @param array $includes   Resolved include term IDs.
	 * @return int[]
	 */
	private static function apply_include_scopes( array $source_ids, array $includes ) : array {
		$source_ids = array_values( array_unique( array_filter( array_map( 'absint', $source_ids ) ) ) );
		$map = [
			'cat_in'   => 'product_cat',
			'tag_in'   => 'product_tag',
			'brand_in' => 'product_brand',
		];
		foreach ( $map as $key => $taxonomy ) {
			$term_ids = array_values( array_filter( array_map( 'absint', (array) ( $includes[ $key ] ?? [] ) ) ) );
			if ( empty( $term_ids ) || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$objects = get_objects_in_term( $term_ids, $taxonomy );
			if ( is_wp_error( $objects ) ) {
				return [];
			}
			$source_ids = array_values( array_intersect( $source_ids, array_map( 'absint', $objects ) ) );
			if ( empty( $source_ids ) ) {
				break;
			}
		}
		return $source_ids;
	}

	/**
	 * Detect which filters are available for the current product pool.
	 *
	 * @param int[] $source_ids Source product IDs.
	 * @return string[] canonical filter ids
	 */
	private static function detect_available_filters( array $source_ids ) : array {
		$available = [];

		$source_ids = array_values( array_filter( array_map( 'absint', $source_ids ) ) );
		if ( empty( $source_ids ) ) {
			return [];
		}

		// Taxonomies: categories/tags/brands
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = wp_get_object_terms( $source_ids, 'product_cat', [ 'hide_empty' => true, 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$available[] = 'categories';
			}
		}

		if ( taxonomy_exists( 'product_tag' ) ) {
			$terms = wp_get_object_terms( $source_ids, 'product_tag', [ 'hide_empty' => true, 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$available[] = 'tags';
			}
		}

		if ( taxonomy_exists( 'product_brand' ) ) {
			$terms = wp_get_object_terms( $source_ids, 'product_brand', [ 'hide_empty' => true, 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$available[] = 'brands';
			}
		}

		// Price availability: at least one product has a price.
		// (Lightweight check: look at a small sample first, then fall back.)
		$sample_ids = array_slice( $source_ids, 0, 50 );
		$has_price  = false;
		foreach ( $sample_ids as $pid ) {
			$p = wc_get_product( $pid );
			if ( $p && '' !== (string) $p->get_price() ) {
				$has_price = true;
				break;
			}
		}
		if ( $has_price ) {
			$available[] = 'price';
		}

		// Rating availability: any product has rating_count > 0 or average > 0.
		$has_rating = false;
		foreach ( $sample_ids as $pid ) {
			$p = wc_get_product( $pid );
			if ( $p && ( (int) $p->get_rating_count() > 0 || (float) $p->get_average_rating() > 0 ) ) {
				$has_rating = true;
				break;
			}
		}
		if ( $has_rating ) {
			$available[] = 'rating';
		}

		$ui_settings = get_option( 'modep_ui', [] );
		$enabled_attributes = isset( $ui_settings['attrs'] ) && is_array( $ui_settings['attrs'] ) ? $ui_settings['attrs'] : [];
		foreach ( $enabled_attributes as $taxonomy => $enabled ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( ! $enabled || 0 !== strpos( $taxonomy, 'pa_' ) || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = wp_get_object_terms( $source_ids, $taxonomy, [ 'hide_empty' => true, 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$available[] = $taxonomy;
			}
		}

		return array_values( array_unique( $available ) );
	}

	/**
	 * Build UI blocks for filters to render.
	 *
	 * Returns an array of blocks in the form:
	 * [
	 * [
	 * 'id' => 'categories',
	 * 'label' => 'Categories',
	 * 'filter_key' => 'category',
	 * 'chips' => [
	 * ['value' => '', 'label' => 'All', 'active' => true],
	 * ['value' => '123', 'label' => 'Shoes (12)', ...],
	 * ],
	 * 'has_more' => true|false
	 * ],
	 * ...
	 * ]
	 *
	 * @param string[] $filters_to_render Canonical ids.
	 * @param int[]    $source_ids Source product IDs.
	 * @param int      $terms_limit Terms limit.
	 * @param string   $terms_orderby count|name
	 * @param string   $terms_order ASC|DESC
	 * @param string   $show_more yes|no
	 * @param bool     $category_hierarchy Preserve product-category ancestry.
	 * @param array    $ex Excludes: cat/tag/brand arrays
	 * @return array
	 */
	private static function build_filters_ui_blocks(
		array $filters_to_render,
		array $source_ids,
		int $terms_limit,
		string $terms_orderby,
		string $terms_order,
		string $show_more,
		bool $category_hierarchy,
		array $ex
	) : array {
		$blocks    = [];
		$source_ids = array_values( array_filter( array_map( 'absint', $source_ids ) ) );

		if ( empty( $filters_to_render ) || empty( $source_ids ) ) {
			return [];
		}

		foreach ( $filters_to_render as $fid ) {
			switch ( $fid ) {
				case 'categories':
					$chips_data = self::get_taxonomy_chips(
						$source_ids,
						'product_cat',
						$terms_limit,
						$terms_orderby,
						$terms_order,
						$show_more,
						$ex['cat'] ?? [],
						true, // include counts
						$category_hierarchy
					);
					if ( ! empty( $chips_data['chips'] ) ) {
						$blocks[] = [
							'id'         => 'categories',
							'label'      => __( 'Categories', 'modefilter-pro' ),
							'filter_key' => 'category',
							'chips'      => $chips_data['chips'],
							'has_more'   => $chips_data['has_more'],
						];
					}
					break;

				case 'tags':
					$chips_data = self::get_taxonomy_chips(
						$source_ids,
						'product_tag',
						$terms_limit,
						'name', // tags: name order is better UX
						'ASC',
						$show_more,
						$ex['tag'] ?? [],
						false
					);
					if ( ! empty( $chips_data['chips'] ) ) {
						$blocks[] = [
							'id'         => 'tags',
							'label'      => __( 'Tags', 'modefilter-pro' ),
							'filter_key' => 'tag',
							'chips'      => $chips_data['chips'],
							'has_more'   => $chips_data['has_more'],
						];
					}
					break;

				case 'brands':
					if ( taxonomy_exists( 'product_brand' ) ) {
						$chips_data = self::get_taxonomy_chips(
							$source_ids,
							'product_brand',
							$terms_limit,
							'name',
							'ASC',
							$show_more,
							$ex['brand'] ?? [],
							false
						);
						if ( ! empty( $chips_data['chips'] ) ) {
							$blocks[] = [
								'id'         => 'brands',
								'label'      => __( 'Brands', 'modefilter-pro' ),
								'filter_key' => 'brand',
								'chips'      => $chips_data['chips'],
								'has_more'   => $chips_data['has_more'],
							];
						}
					}
					break;

				case 'price':
					// Pro: simple chips for price ranges (no slider). JS can still pass min/max.
					$price_chips = self::build_price_chips();
					if ( ! empty( $price_chips ) ) {
						$blocks[] = [
							'id'         => 'price',
							'label'      => __( 'Price', 'modefilter-pro' ),
							'filter_key' => 'price',
							'chips'      => $price_chips,
							'has_more'   => false,
						];
					}
					break;

				case 'rating':
					$rating_chips = self::build_rating_chips();
					if ( ! empty( $rating_chips ) ) {
						$blocks[] = [
							'id'         => 'rating',
							'label'      => __( 'Rating', 'modefilter-pro' ),
							'filter_key' => 'rating',
							'chips'      => $rating_chips,
							'has_more'   => false,
						];
					}
					break;

				default:
					if ( 0 === strpos( $fid, 'pa_' ) && taxonomy_exists( $fid ) ) {
						$chips_data = self::get_taxonomy_chips(
							$source_ids,
							$fid,
							$terms_limit,
							'name',
							'ASC',
							$show_more,
							[],
							true
						);
						if ( ! empty( $chips_data['chips'] ) ) {
							$blocks[] = [
								'id'         => $fid,
								'label'      => wc_attribute_label( $fid ),
								'filter_key' => $fid,
								'chips'      => $chips_data['chips'],
								'has_more'   => $chips_data['has_more'],
							];
						}
					}
					break;
			}
		}

		return $blocks;
	}

	/**
	 * Get chips for a taxonomy within a set of products.
	 *
	 * @param int[]  $product_ids Source product IDs.
	 * @param string $taxonomy Taxonomy.
	 * @param int    $terms_limit Limit.
	 * @param string $orderby count|name
	 * @param string $order ASC|DESC
	 * @param string $show_more yes|no
	 * @param int[]  $exclude_ids Excluded term IDs.
	 * @param bool   $with_counts Whether to append (count).
	 * @param bool   $hierarchical Whether to preserve taxonomy ancestry.
	 * @return array{chips: array, has_more: bool}
	 */
	private static function get_taxonomy_chips(
		array $product_ids,
		string $taxonomy,
		int $terms_limit,
		string $orderby,
		string $order,
		string $show_more,
		array $exclude_ids,
		bool $with_counts,
		bool $hierarchical = false
	) : array {
		$product_ids = array_values( array_filter( array_map( 'absint', $product_ids ) ) );
		$exclude_ids = array_values( array_filter( array_map( 'absint', $exclude_ids ) ) );

		if ( empty( $product_ids ) || ! taxonomy_exists( $taxonomy ) ) {
			return [ 'chips' => [], 'has_more' => false ];
		}

		// Get term IDs present in these products.
		$term_ids = wp_get_object_terms( $product_ids, $taxonomy, [ 'hide_empty' => true, 'fields' => 'ids' ] );
		if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
			return [ 'chips' => [], 'has_more' => false ];
		}

		$term_ids = array_values( array_unique( array_map( 'absint', (array) $term_ids ) ) );
		if ( ! empty( $exclude_ids ) ) {
			$term_ids = array_values( array_diff( $term_ids, $exclude_ids ) );
		}
		if ( empty( $term_ids ) ) {
			return [ 'chips' => [], 'has_more' => false ];
		}

		if ( $hierarchical && is_taxonomy_hierarchical( $taxonomy ) ) {
			$ancestor_ids = [];
			foreach ( $term_ids as $term_id ) {
				$ancestor_ids = array_merge( $ancestor_ids, get_ancestors( $term_id, $taxonomy, 'taxonomy' ) );
			}
			$term_ids = array_values( array_unique( array_diff( array_merge( $term_ids, array_map( 'absint', $ancestor_ids ) ), $exclude_ids ) ) );
		}

		// Fetch terms with ordering.
		$args = [
			'taxonomy'   => $taxonomy,
			'include'    => $term_ids,
			'hide_empty' => ! $hierarchical,
			'orderby'    => ( 'count' === $orderby ) ? 'count' : 'name',
			'order'      => $order,
		];

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [ 'chips' => [], 'has_more' => false ];
		}

		$term_rows = $hierarchical && is_taxonomy_hierarchical( $taxonomy )
			? self::flatten_terms_hierarchy( $terms, $orderby, $order )
			: array_map( static function ( $term ) { return [ 'term' => $term, 'depth' => 0 ]; }, $terms );

		$total_terms = count( $term_rows );
		$has_more    = ( 'yes' === $show_more && $total_terms > $terms_limit );

		// If show_more is disabled, slice hard (performance win).
		$terms_to_render = ( 'yes' === $show_more )
			? $term_rows
			: array_slice( $term_rows, 0, $terms_limit );

		$chips = [];
		// All chip (default active state handled by JS; we keep markup consistent).
		$chips[] = [
			'value'  => '',
			'label'  => __( 'All', 'modefilter-pro' ),
			'active' => true,
			'hidden' => false,
		];

		foreach ( $terms_to_render as $idx => $row ) {
			$t     = $row['term'] ?? null;
			$depth = absint( $row['depth'] ?? 0 );
			if ( ! ( $t instanceof WP_Term ) ) {
				continue;
			}
			$label = (string) $t->name;
			if ( $with_counts ) {
				$label = sprintf( '%s (%d)', $label, (int) $t->count );
			}

			$is_hidden = ( 'yes' === $show_more && $idx >= $terms_limit );
			$chips[] = [
				'value'  => (string) (int) $t->term_id,
				'label'  => $label,
				'active' => false,
				'hidden' => $is_hidden,
				'parent' => (string) (int) $t->parent,
				'depth'  => $depth,
			];
		}

		return [
			'chips'    => $chips,
			'has_more' => $has_more,
		];
	}

	private static function flatten_terms_hierarchy( array $terms, string $orderby, string $order ) : array {
		$by_id    = [];
		$children = [];
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$by_id[ (int) $term->term_id ] = $term;
			}
		}
		foreach ( $by_id as $id => $term ) {
			$parent = isset( $by_id[ (int) $term->parent ] ) ? (int) $term->parent : 0;
			$children[ $parent ][] = $id;
		}

		$direction = 'DESC' === strtoupper( $order ) ? -1 : 1;
		$sort_ids  = static function ( array &$ids ) use ( $by_id, $orderby, $direction ) : void {
			usort( $ids, static function ( int $left, int $right ) use ( $by_id, $orderby, $direction ) : int {
				$a = $by_id[ $left ];
				$b = $by_id[ $right ];
				$result = 'count' === $orderby
					? ( (int) $a->count <=> (int) $b->count )
					: strcasecmp( (string) $a->name, (string) $b->name );
				return $direction * $result;
			} );
		};
		foreach ( $children as &$ids ) {
			$sort_ids( $ids );
		}
		unset( $ids );

		$rows    = [];
		$visited = [];
		$walk    = static function ( int $parent, int $depth ) use ( &$walk, &$rows, &$visited, $children, $by_id ) : void {
			foreach ( $children[ $parent ] ?? [] as $id ) {
				if ( isset( $visited[ $id ] ) ) {
					continue;
				}
				$visited[ $id ] = true;
				$rows[] = [ 'term' => $by_id[ $id ], 'depth' => $depth ];
				$walk( $id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		foreach ( array_keys( $by_id ) as $id ) {
			if ( ! isset( $visited[ $id ] ) ) {
				$rows[] = [ 'term' => $by_id[ $id ], 'depth' => 0 ];
			}
		}
		return $rows;
	}

	/**
	 * Build Pro price chips (no slider).
	 *
	 * Note: you can later make this configurable via shortcode attr price_ranges.
	 *
	 * @return array
	 */
	private static function build_price_chips() : array {
		// value: "min|max" for JS convenience.
		return [
			[ 'value' => '',        'label' => __( 'All', 'modefilter-pro' ), 'active' => true,  'hidden' => false ],
			[ 'value' => '0|50',    'label' => __( 'Under 50', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '50|100',  'label' => __( '50–100', 'modefilter-pro' ),  'active' => false, 'hidden' => false ],
			[ 'value' => '100|200', 'label' => __( '100–200', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '200|',    'label' => __( '200+', 'modefilter-pro' ),    'active' => false, 'hidden' => false ],
		];
	}

	/**
	 * Build Pro rating chips.
	 *
	 * value: minimum rating integer.
	 *
	 * @return array
	 */
	private static function build_rating_chips() : array {
		return [
			[ 'value' => '', 'label' => __( 'All', 'modefilter-pro' ), 'active' => true, 'hidden' => false ],
			[ 'value' => '5', 'label' => __( '★★★★★', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '4', 'label' => __( '★★★★☆ & up', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '3', 'label' => __( '★★★☆☆ & up', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '2', 'label' => __( '★★☆☆☆ & up', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
			[ 'value' => '1', 'label' => __( '★☆☆☆☆ & up', 'modefilter-pro' ), 'active' => false, 'hidden' => false ],
		];
	}

	/**
	 * Render the shared filter control markup for every presentation style.
	 *
	 * @param array $blocks UI blocks.
	 * @return string
	 */
	private static function render_filters_ui( array $blocks, string $style, bool $hierarchical ) : string {
		if ( empty( $blocks ) ) {
			// Keep layout stable: empty sidebar wrapper.
			$css_class = 'modep-sidebar modep--ui-' . esc_attr( $style );
			return '<aside class="' . esc_attr( $css_class ) . '" aria-label="' . esc_attr__( 'Filters', 'modefilter-pro' ) . '"></aside>';
		}

		ob_start();
		$css_class = 'modep-sidebar modep--ui-' . esc_attr( $style );
		?>
		<aside class="<?php echo esc_attr( $css_class ); ?>" aria-label="<?php esc_attr_e( 'Filters', 'modefilter-pro' ); ?>" data-filter-style="<?php echo esc_attr( $style ); ?>">
			<?php foreach ( $blocks as $block ) : ?>
				<?php
				$label      = isset( $block['label'] ) ? (string) $block['label'] : '';
				$filter_key = isset( $block['filter_key'] ) ? (string) $block['filter_key'] : '';
				$chips      = isset( $block['chips'] ) && is_array( $block['chips'] ) ? $block['chips'] : [];
				$has_more   = ! empty( $block['has_more'] );
				?>
				<?php if ( '' !== $filter_key && ! empty( $chips ) ) : ?>
					<section class="modep-filter-box<?php echo $hierarchical && 'category' === $filter_key ? ' modep-filter-box--hierarchical' : ''; ?>" data-filter-block="<?php echo esc_attr( $filter_key ); ?>">
						<h3 class="modep-filter-title"><?php echo esc_html( $label ); ?></h3>

						<div class="modep-chips modep-options" data-filter="<?php echo esc_attr( $filter_key ); ?>" role="<?php echo 'radios' === $style ? 'radiogroup' : 'group'; ?>" aria-label="<?php echo esc_attr( $label ); ?>">
							<?php foreach ( $chips as $chip ) : ?>
								<?php
								$value  = isset( $chip['value'] ) ? (string) $chip['value'] : '';
								$text   = isset( $chip['label'] ) ? (string) $chip['label'] : '';
								$active = ! empty( $chip['active'] );
								$hidden = ! empty( $chip['hidden'] );
								$depth  = absint( $chip['depth'] ?? 0 );
							$cls    = 'modep-chip'
								. ' modep-option'
								. ( $active ? ' is-selected' : '' )
								. ( $value === '' ? ' modep-chip--all' : '' )
								. ( $hidden ? ' modep-chip--hidden' : '' )
								. ( $depth > 0 ? ' modep-option--child' : '' );
							$role = 'radios' === $style ? 'radio' : ( in_array( $style, [ 'checkboxes', 'toggles', 'hierarchical' ], true ) ? 'checkbox' : '' );
								?>
								<button
									type="button"
									class="<?php echo esc_attr( $cls ); ?>"
									data-term="<?php echo esc_attr( $value ); ?>"
									data-depth="<?php echo esc_attr( (string) $depth ); ?>"
									style="--modep-depth: <?php echo esc_attr( (string) $depth ); ?>; --modep-indent: <?php echo esc_attr( (string) ( $depth * 18 ) ); ?>px; --modep-branch-indent: <?php echo esc_attr( (string) ( max( 0, $depth - 1 ) * 18 ) ); ?>px;"
									<?php echo '' !== $role ? 'role="' . esc_attr( $role ) . '" aria-checked="' . ( $active ? 'true' : 'false' ) . '"' : 'aria-pressed="' . ( $active ? 'true' : 'false' ) . '"'; ?>
									<?php echo $hidden ? 'data-hidden="1" hidden' : ''; ?>
								>
									<?php echo esc_html( $text ); ?>
								</button>
							<?php endforeach; ?>
						</div>

						<?php if ( $has_more ) : ?>
							<button type="button" class="modep-terms-more" data-action="toggle-more">
								<span class="modep-terms-more__more"><?php esc_html_e( 'Show more', 'modefilter-pro' ); ?></span>
								<span class="modep-terms-more__less" hidden><?php esc_html_e( 'Show less', 'modefilter-pro' ); ?></span>
							</button>
						<?php endif; ?>
					</section>
				<?php endif; ?>
			<?php endforeach; ?>
		</aside>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Determine which product IDs are the "filter source pool".
	 *
	 * @param bool   $only_catalog      Catalog-only mode.
	 * @param string $sellable_cat_slug Optional sellable category slug.
	 * @return int[]
	 */
	private static function get_source_product_ids( bool $only_catalog, string $sellable_cat_slug ) : array {
		$sellable_cat_slug = sanitize_title( $sellable_cat_slug );
		$cache_version     = class_exists( 'MODEP_Catalog_Index' ) ? MODEP_Catalog_Index::cache_version() : 1;
		$cache_key         = 'modep_source_' . md5( ( $only_catalog ? 'catalog' : 'sell' ) . '|' . $sellable_cat_slug . '|' . $cache_version );
		$cached            = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return array_map( 'absint', $cached );
		}
		$remember = static function ( array $ids ) use ( $cache_key ) : array {
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			set_transient( $cache_key, $ids, 10 * MINUTE_IN_SECONDS );
			return $ids;
		};

		if ( class_exists( 'MODEP_Catalog_Index' ) && MODEP_Catalog_Index::is_ready() ) {
			$indexed_args = [
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => MODEP_Catalog_Index::add_mode_clause( [], $only_catalog ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			];
			if ( ! $only_catalog && '' !== $sellable_cat_slug ) {
				$indexed_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => [ $sellable_cat_slug ],
					],
				];
			}
			$indexed = new WP_Query( $indexed_args );
			return $remember( (array) $indexed->posts );
		}

		// Catalog pool
		if ( $only_catalog && class_exists( 'MODEP_Catalog_Mode' ) ) {
			$base = new WP_Query(
				[
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$ids = [];
			if ( $base->have_posts() ) {
				foreach ( (array) $base->posts as $pid ) {
					$pid = absint( $pid );
					if ( ! $pid ) {
						continue;
					}

					$product = wc_get_product( $pid );
					if ( ! $product ) {
						continue;
					}

					$mode = MODEP_Catalog_Mode::get_effective_mode( $product );
					if ( 'catalog' === $mode ) {
						$ids[] = $pid;
					}
				}
			}
			wp_reset_postdata();

			return $remember( $ids );
		}

		// Sellable pool, variant A: explicit sellable base category
		if ( '' !== $sellable_cat_slug ) {
			$q = new WP_Query(
				[
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'tax_query'              => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => [ $sellable_cat_slug ],
						],
					],
					'no_found_rows'          => true,
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$ids = $q->have_posts() ? array_map( 'absint', (array) $q->posts ) : [];
			wp_reset_postdata();

			return $remember( $ids );
		}

		// Sellable pool, variant B: if Catalog Mode exists, sellable means "non-catalog"
		if ( class_exists( 'MODEP_Catalog_Mode' ) ) {
			$base = new WP_Query(
				[
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$ids = [];
			if ( $base->have_posts() ) {
				foreach ( (array) $base->posts as $pid ) {
					$pid = absint( $pid );
					if ( ! $pid ) {
						continue;
					}

					$product = wc_get_product( $pid );
					if ( ! $product ) {
						continue;
					}

					$mode = MODEP_Catalog_Mode::get_effective_mode( $product );
					if ( 'catalog' !== $mode ) {
						$ids[] = $pid;
					}
				}
			}
			wp_reset_postdata();

			return $remember( $ids );
		}

		// Sellable pool, fallback: all products
		$q = new WP_Query(
			[
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$ids = $q->have_posts() ? array_map( 'absint', (array) $q->posts ) : [];
		wp_reset_postdata();

		return $remember( $ids );
	}
}
