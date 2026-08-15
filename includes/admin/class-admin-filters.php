<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class MODEP_Admin_Filters {

    public static function register_settings() : void {
        register_setting( 'modep_ui', MODEP_Admin::OPT_UI, [
            'type'              => 'array',
            'sanitize_callback' => [ __CLASS__, 'sanitize_ui' ],
            'default'           => [],
        ] );

        add_settings_section( 'modep_ui_main', '', '__return_false', 'modep_ui' ); // we render sections manually
        add_settings_field( 'modep_ui_core',  '', [ __CLASS__, 'field_core'  ], 'modep_ui', 'modep_ui_main' );
        add_settings_field( 'modep_ui_presentation', '', [ __CLASS__, 'field_presentation' ], 'modep_ui', 'modep_ui_main' );
        add_settings_field( 'modep_ui_attrs', '', [ __CLASS__, 'field_attrs' ], 'modep_ui', 'modep_ui_main' );
        add_settings_field( 'modep_ui_custom', '', [ __CLASS__, 'field_custom' ], 'modep_ui', 'modep_ui_main' ); // ADDED: Field for custom filters
    }

    public static function sanitize_ui( $input ) {
        $input = is_array( $input ) ? $input : [];
        $out = [
            'core'  => [
                'category' => ! empty( $input['core']['category'] ) ? 1 : 0,
                'tag'      => ! empty( $input['core']['tag'] ) ? 1 : 0,
                'brand'    => ! empty( $input['core']['brand'] ) ? 1 : 0,
                'price'    => ! empty( $input['core']['price'] ) ? 1 : 0,
                'stock'    => ! empty( $input['core']['stock'] ) ? 1 : 0,
            ],
            'attrs' => [],
            'custom' => [], // ADDED: Initialize custom array
            'presentation' => [
                'filter_style'       => self::sanitize_choice( $input['presentation']['filter_style'] ?? 'chips', [ 'chips', 'checkboxes', 'radios', 'toggles', 'hierarchical' ], 'chips' ),
                'category_hierarchy' => ! empty( $input['presentation']['category_hierarchy'] ) ? 'yes' : 'no',
                'loader_style'       => self::sanitize_choice( $input['presentation']['loader_style'] ?? 'spinner', [ 'spinner', 'skeleton', 'dots', 'pulse', 'custom', 'none' ], 'spinner' ),
                'loader_image'       => esc_url_raw( (string) ( $input['presentation']['loader_image'] ?? '' ) ),
            ],
        ];

        if ( ! empty( $input['attrs'] ) && is_array( $input['attrs'] ) ) {
            foreach ( $input['attrs'] as $tx => $flag ) {
                if ( taxonomy_exists( $tx ) ) $out['attrs'][ $tx ] = $flag ? 1 : 0;
            }
        }
        
        // ADDED: Custom Filter Sanitization
        if ( ! empty( $input['custom'] ) && is_array( $input['custom'] ) ) {
            do_action( 'modep_before_custom_filter_sanitize' );
            $custom_filters = self::get_custom_filters();
            $custom_keys = array_keys( $custom_filters );

            foreach ( $input['custom'] as $key => $flag ) {
                $key = sanitize_key( $key );
                if ( in_array( $key, $custom_keys, true ) ) {
                    $out['custom'][ $key ] = $flag ? 1 : 0;
                }
            }
        }

        return $out;
    }

    private static function sanitize_choice( $value, array $allowed, string $fallback ) : string {
        $value = sanitize_key( (string) $value );
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    public static function field_presentation() : void {
        $opt          = (array) get_option( MODEP_Admin::OPT_UI, [] );
        $presentation = isset( $opt['presentation'] ) && is_array( $opt['presentation'] ) ? $opt['presentation'] : [];
        $style        = self::sanitize_choice( $presentation['filter_style'] ?? 'chips', [ 'chips', 'checkboxes', 'radios', 'toggles', 'hierarchical' ], 'chips' );
        $loader       = self::sanitize_choice( $presentation['loader_style'] ?? 'spinner', [ 'spinner', 'skeleton', 'dots', 'pulse', 'custom', 'none' ], 'spinner' );
        ?>
        <div class="modep-form-grid">
            <label>
                <strong><?php esc_html_e( 'Default filter style', 'modefilter-pro' ); ?></strong>
                <select class="modep-select" name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[presentation][filter_style]">
                    <option value="chips" <?php selected( $style, 'chips' ); ?>><?php esc_html_e( 'Modern Chips', 'modefilter-pro' ); ?></option>
                    <option value="checkboxes" <?php selected( $style, 'checkboxes' ); ?>><?php esc_html_e( 'Checkboxes', 'modefilter-pro' ); ?></option>
                    <option value="radios" <?php selected( $style, 'radios' ); ?>><?php esc_html_e( 'Radio Buttons', 'modefilter-pro' ); ?></option>
                    <option value="toggles" <?php selected( $style, 'toggles' ); ?>><?php esc_html_e( 'Toggle Switches', 'modefilter-pro' ); ?></option>
                    <option value="hierarchical" <?php selected( $style, 'hierarchical' ); ?>><?php esc_html_e( 'Hierarchical Category Tree', 'modefilter-pro' ); ?></option>
                </select>
            </label>
            <label class="modep-checkbox">
                <input type="checkbox" name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[presentation][category_hierarchy]" value="1" <?php checked( 'yes', $presentation['category_hierarchy'] ?? 'no' ); ?> />
                <span><?php esc_html_e( 'Preserve parent/child category hierarchy with any filter style', 'modefilter-pro' ); ?></span>
            </label>
            <label>
                <strong><?php esc_html_e( 'Default AJAX loader', 'modefilter-pro' ); ?></strong>
                <select class="modep-select" name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[presentation][loader_style]">
                    <option value="spinner" <?php selected( $loader, 'spinner' ); ?>><?php esc_html_e( 'Spinning Loader', 'modefilter-pro' ); ?></option>
                    <option value="skeleton" <?php selected( $loader, 'skeleton' ); ?>><?php esc_html_e( 'Product Skeletons', 'modefilter-pro' ); ?></option>
                    <option value="dots" <?php selected( $loader, 'dots' ); ?>><?php esc_html_e( 'Animated Dots', 'modefilter-pro' ); ?></option>
                    <option value="pulse" <?php selected( $loader, 'pulse' ); ?>><?php esc_html_e( 'Pulsing Ring', 'modefilter-pro' ); ?></option>
                    <option value="custom" <?php selected( $loader, 'custom' ); ?>><?php esc_html_e( 'Custom Image / GIF / SVG', 'modefilter-pro' ); ?></option>
                    <option value="none" <?php selected( $loader, 'none' ); ?>><?php esc_html_e( 'No Visual Loader', 'modefilter-pro' ); ?></option>
                </select>
            </label>
            <label>
                <strong><?php esc_html_e( 'Custom loader image URL', 'modefilter-pro' ); ?></strong>
                <span class="modep-row modep-gap">
                    <input id="modep_global_loader_image" type="url" class="regular-text" name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[presentation][loader_image]" value="<?php echo esc_attr( (string) ( $presentation['loader_image'] ?? '' ) ); ?>" placeholder="https://example.com/loader.gif" />
                    <button type="button" class="button modep-loader-media-select" data-target="modep_global_loader_image"><?php esc_html_e( 'Choose Media', 'modefilter-pro' ); ?></button>
                    <button type="button" class="button-link-delete modep-loader-media-clear" data-target="modep_global_loader_image"><?php esc_html_e( 'Clear', 'modefilter-pro' ); ?></button>
                </span>
                <span class="description"><?php esc_html_e( 'Used when Custom Image is selected. GIF, SVG, WebP and PNG URLs are supported.', 'modefilter-pro' ); ?></span>
            </label>
        </div>
        <?php
    }

    public static function field_core() : void {
        $opt = get_option( MODEP_Admin::OPT_UI, [] );
        $c   = $opt['core'] ?? [];
        $box = function( $key, $label ) use ( $c ) {
            printf(
                // stf-checkbox -> modep-checkbox
                '<label class="modep-checkbox"><input type="checkbox" name="%1$s[core][%2$s]" value="1" %3$s/> <span>%4$s</span></label>',
                esc_attr( MODEP_Admin::OPT_UI ),
                esc_attr( $key ),
                checked( ! empty( $c[ $key ] ), true, false ),
                esc_html( $label )
            );
        };
        // stf-row stf-row-wrap stf-gap -> modep-row modep-row-wrap modep-gap
        echo '<div class="modep-row modep-row-wrap modep-gap">';
        $box( 'category', __( 'Categories', 'modefilter-pro' ) );
        $box( 'tag',      __( 'Tags', 'modefilter-pro' ) );
        if ( taxonomy_exists( 'product_brand' ) ) $box( 'brand', __( 'Brands', 'modefilter-pro' ) );
        $box( 'price',    __( 'Price', 'modefilter-pro' ) );
        $box( 'stock',    __( 'Stock Status', 'modefilter-pro' ) );
        echo '</div>';
    }

    public static function field_attrs() : void {
        $opt   = get_option( MODEP_Admin::OPT_UI, [] );
        $attrs = MODEP_Attributes::get_registered_attribute_taxonomies();
        if ( empty( $attrs ) ) {
            echo '<p>'. esc_html__( 'No product attributes found.', 'modefilter-pro' ) .'</p>';
            return;
        }
        // stf-attrs-grid -> modep-attrs-grid
        echo '<div class="modep-attrs-grid">';
        foreach ( $attrs as $tx => $label ) {
            $checked = ! empty( $opt['attrs'][ $tx ] );
            printf(
                // stf-checkbox -> modep-checkbox
                '<label class="modep-checkbox"><input type="checkbox" name="%1$s[attrs][%2$s]" value="1" %3$s/> <span>%4$s</span> <code>%5$s</code></label>',
                esc_attr( MODEP_Admin::OPT_UI ),
                esc_attr( $tx ),
                checked( $checked, true, false ),
                esc_html( $label ),
                esc_html( $tx )
            );
        }
        echo '</div>';
    }

    /**
     * Helper method to get custom filters registered via hook.
     * @return array Array of custom filters (key => label).
     */
    private static function get_custom_filters(): array {
        /**
         * Filter to register custom filter blocks (beyond core and attributes).
         *
         * Key should be a unique ID (e.g., taxonomy name or internal ID), value is display label.
         * The key must be a valid key that can be used in a checkbox input name.
         *
         * @param array $filters Array of custom filters.
         * @since 1.0.0
         */
        return (array) apply_filters( 'modep_custom_filters', [] );
    }

    public static function field_custom() : void {
        $opt = get_option( MODEP_Admin::OPT_UI, [] );
        $c   = $opt['custom'] ?? [];
        $custom_filters = self::get_custom_filters();

        if ( empty( $custom_filters ) ) {
            echo '<p>'. esc_html__( 'No custom filter blocks have been registered by other plugins.', 'modefilter-pro' ) .'</p>';
            return;
        }
        
        // modep-row modep-row-wrap modep-gap for rendering the checkboxes
        echo '<div class="modep-row modep-row-wrap modep-gap">';
        
        foreach ( $custom_filters as $key => $label ) {
            printf(
                '<label class="modep-checkbox"><input type="checkbox" name="%1$s[custom][%2$s]" value="1" %3$s/> <span>%4$s</span> <code>%5$s</code></label>',
                esc_attr( MODEP_Admin::OPT_UI ),
                esc_attr( $key ),
                checked( ! empty( $c[ $key ] ), true, false ),
                esc_html( $label ),
                esc_html( $key )
            );
        }
        echo '</div>';
    }

    public static function render() : void {
        MODEP_Admin_UI::page_open( __( 'Filters', 'modefilter-pro' ), __( 'Configure which UI filters are available globally. You can still toggle them per instance.', 'modefilter-pro' ) );
        MODEP_Admin_UI::tabs( 'filters' );

        // stf-form -> modep-form
        echo '<form method="post" action="options.php" class="modep-form">';

		// Settings API nonce for the whole form.
		settings_fields( 'modep_ui' );

		// Section: Core filters.
		MODEP_Admin_UI::section_card_open(
			__( 'Core Filters', 'modefilter-pro' ),
			__( 'Show/hide the basic taxonomy & price/stock filters available to widgets/shortcodes.', 'modefilter-pro' )
		);
		self::field_core();
		MODEP_Admin_UI::section_card_close();

		MODEP_Admin_UI::section_card_open(
			__( 'Filter Presentation & AJAX Loader', 'modefilter-pro' ),
			__( 'Set defaults used by shortcodes and Elementor widgets when they are configured to inherit global settings.', 'modefilter-pro' )
		);
		self::field_presentation();
		MODEP_Admin_UI::section_card_close();

		// Section: Attribute filters.
		MODEP_Admin_UI::section_card_open(
			__( 'Attribute Filters', 'modefilter-pro' ),
			__( 'Enable any registered product attributes as filters (e.g., size, color, material).', 'modefilter-pro' )
		);
		self::field_attrs();
		MODEP_Admin_UI::section_card_close();

		// Section: Custom filters.
		MODEP_Admin_UI::section_card_open(
			__( 'Custom Filters', 'modefilter-pro' ),
			__( 'Filter blocks registered by other plugins/themes (e.g., custom taxonomies or dedicated filter types).', 'modefilter-pro' )
		);
		self::field_custom();
		MODEP_Admin_UI::section_card_close();

		// Section: Filter Customization (NEW in v1.0.7)
		MODEP_Admin_UI::section_card_open(
			__( 'Advanced: Filter Chip Customization', 'modefilter-pro' ),
			__( 'Customize colors, spacing, and borders of filter chips. Leave empty to use default theme colors.', 'modefilter-pro' )
		);
		ob_start();
		MODEP_Admin_Filter_Customize::field_colors();
		MODEP_Admin_Filter_Customize::field_spacing();
		MODEP_Admin_Filter_Customize::field_borders();
		$customize_html = ob_get_clean();
		echo wp_kses_post( $customize_html );
		MODEP_Admin_UI::section_card_close();

        submit_button( __( 'Save Settings', 'modefilter-pro' ) );
        echo '</form>';

        MODEP_Admin_UI::page_close();
    }
}
