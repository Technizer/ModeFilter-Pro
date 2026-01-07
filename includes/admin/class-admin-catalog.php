<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Admin_Catalog {

    /**
     * Register Catalog + PayPal settings via WordPress Settings API.
     */
    public static function register_settings() : void {

        // Main Catalog array: Stores global mode, price visibility, and button replacement settings.
        register_setting(
            'modep_catalog',
            MODEP_Catalog_Mode::OPT,
            [
                'type'              => 'array',
                'sanitize_callback' => [ __CLASS__, 'sanitize_catalog' ],
                'default'           => [],
            ]
        );

        // PayPal Promo Toggle: Simple boolean to show/hide the "Pay Later" block.
        register_setting(
            'modep_catalog',
            'modep_paypal_promo_enabled',
            [
                'type'              => 'boolean',
                'sanitize_callback' => [ __CLASS__, 'sanitize_checkbox' ],
                'default'           => 0,
            ]
        );

        // PayPal Min Amount: Ensures promos only show for higher-value items.
        register_setting(
            'modep_catalog',
            'modep_paypal_min_amount',
            [
                'type'              => 'string',
                'sanitize_callback' => [ __CLASS__, 'sanitize_price' ],
                'default'           => '30',
            ]
        );

        // Registry of fields for the Settings API (manual rendering is used in ::render).
        add_settings_section( 'modep_cat_main', '', '__return_false', 'modep_catalog' );
        add_settings_field( 'modep_cat_global', '', [ __CLASS__, 'field_cat_global' ], 'modep_catalog', 'modep_cat_main' );
        add_settings_field( 'modep_cat_price',  '', [ __CLASS__, 'field_cat_price'  ], 'modep_catalog', 'modep_cat_main' );
        add_settings_field( 'modep_cat_replace', '', [ __CLASS__, 'field_cat_replace' ], 'modep_catalog', 'modep_cat_main' );
        add_settings_field( 'modep_cat_terms', '', [ __CLASS__, 'field_term_defaults' ], 'modep_catalog', 'modep_cat_main' );
    }

    /**
     * Sanitizes the main catalog array and prepares term defaults.
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

        // Re-initialize Term defaults for categories, tags, and brands.
        $out['term_defaults'] = [
            'catalog' => [ 'product_cat' => [], 'product_tag' => [], 'product_brand' => [] ],
            'sell'    => [ 'product_cat' => [], 'product_tag' => [], 'product_brand' => [] ],
        ];

        $taxes = [ 'product_cat', 'product_tag' ];
        if ( taxonomy_exists( 'product_brand' ) ) {
            $taxes[] = 'product_brand';
        }

        foreach ( $taxes as $tax ) {
            $raw_catalog = isset( $input['term_defaults']['catalog'][ $tax ] ) ? (array) $input['term_defaults']['catalog'][ $tax ] : [];
            $raw_sell    = isset( $input['term_defaults']['sell'][ $tax ] ) ? (array) $input['term_defaults']['sell'][ $tax ] : [];

            // Cast IDs to absolute integers for security and consistency.
            $out['term_defaults']['catalog'][ $tax ] = array_values( array_filter( array_map( 'absint', $raw_catalog ) ) );
            $out['term_defaults']['sell'][ $tax ]    = array_values( array_filter( array_map( 'absint', $raw_sell ) ) );
        }

        // IMPORTANT: Sync meta to terms so the frontend query remains fast.
        self::sync_term_defaults_meta( $old['term_defaults'] ?? [], $out['term_defaults'] );

        return $out;
    }

    /**
     * Sync term defaults to database meta.
     * Logic: Compares old vs new IDs and adds/removes the TERM_DEFAULT_KEY accordingly.
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

            // Remove meta from terms no longer assigned a specific mode.
            $to_clear = array_diff( $old_all, $new_all );
            foreach ( $to_clear as $term_id ) {
                if ( $term_id > 0 ) {
                    delete_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY );
                }
            }

            // Apply 'catalog' mode meta to assigned terms.
            foreach ( $new_catalog as $term_id ) {
                if ( $term_id > 0 ) {
                    update_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY, 'catalog' );
                }
            }
            // Apply 'sell' mode meta to assigned terms.
            foreach ( $new_sell as $term_id ) {
                if ( $term_id > 0 ) {
                    update_term_meta( (int) $term_id, MODEP_Catalog_Mode::TERM_DEFAULT_KEY, 'sell' );
                }
            }
        }
    }

    public static function sanitize_checkbox( $value ) : int {
        return ! empty( $value ) ? 1 : 0;
    }

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
            <input type="radio" name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]" value="sell" <?php checked( $s['global_mode'], 'sell' ); ?> />
            <span><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></span>
        </label>
        <label class="modep-radio">
            <input type="radio" name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]" value="catalog" <?php checked( $s['global_mode'], 'catalog' ); ?> />
            <span><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></span>
        </label>
        <label class="modep-radio">
            <input type="radio" name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[global_mode]" value="hybrid" <?php checked( $s['global_mode'], 'hybrid' ); ?> />
            <span><?php esc_html_e( 'Hybrid', 'modefilter-pro' ); ?></span>
        </label>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e( 'Hybrid behaves like Sell by default, but follows the term rules and product overrides below.', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    public static function field_cat_price() : void {
        $s = MODEP_Catalog_Mode::get_settings();
        ?>
        <label class="modep-switch">
            <input type="checkbox" name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[hide_prices]" value="yes" <?php checked( $s['hide_prices'], 'yes' ); ?> />
            <span class="modep-switch-ui"></span>
            <span class="modep-switch-label"><?php esc_html_e( 'Hide price HTML when in Catalog mode', 'modefilter-pro' ); ?></span>
        </label>
        <?php
    }

    public static function field_cat_replace() : void {
        $s = MODEP_Catalog_Mode::get_settings();
        ?>
        <label class="modep-switch">
            <input type="checkbox" name="<?php echo esc_attr( MODEP_Catalog_Mode::OPT ); ?>[replace_button]" value="yes" <?php checked( $s['replace_button'], 'yes' ); ?> />
            <span class="modep-switch-ui"></span>
            <span class="modep-switch-label"><?php esc_html_e( 'Replace “Add to cart” button for Catalog products', 'modefilter-pro' ); ?></span>
        </label>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e( 'Button label/URL are configured per grid instance to allow unique CTAs.', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    /** @deprecated 1.0.0 */
    public static function field_cat_button() : void {
        self::field_cat_replace();
    }

    /**
     * Term defaults UI: Allows bulk assignment of categories to Sell or Catalog.
     */
    public static function field_term_defaults() : void {
        $s  = MODEP_Catalog_Mode::get_settings();
        $td = isset( $s['term_defaults'] ) && is_array( $s['term_defaults'] ) ? $s['term_defaults'] : [];

        $taxes = [ 'product_cat' => __( 'Categories', 'modefilter-pro' ), 'product_tag' => __( 'Tags', 'modefilter-pro' ) ];
        if ( taxonomy_exists( 'product_brand' ) ) { $taxes['product_brand'] = __( 'Brands', 'modefilter-pro' ); }

        echo '<p class="description">' . esc_html__( 'Products set to “Inherit” will follow these term defaults.', 'modefilter-pro' ) . '</p>';

        foreach ( $taxes as $tax => $label ) {
            $catalog_sel = isset( $td['catalog'][ $tax ] ) ? (array) $td['catalog'][ $tax ] : [];
            $sell_sel    = isset( $td['sell'][ $tax ] ) ? (array) $td['sell'][ $tax ] : [];

            echo '<div class="modep-columns modep-columns--2" style="margin-top:14px;">';
                echo '<div class="modep-columns__col">';
                    /* translators: %s: Taxonomy label (e.g. Categories) */
                    echo '<h4 style="margin:0 0 6px;">' . esc_html( sprintf( __( '%s: Catalog Default', 'modefilter-pro' ), $label ) ) . '</h4>';
                    self::render_terms_multiselect( $tax, MODEP_Catalog_Mode::OPT . '[term_defaults][catalog][' . $tax . '][]', $catalog_sel );
                echo '</div>';
                echo '<div class="modep-columns__col">';
                    /* translators: %s: Taxonomy label (e.g. Categories) */
                    echo '<h4 style="margin:0 0 6px;">' . esc_html( sprintf( __( '%s: Sell Default', 'modefilter-pro' ), $label ) ) . '</h4>';
                    self::render_terms_multiselect( $tax, MODEP_Catalog_Mode::OPT . '[term_defaults][sell][' . $tax . '][]', $sell_sel );
                echo '</div>';
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
        $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 200, 'orderby' => 'name', 'order' => 'ASC' ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            echo '<p>' . esc_html__( 'No terms found.', 'modefilter-pro' ) . '</p>';
            return;
        }

        printf( '<select class="modep-select" multiple="multiple" size="8" name="%s" style="width:100%%;">', esc_attr( $name ) );
        foreach ( $terms as $t ) {
            $tid = absint( $t->term_id );
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( (string) $tid ), selected( in_array( $tid, $selected, true ), true, false ), esc_html( $t->name ) );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Cmd/Ctrl + Click to select multiple.', 'modefilter-pro' ) . '</p>';
    }

    /** --- PayPal Promo UI --- */

    public static function field_paypal_promo() : void {
        $enabled = (int) get_option( 'modep_paypal_promo_enabled', 0 );
        $min     = (string) get_option( 'modep_paypal_min_amount', '30' );
        ?>
        <label class="modep-switch">
            <input type="checkbox" name="modep_paypal_promo_enabled" value="1" <?php checked( 1, $enabled ); ?> />
            <span class="modep-switch-ui"></span>
            <span class="modep-switch-label"><?php esc_html_e( 'Show PayPal promo on product cards', 'modefilter-pro' ); ?></span>
        </label>
        <div class="modep-row" style="margin-top:12px;">
            <label>
                <?php esc_html_e( 'Min price threshold', 'modefilter-pro' ); ?>
                <input type="number" name="modep_paypal_min_amount" value="<?php echo esc_attr( $min ); ?>" min="0" step="0.01" class="small-text" style="margin-left:10px;" />
            </label>
        </div>
        <?php
    }

    /** --- Main Page Rendering --- */

    public static function render() : void {
        MODEP_Admin_UI::page_open( __( 'Catalog / Sell Mode', 'modefilter-pro' ), __( 'Configure global store behavior and hybrid term rules.', 'modefilter-pro' ) );
        MODEP_Admin_UI::tabs( 'catalog' );

        echo '<form method="post" action="options.php" class="modep-form">';
        settings_fields( 'modep_catalog' );

        MODEP_Admin_UI::section_card_open( __( 'Store Mode', 'modefilter-pro' ), __( 'Choose how the store handles product availability.', 'modefilter-pro' ) );
        self::field_cat_global();
        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( __( 'Display Logic', 'modefilter-pro' ) );
        self::field_cat_price();
        echo '<div style="margin-top:15px;">';
        self::field_cat_replace();
        echo '</div>';
        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( __( 'Term Default Rules (Hybrid Mode)', 'modefilter-pro' ), __( 'Bulk-assign modes to categories and tags.', 'modefilter-pro' ) );
        self::field_term_defaults();
        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( __( 'PayPal Integration', 'modefilter-pro' ) );
        self::field_paypal_promo();
        MODEP_Admin_UI::section_card_close();

        submit_button( __( 'Save Catalog Configuration', 'modefilter-pro' ) );
        echo '</form>';

        MODEP_Admin_UI::page_close();
    }
}