<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ModeFilter Pro — Template Kits system.
 * 
 * Provides pre-configured templates for grid + filters combinations.
 */
final class MODEP_Template_Kits {

	/**
	 * Get all available template kits.
	 * 
	 * @return array Template kits indexed by ID with config data.
	 */
	public static function get_kits() : array {
		return [
			'classic' => [
				'name'        => __( 'Classic Grid', 'modefilter-pro' ),
				'description' => __( 'Polished blue storefront with chip filters and a balanced 3-column grid', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/classic.svg',
				'config'      => [
					'columns'           => 3,
					'grid_layout'       => 'grid',
					'preset'            => 'normal',
					'filter_style'      => 'chips',
					'filter_position'   => 'left',
					'filters_mode'      => 'auto',
					'pagination'        => 'load_more',
					'loader_style'      => 'skeleton',
					'only_catalog'      => 'no',
					'link_whole_card'   => 'no',
					'terms_limit'       => 10,
					'terms_orderby'     => 'count',
					'terms_order'       => 'DESC',
					'category_hierarchy'=> 'no',
				],
			],
			'minimal' => [
				'name'        => __( 'Minimal (2 Column)', 'modefilter-pro' ),
				'description' => __( 'Editorial monochrome layout with quiet checkbox filters and spacious cards', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/minimal.svg',
				'config'      => [
					'columns'           => 2,
					'grid_layout'       => 'grid',
					'preset'            => 'minimal',
					'filter_style'      => 'checkboxes',
					'filter_position'   => 'left',
					'filters_mode'      => 'auto',
					'pagination'        => 'numbers',
					'loader_style'      => 'dots',
					'only_catalog'      => 'no',
					'terms_limit'       => 8,
					'terms_orderby'     => 'name',
					'terms_order'       => 'ASC',
					'show_excerpt'      => 'no',
					'link_whole_card'   => 'yes',
					'category_hierarchy'=> 'no',
				],
			],
			'masonry' => [
				'name'        => __( 'Masonry', 'modefilter-pro' ),
				'description' => __( 'Violet discovery wall with top-mounted toggle filters and flowing card heights', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/masonry.svg',
				'config'      => [
					'columns'           => 4,
					'grid_layout'       => 'masonry',
					'masonry_gap'       => 28,
					'preset'            => 'normal',
					'filter_style'      => 'toggles',
					'filter_position'   => 'top',
					'filters_mode'      => 'auto',
					'pagination'        => 'load_more',
					'loader_style'      => 'pulse',
					'only_catalog'      => 'no',
					'link_whole_card'   => 'no',
					'terms_limit'       => 12,
					'terms_orderby'     => 'count',
					'terms_order'       => 'DESC',
					'show_excerpt'      => 'yes',
					'category_hierarchy'=> 'no',
				],
			],
			'hierarchy' => [
				'name'        => __( 'Hierarchy Browser', 'modefilter-pro' ),
				'description' => __( 'Deep green category navigator with a structured tree and compact product grid', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/hierarchy.svg',
				'config'      => [
					'columns'           => 3,
					'grid_layout'       => 'grid',
					'preset'            => 'normal',
					'filter_style'      => 'hierarchical',
					'filter_position'   => 'left',
					'filters_mode'      => 'auto',
					'pagination'        => 'numbers',
					'loader_style'      => 'skeleton',
					'only_catalog'      => 'no',
					'link_whole_card'   => 'no',
					'terms_limit'       => 18,
					'terms_orderby'     => 'name',
					'terms_order'       => 'ASC',
					'category_hierarchy'=> 'yes',
				],
			],
			'justified' => [
				'name'        => __( 'Justified Gallery', 'modefilter-pro' ),
				'description' => __( 'Image-led magenta gallery with radio filters, fixed rows, and overlay titles', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/justified.svg',
				'config'      => [
					'columns'           => 4,
					'grid_layout'       => 'justified',
					'justified_row_height' => 238,
					'preset'            => 'overlay',
					'filter_style'      => 'radios',
					'filter_position'   => 'top',
					'filters_mode'      => 'auto',
					'pagination'        => 'load_more',
					'loader_style'      => 'dots',
					'only_catalog'      => 'no',
					'terms_limit'       => 10,
					'terms_orderby'     => 'name',
					'terms_order'       => 'ASC',
					'show_excerpt'      => 'no',
					'link_whole_card'   => 'yes',
					'custom_layout'     => 'badge|image|title|price|!add_to_cart|!paypal|!excerpt',
					'category_hierarchy'=> 'no',
				],
			],
			'catalog' => [
				'name'        => __( 'Catalog Mode', 'modefilter-pro' ),
				'description' => __( 'Warm quotation catalog with right-side filters and horizontal enquiry cards', 'modefilter-pro' ),
				'thumbnail'   => MODEP_PLUGIN_URL . 'assets/kit-thumbnails/catalog.svg',
				'config'      => [
					'columns'           => 2,
					'grid_layout'       => 'grid',
					'preset'            => 'normal',
					'filter_style'      => 'chips',
					'filter_position'   => 'right',
					'filters_mode'      => 'auto',
					'pagination'        => 'numbers',
					'loader_style'      => 'skeleton',
					'terms_limit'       => 9,
					'terms_orderby'     => 'name',
					'terms_order'       => 'ASC',
					'only_catalog'      => 'yes',
					'link_whole_card'   => 'no',
					'show_excerpt'      => 'yes',
					'excerpt_length'    => 16,
					'catalog_button_text' => __( 'Request a quote', 'modefilter-pro' ),
					'category_hierarchy'=> 'no',
				],
			],
		];
	}

	/**
	 * Get a single template kit config by ID.
	 * 
	 * @param string $kit_id Template kit ID.
	 * @return array|null Template config or null if not found.
	 */
	public static function get_kit( string $kit_id ) : ?array {
		$kit_id = self::sanitize_kit_id( $kit_id );
		$kits = self::get_kits();
		return $kits[ $kit_id ] ?? null;
	}

	/**
	 * Validate a kit ID against the registered kit collection.
	 *
	 * @param string $kit_id Candidate kit ID.
	 * @return string Valid kit ID or "none".
	 */
	public static function sanitize_kit_id( string $kit_id ) : string {
		$kit_id = sanitize_key( $kit_id );
		return array_key_exists( $kit_id, self::get_kits() ) ? $kit_id : 'none';
	}

	/**
	 * Get only a kit's shortcode-safe configuration.
	 *
	 * @param string $kit_id Kit ID.
	 * @return array<string,mixed>
	 */
	public static function get_config( string $kit_id ) : array {
		$kit = self::get_kit( $kit_id );
		return $kit && isset( $kit['config'] ) && is_array( $kit['config'] ) ? $kit['config'] : [];
	}

	/**
	 * Apply kit defaults while preserving explicitly supplied shortcode values.
	 *
	 * @param array<string,mixed> $atts Raw shortcode attributes.
	 * @return array<string,mixed>
	 */
	public static function apply_to_atts( array $atts ) : array {
		$requested = (string) ( $atts['template_kit'] ?? $atts['modep_template_kit'] ?? 'none' );
		$kit_id    = self::sanitize_kit_id( $requested );
		if ( 'none' === $kit_id ) {
			$atts['template_kit'] = 'none';
			return $atts;
		}

		$atts = array_merge( self::get_config( $kit_id ), $atts );
		$atts['template_kit'] = $kit_id;
		unset( $atts['modep_template_kit'] );
		return $atts;
	}

	/**
	 * Get default template kit.
	 * 
	 * @return array Default template config.
	 */
	public static function get_default() : array {
		$kits = self::get_kits();
		return $kits['classic'] ?? [];
	}

	/**
	 * Render kit selector HTML for admin.
	 * 
	 * @param string $selected Selected kit ID.
	 * @return void
	 */
	public static function render_selector( string $selected = 'classic' ) : void {
		$kits = self::get_kits();
		?>
		<div class="modep-kit-selector" data-builder-url="<?php echo esc_url( admin_url( 'admin.php?page=modefilter-pro-builder' ) ); ?>">
			<?php foreach ( $kits as $kit_id => $kit ) : ?>
				<label class="modep-kit-option <?php echo esc_attr( $selected === $kit_id ? 'is-selected' : '' ); ?>">
					<input 
						type="radio" 
						name="modep_template_kit" 
						value="<?php echo esc_attr( $kit_id ); ?>" 
						<?php checked( $selected, $kit_id ); ?>
						class="modep-kit-radio"
					/>
					<div class="modep-kit-card">
						<?php if ( ! empty( $kit['thumbnail'] ) ) : ?>
							<img src="<?php echo esc_url( $kit['thumbnail'] ); ?>" alt="<?php echo esc_attr( $kit['name'] ); ?>" class="modep-kit-thumb" />
						<?php endif; ?>
						<h4 class="modep-kit-name"><?php echo esc_html( $kit['name'] ); ?></h4>
						<p class="modep-kit-desc"><?php echo esc_html( $kit['description'] ); ?></p>
					</div>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Generate shortcode from template kit.
	 * 
	 * @param string $kit_id Template kit ID.
	 * @param array  $override Additional attributes to override kit config.
	 * @return string Shortcode string.
	 */
	public static function generate_shortcode( string $kit_id, array $override = [] ) : string {
		$kit = self::get_kit( $kit_id );
		if ( ! $kit ) {
			return '';
		}

		$config = array_merge( $kit['config'] ?? [], $override );
		$config = array_merge( [ 'template_kit' => $kit_id ], $config );
		$attrs  = [];

		foreach ( $config as $key => $value ) {
			if ( in_array( $value, [ '', null, false ], true ) ) {
				continue;
			}
			if ( 'yes' === (string) $value || true === $value ) {
				$attrs[] = $key . '="yes"';
			} else {
				$attrs[] = $key . '="' . esc_attr( (string) $value ) . '"';
			}
		}

		return '[modep_filters ' . implode( ' ', $attrs ) . ']';
	}
}
