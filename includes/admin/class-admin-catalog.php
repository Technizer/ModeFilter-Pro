<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MODEP_Admin_Catalog {

	/**
	 * Register Catalog + PayPal settings.
	 */
	public static function register_settings() : void {

		// Main Catalog mode option (single array option).
		register_setting(
			'modep_catalog',
			MODEP_Catalog_Mode::OPT,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_catalog' ],
				'default'           => [],
			]
		);

		// PayPal promo: enabled / disabled (simple checkbox).
		register_setting(
			'modep_catalog',
			'modep_paypal_promo_enabled',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ __CLASS__, 'sanitize_checkbox' ],
				'default'           => 0,
			]
		);

		// PayPal promo: minimum product price for showing the block.
		register_setting(
			'modep_catalog',
			'modep_paypal_min_amount',
			[
				'type'              => 'string',
				'sanitize_callback' => [ __CLASS__, 'sanitize_price' ],
				'default'           => '30',
			]
		);

		// We still register a generic section for the main catalog array option,
		// even though we render the actual fields manually in ::render().
		add_settings_section( 'modep_cat_main', '', '__return_false', 'modep_catalog' );
		add_settings_field( 'modep_cat_global', '', [ __CLASS__, 'field_cat_global' ], 'modep_catalog', 'modep_cat_main' );
		add_settings_field( 'modep_cat_price',  '', [ __CLASS__, 'field_cat_price'  ], 'modep_catalog', 'modep_cat_main' );
		add_settings_field( 'modep_cat_replace', '', [ __CLASS__, 'field_cat_replace' ], 'modep_catalog', 'modep_cat_main' );
		add_settings_field( 'modep_cat_terms', '', [ __CLASS__, 'field_term_defaults' ], 'modep_catalog', 'modep_cat_main' );
	}

	/**
	 * Sanitize main Catalog mode option (array).
	 *
	 * @param mixed $input Raw input from form.
	 * @return array
	 */
	public static function sanitize_catalog( $input ) : array {
		$input = (array) $input;

		$old = MODEP_Catalog_Mode::get_settings();

		$allowed_modes = [ 'sell', 'catalog', 'hybrid' ];
		$mode          = (string) ( $input['global_mode'] ?? 'sell' );
		$mode          = in_array( $mode, $allowed_modes, true ) ? $mode : 'sell';

		$out = $old;
		$out['global_mode']    = $mode;
		$out['hide_prices']    = ! empty( $input['hide_prices'] ) ? 'yes' : 'no';
		$out['replace_button'] = ! empty( $input['replace_button'] ) ? 'yes' : 'no';

		// Term defaults (quick Hybrid configuration).
		$out['term_defaults'] = [
			'catalog' => [
				'product_cat'   => [],
				'product_tag'   => [],
				'product_brand' => [],
			],
			'sell'    => [
				'product_cat'   => [],
				'product_tag'   => [],
				'product_brand' => [],
			],
		];

		$taxes = [ 'product_cat', 'product_tag' ];
		if ( taxonomy_exists( 'product_brand' ) ) {
			$taxes[] = 'product_brand';
		}

		foreach ( $taxes as $tax ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$raw_catalog = isset( $input['term_defaults']['catalog'][ $tax ] ) ? (array) $input['term_defaults']['catalog'][ $tax ] : [];
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$raw_sell    = isset( $input['term_defaults']['sell'][ $tax ] ) ? (array) $input['term_defaults']['sell'][ $tax ] : [];

			$out['term_defaults']['catalog'][ $tax ] = array_values( array_filter( array_map( 'absint', $raw_catalog ) ) );
			$out['term_defaults']['sell'][ $tax ]    = array_values( array_filter( array_map( 'absint', $raw_sell ) ) );
		}

		// Sync term meta so the runtime logic can stay fast.
		self::sync_term_defaults_meta( $old['term_defaults'] ?? [], $out['term_defaults'] );

		return $out;
	}

	/**
	 * Sync configured term defaults to term meta.
	 *
	 * We store the configuration in the option for UI convenience,
	 * but the catalog-mode engine reads term meta.
	 *
	 * @param array $old Old term defaults.
	 * @param array $new New term defaults.
	 */
	private static function sync_term_defaults_meta( array $old, array $new ) : void {
		$taxes = [ 'product_cat', 'product_tag' ];
		if ( taxonomy_exists( 'product_brand' ) ) {
			$taxes[] = 'product_brand';
		}

		foreach ( $taxes as $tax ) {
			$old_catalog = isset( $old['catalog'][ $tax ] ) ? (array) $old['catalog'][ $tax ] : [];
			$old_sell    = isset( $old['sell'][ $tax ] ) ? (array) $old['sell'][ $tax ] : [];
			$old_all     = array_unique( array_merge( array_map( 'absint', $old_catalog ), array_map( 'absint', $old_sell ) ) );

			$new_catalog = isset( $new['catalog'][ $tax ] ) ? (array) $new['catalog'][ $tax ] : [];
			$new_sell    = isset( $new['sell'][ $tax ] ) ? (array) $new['sell'][ $tax ] : [];
			$new_all     = array_unique( array_merge( array_map( 'absint', $new_catalog ), array_map( 'absint', $new_sell ) ) );

			// Clear removed.
			$to_clear = array_diff( $old_all, $new_all );
			foreach ( $to_clear as $term_id ) {
				if ( $term_id > 0 ) {
					delete_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY );
				}
			}

			// Apply new.
			foreach ( $new_catalog as $term_id ) {
				if ( $term_id > 0 ) {
					update_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY, 'catalog' );
				}
			}
			foreach ( $new_sell as $term_id ) {
				if ( $term_id > 0 ) {
					update_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY, 'sell' );
				}
			}
		}
	}

	/**
	 * Sanitize a checkbox into 0/1.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_checkbox( $value ) : int {
		return ! empty( $value ) ? 1 : 0;
	}

	/**
	 * Sanitize a price-like numeric string (for min amount).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_price( $value ) : string {
		$value = (string) $value;
		$value = str_replace( ',', '.', $value );
		$value = preg_replace( '/[^0-9.]/', '', $value );
		return (string) $value;
	}

	/** ---------- Fields: Catalog mode ---------- */

	public static function field_cat_global() : void {
		$s = MODEP_Catalog_Mode::get_settings();
		?>
		<label class="modep-radio">
			<input
				type="radio"
				name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]"
				value="sell"
				<?php checked( $s['global_mode'], 'sell' ); ?>
			/>
			<span><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></span>
		</label>
		<label class="modep-radio">
			<input
				type="radio"
				name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]"
				value="catalog"
				<?php checked( $s['global_mode'], 'catalog' ); ?>
			/>
			<span><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></span>
		</label>
		<label class="modep-radio">
			<input
				type="radio"
				name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]"
				value="hybrid"
				<?php checked( $s['global_mode'], 'hybrid' ); ?>
			/>
			<span><?php esc_html_e( 'Hybrid', 'modefilter-pro' ); ?></span>
		</label>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Hybrid behaves like Sell by default, but you can quickly mark specific categories/tags/brands as Catalog below.', 'modefilter-pro' ); ?>
		</p>
		<?php
	}

	public static function field_cat_price() : void {
		$s = MODEP_Catalog_Mode::get_settings();
		?>
		<label class="modep-switch">
			<input
				type="checkbox"
				name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[hide_prices]"
				value="yes"
				<?php checked( $s['hide_prices'], 'yes' ); ?>
			/>
			<span class="modep-switch-ui"></span>
			<span class="modep-switch-label">
				<?php esc_html_e( 'Hide price HTML when in Catalog mode', 'modefilter-pro' ); ?>
			</span>
		</label>
		<?php
	}

	public static function field_cat_replace() : void {
		$s = MODEP_Catalog_Mode::get_settings();
		?>
		<label class="modep-switch">
			<input
				type="checkbox"
				name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[replace_button]"
				value="yes"
				<?php checked( $s['replace_button'], 'yes' ); ?>
			/>
			<span class="modep-switch-ui"></span>
			<span class="modep-switch-label">
				<?php esc_html_e( 'Replace “Add to cart” button for Catalog products', 'modefilter-pro' ); ?>
			</span>
		</label>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Button label/URL should be configured per grid instance (Shortcode Builder / Elementor widget), so you can use different CTAs in different sections.', 'modefilter-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Back-compat: older templates referenced field_cat_button().
	 *
	 * @deprecated 1.0.0 Use field_cat_replace() instead.
	 */
	public static function field_cat_button() : void {
		self::field_cat_replace();
	}

	/**
	 * Term defaults UI.
	 */
	public static function field_term_defaults() : void {
		$s  = MODEP_Catalog_Mode::get_settings();
		$td = isset( $s['term_defaults'] ) && is_array( $s['term_defaults'] ) ? $s['term_defaults'] : [];

		$taxes = [
			'product_cat' => __( 'Categories', 'modefilter-pro' ),
			'product_tag' => __( 'Tags', 'modefilter-pro' ),
		];
		if ( taxonomy_exists( 'product_brand' ) ) {
			$taxes['product_brand'] = __( 'Brands', 'modefilter-pro' );
		}

		echo '<p class="description">' . esc_html__( 'Quickly set default modes for entire categories/tags/brands. Products set to “Inherit” will follow these term defaults.', 'modefilter-pro' ) . '</p>';

		foreach ( $taxes as $tax => $label ) {
			$catalog_sel = isset( $td['catalog'][ $tax ] ) ? (array) $td['catalog'][ $tax ] : [];
			$sell_sel    = isset( $td['sell'][ $tax ] ) ? (array) $td['sell'][ $tax ] : [];

			echo '<div class="modep-columns modep-columns--2" style="margin-top:14px;">';
			echo '  <div class="modep-columns__col">';
			/* translators: %s: taxonomy label (e.g., Categories, Tags). */
			echo '    <h4 style="margin:0 0 6px;">' . esc_html( sprintf( __( '%s default: Catalog', 'modefilter-pro' ), (string) $label ) ) . '</h4>';
			self::render_terms_multiselect( $tax, MODEP_Catalog_Mode::OPT . '[term_defaults][catalog][' . $tax . '][]', $catalog_sel );
			echo '  </div>';
			echo '  <div class="modep-columns__col">';
			/* translators: %s: taxonomy label (e.g., Categories, Tags). */
			echo '    <h4 style="margin:0 0 6px;">' . esc_html( sprintf( __( '%s default: Sell', 'modefilter-pro' ), (string) $label ) ) . '</h4>';
			self::render_terms_multiselect( $tax, MODEP_Catalog_Mode::OPT . '[term_defaults][sell][' . $tax . '][]', $sell_sel );
			echo '  </div>';
			echo '</div>';
		}
	}

	/**
	 * Render a simple <select multiple> for terms.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $name     Field name.
	 * @param array  $selected Selected term IDs.
	 */
	private static function render_terms_multiselect( string $taxonomy, string $name, array $selected ) : void {
		$selected = array_map( 'absint', $selected );

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<p>' . esc_html__( 'No terms found.', 'modefilter-pro' ) . '</p>';
			return;
		}

		printf(
			'<select class="modep-select" multiple="multiple" size="8" name="%s" style="min-width:260px; width:100%%;">',
			esc_attr( $name )
		);

		foreach ( $terms as $t ) {
			$tid = absint( $t->term_id );

			// FIX: escape tid for attribute context to satisfy Plugin Check.
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( (string) $tid ),
				selected( in_array( $tid, $selected, true ), true, false ),
				esc_html( $t->name )
			);
		}

		echo '</select>';
		echo '<p class="description" style="margin-top:6px;">' . esc_html__( 'Hold Ctrl (Windows) / Cmd (Mac) to select multiple.', 'modefilter-pro' ) . '</p>';
	}

	/** ---------- Fields: PayPal promo ---------- */

	/**
	 * Combined PayPal promo field (enable + min amount).
	 */
	public static function field_paypal_promo() : void {
		$enabled = (int) get_option( 'modep_paypal_promo_enabled', 0 );
		$min     = (string) get_option( 'modep_paypal_min_amount', '30' );
		?>
		<label class="modep-switch">
			<input
				type="checkbox"
				name="modep_paypal_promo_enabled"
				value="1"
				<?php checked( 1, $enabled ); ?>
			/>
			<span class="modep-switch-ui"></span>
			<span class="modep-switch-label">
				<?php esc_html_e( 'Show PayPal promo on eligible product cards', 'modefilter-pro' ); ?>
			</span>
		</label>

		<div class="modep-row" style="margin-top:8px;">
			<label>
				<?php esc_html_e( 'Minimum product price', 'modefilter-pro' ); ?>
				<input
					type="number"
					name="modep_paypal_min_amount"
					value="<?php echo esc_attr( $min ); ?>"
					min="0"
					step="0.01"
					class="small-text"
					style="margin-left:8px;"
				/>
			</label>
			<p class="description">
				<?php esc_html_e( 'Only show the PayPal promo for products priced at or above this amount.', 'modefilter-pro' ); ?>
			</p>
		</div>
		<?php
	}

	/** ---------- Render page ---------- */

	public static function render() : void {
		MODEP_Admin_UI::page_open(
			__( 'Catalog / Sell Mode', 'modefilter-pro' ),
			__( 'Switch between sell and catalog behaviors. Per-taxonomy and per-product overrides still apply.', 'modefilter-pro' )
		);

		MODEP_Admin_UI::tabs( 'catalog' );

		echo '<form method="post" action="options.php" class="modep-form">';
		settings_fields( 'modep_catalog' );

		// Section: Mode
		MODEP_Admin_UI::section_card_open(
			__( 'Global Mode', 'modefilter-pro' ),
			__( 'Choose the default behavior for the entire store. Useful for temporary “catalog only” campaigns.', 'modefilter-pro' )
		);
		self::field_cat_global();
		MODEP_Admin_UI::section_card_close();

		// Section: Prices
		MODEP_Admin_UI::section_card_open(
			__( 'Price Visibility', 'modefilter-pro' ),
			__( 'When Catalog is active, hide price display globally to discourage direct purchasing.', 'modefilter-pro' )
		);
		self::field_cat_price();
		MODEP_Admin_UI::section_card_close();

		// Section: Replace button
		MODEP_Admin_UI::section_card_open(
			__( 'Replace “Add to cart”', 'modefilter-pro' ),
			__( 'For catalog-mode products, replace the Add to cart button with your enquiry CTA. Button label/URL can be configured per shortcode/widget.', 'modefilter-pro' )
		);
		self::field_cat_replace();
		MODEP_Admin_UI::section_card_close();

		// Section: Term defaults (Hybrid rules).
		MODEP_Admin_UI::section_card_open(
			__( 'Term Defaults (Hybrid rules)', 'modefilter-pro' ),
			__( 'Quickly mark categories/tags/brands as Catalog or Sell by default. Products set to “Inherit” will follow these term rules.', 'modefilter-pro' )
		);
		self::field_term_defaults();
		MODEP_Admin_UI::section_card_close();

		// Section: PayPal promo
		MODEP_Admin_UI::section_card_open(
			__( 'Payment promo (PayPal)', 'modefilter-pro' ),
			__( 'Optionally show a PayPal “Pay in 4” style promo on eligible product cards.', 'modefilter-pro' )
		);
		self::field_paypal_promo();
		MODEP_Admin_UI::section_card_close();

		submit_button( __( 'Save Settings', 'modefilter-pro' ) );
		echo '</form>';

		MODEP_Admin_UI::page_close();
	}
}
