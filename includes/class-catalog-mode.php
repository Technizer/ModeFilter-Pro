<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Catalog_Mode {

    /**
     * Option key for global catalog settings (array).
     *
     * @var string
     */
    const OPT = 'modep_catalog_mode';

    /**
     * Bootstrap hooks.
     */
    public static function init() : void {
        // Frontend behaviour.
        add_filter( 'woocommerce_is_purchasable',        [ __CLASS__, 'filter_is_purchasable' ], 20, 2 );
        add_filter( 'woocommerce_get_price_html',        [ __CLASS__, 'filter_price_html' ], 20, 2 );
        add_filter( 'woocommerce_loop_add_to_cart_link', [ __CLASS__, 'filter_loop_add_to_cart' ], 20, 2 );

        // Product meta box.
        add_action( 'add_meta_boxes',    [ __CLASS__, 'register_meta_box' ] );
        add_action( 'save_post_product', [ __CLASS__, 'save_product_meta' ] );

        // Term meta for product_cat/tag/brand.
        foreach ( [ 'product_cat', 'product_tag', 'product_brand' ] as $tx ) {
            if ( taxonomy_exists( $tx ) ) {
                add_action( "{$tx}_add_form_fields",  [ __CLASS__, 'term_field' ] );
                add_action( "{$tx}_edit_form_fields", [ __CLASS__, 'term_field_edit' ], 10, 2 );
                add_action( "created_{$tx}",          [ __CLASS__, 'save_term_meta' ], 10, 2 );
                add_action( "edited_{$tx}",           [ __CLASS__, 'save_term_meta' ], 10, 2 );
            }
        }
    }

    /** ---------- Helpers ---------- */

    /**
     * Get global Catalog settings.
     *
     * @return array
     */
    public static function get_settings() : array {
		$defaults = [
			// Global store default.
			// - sell: normal store behavior unless items/terms are explicitly marked.
			// - catalog: everything inherits catalog unless explicitly overridden.
			// - hybrid: behaves like sell, but is exposed in the UI to encourage mixed catalogs.
			'global_mode'    => 'sell', // sell | catalog | hybrid.
            'hide_prices'    => 'yes',  // when catalog.
            'replace_button' => 'yes',
            'button_label'   => __( 'Enquire', 'modefilter-pro' ),
            'button_url'     => '', // fallback URL.
        ];

        $opt = get_option( self::OPT, [] );

        return wp_parse_args( $opt, $defaults );
    }

    /**
     * Check if the global mode is set to "catalog".
     *
     * This is your primary helper for "shop off / catalog-only" checks.
     *
     * @return bool
     */
    public static function is_global_catalog_mode() : bool {
        $settings = self::get_settings();
		return ( isset( $settings['global_mode'] ) && 'catalog' === $settings['global_mode'] );
    }

    /**
     * Alias for readability when you want to express "catalog-only".
     *
     * @return bool
     */
    public static function is_catalog_only() : bool {
        return self::is_global_catalog_mode();
    }

    /**
     * Resolve effective mode for a product: 'sell' | 'catalog'.
     *
     * Priority: per-product override > any term (cat/tag/brand) override > global.
     *
     * @param WC_Product $product Product object.
     * @return string             Mode string ('sell' or 'catalog').
     */
    public static function get_effective_mode( WC_Product $product ) : string {
        $product_id = $product->get_id();

        // 1) Per-product meta.
        $ov = get_post_meta( $product_id, '_modep_catalog_override', true ); // 'sell'|'catalog'|'' (inherit).

        if ( in_array( $ov, [ 'sell', 'catalog' ], true ) ) {
            $mode = $ov;
        } else {
            // 2) Any term default.
            $mode  = '';
            $taxes = [ 'product_cat', 'product_tag', 'product_brand' ];

            foreach ( $taxes as $tx ) {
                if ( ! taxonomy_exists( $tx ) ) {
                    continue;
                }

                $terms = get_the_terms( $product_id, $tx );
                if ( empty( $terms ) || is_wp_error( $terms ) ) {
                    continue;
                }

                foreach ( $terms as $term ) {
                    $term_mode = get_term_meta( $term->term_id, '_modep_catalog_default', true ); // 'sell'|'catalog'|''.
                    if ( in_array( $term_mode, [ 'sell', 'catalog' ], true ) ) {
                        $mode = $term_mode;
                        break 2; // Found a term mode, stop searching.
                    }
                }
            }

            // 3) Global fallback.
            if ( '' === $mode ) {
                $settings = self::get_settings();
                $mode     = $settings['global_mode'];
            }
        }

        /**
         * Filter the effective ModeFilter Pro catalog mode for a product.
         *
         * @param string     $mode    Resolved mode ('sell'|'catalog').
         * @param WC_Product $product Product object.
         */
        return apply_filters( 'modep_effective_catalog_mode', $mode, $product );
    }

    /** ---------- Frontend filters ---------- */

    /**
     * Make products non-purchasable when in catalog mode.
     *
     * @param bool       $purchasable Current purchasable state.
     * @param WC_Product $product     Product object.
     * @return bool
     */
    public static function filter_is_purchasable( bool $purchasable, WC_Product $product ) : bool {
        return ( 'catalog' === self::get_effective_mode( $product ) ) ? false : $purchasable;
    }

    /**
     * Optionally hide price HTML when in catalog mode.
     *
     * @param string     $price_html Existing price HTML.
     * @param WC_Product $product    Product object.
     * @return string
     */
    public static function filter_price_html( string $price_html, WC_Product $product ) : string {
        if ( 'catalog' !== self::get_effective_mode( $product ) ) {
            return $price_html;
        }

        $settings = self::get_settings();

        return ( 'yes' === $settings['hide_prices'] ) ? '' : $price_html;
    }

    /**
     * Replace loop "Add to cart" button with Enquire button when in catalog mode.
     *
     * Uses MODEP_Enquiry_Settings to decide what the button does.
     *
     * @param string     $html    Original button HTML.
     * @param WC_Product $product Product object.
     * @return string
     */
    public static function filter_loop_add_to_cart( string $html, WC_Product $product ) : string {
        if ( 'catalog' !== self::get_effective_mode( $product ) ) {
            return $html;
        }

        // If enquiry settings class doesn’t exist, don’t break – just keep original HTML.
        if ( ! class_exists( 'MODEP_Enquiry_Settings' ) ) {
            return $html;
        }

        $settings = MODEP_Enquiry_Settings::get_settings();

        // Default label (can be filtered if you like later).
        $label = esc_html__( 'Enquire Now', 'modefilter-pro' );

        // Build redirect URL if redirect action is active.
        $redirect_url = '';

        if ( in_array( $settings['action'], [ 'redirect_page', 'redirect_url' ], true ) ) {
            if ( ! empty( $settings['redirect_page_id'] ) ) {
                $redirect_url = get_permalink( (int) $settings['redirect_page_id'] );
            } elseif ( ! empty( $settings['redirect_url'] ) ) {
                $redirect_url = esc_url_raw( $settings['redirect_url'] );
            }
        }

        $href = $redirect_url ? $redirect_url : '#';

        /**
         * Filter the enquiry button URL in catalog mode.
         *
         * @param string     $href    Button href.
         * @param WC_Product $product Product object.
         * @param array      $settings Enquiry settings.
         */
        $href = apply_filters( 'modep_catalog_enquiry_button_href', $href, $product, $settings );

        /**
         * Filter the enquiry button label in catalog mode.
         *
         * @param string     $label   Button label.
         * @param WC_Product $product Product object.
         * @param array      $settings Enquiry settings.
         */
        $label = apply_filters( 'modep_catalog_enquiry_button_label', $label, $product, $settings );

        return sprintf(
            '<a class="button modep-enquire" href="%1$s" data-modep-enquire="1" data-modep-product="%2$d">%3$s</a>',
            esc_url( $href ),
            (int) $product->get_id(),
            esc_html( $label )
        );
    }

    /** ---------- Product meta box ---------- */

    /**
     * Register the product meta box for per-product override.
     */
    public static function register_meta_box() : void {
        add_meta_box(
            'modep_catalog_mode',
            __( 'ModeFilter Pro — Catalog/Sell Mode', 'modefilter-pro' ),
            [ __CLASS__, 'render_meta_box' ],
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render the product meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_meta_box( WP_Post $post ) : void {
        wp_nonce_field( 'modep_catalog_mode', 'modep_catalog_mode_nonce' );

        $val = get_post_meta( $post->ID, '_modep_catalog_override', true );
        ?>
        <p>
            <label for="modep_catalog_override">
                <strong><?php esc_html_e( 'Mode override', 'modefilter-pro' ); ?></strong>
            </label>
        </p>
        <select id="modep_catalog_override" name="modep_catalog_override" class="widefat">
            <option value=""><?php esc_html_e( 'Inherit (taxonomy/global)', 'modefilter-pro' ); ?></option>
            <option value="sell" <?php selected( $val, 'sell' ); ?>>
                <?php esc_html_e( 'Sell', 'modefilter-pro' ); ?>
            </option>
            <option value="catalog" <?php selected( $val, 'catalog' ); ?>>
                <?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Save per-product catalog override meta.
     *
     * @param int $post_id Product ID.
     */
    public static function save_product_meta( int $post_id ) : void {
        if ( ! isset( $_POST['modep_catalog_mode_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( (string) $_POST['modep_catalog_mode_nonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'modep_catalog_mode' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $val = isset( $_POST['modep_catalog_override'] )
            ? sanitize_text_field( wp_unslash( (string) $_POST['modep_catalog_override'] ) )
            : '';

        if ( ! in_array( $val, [ '', 'sell', 'catalog' ], true ) ) {
            $val = '';
        }

        update_post_meta( $post_id, '_modep_catalog_override', $val );
    }

    /** ---------- Term meta (taxonomy defaults) ---------- */

    /**
     * Add-term screen field.
     */
    public static function term_field() : void {
        // Nonce for add-term form.
        wp_nonce_field( 'modep_save_term_default', 'modep_term_default_nonce' );
        ?>
        <div class="form-field">
            <label for="modep_catalog_default">
                <?php esc_html_e( 'ModeFilter Pro — Default Mode', 'modefilter-pro' ); ?>
            </label>
            <select id="modep_catalog_default" name="modep_catalog_default">
                <option value=""><?php esc_html_e( 'Inherit (global)', 'modefilter-pro' ); ?></option>
                <option value="sell"><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></option>
                <option value="catalog"><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></option>
            </select>
            <p class="description">
                <?php esc_html_e( 'Default mode for products in this term.', 'modefilter-pro' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Edit-term screen field.
     *
     * @param WP_Term $term     Current term.
     * @param string  $taxonomy Taxonomy slug.
     */
    public static function term_field_edit( $term, string $taxonomy ) : void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $val = get_term_meta( $term->term_id, '_modep_catalog_default', true );
        // Nonce for edit-term form.
        wp_nonce_field( 'modep_save_term_default', 'modep_term_default_nonce' );
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="modep_catalog_default">
                    <?php esc_html_e( 'ModeFilter Pro — Default Mode', 'modefilter-pro' ); ?>
                </label>
            </th>
            <td>
                <select id="modep_catalog_default" name="modep_catalog_default">
                    <option value=""><?php esc_html_e( 'Inherit (global)', 'modefilter-pro' ); ?></option>
                    <option value="sell" <?php selected( $val, 'sell' ); ?>>
                        <?php esc_html_e( 'Sell', 'modefilter-pro' ); ?>
                    </option>
                    <option value="catalog" <?php selected( $val, 'catalog' ); ?>>
                        <?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?>
                    </option>
                </select>
                <p class="description">
                    <?php esc_html_e( 'Default mode for products in this term.', 'modefilter-pro' ); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save term default mode meta.
     *
     * @param int $term_id Term ID.
     * @param int $tt_id   Term taxonomy ID (unused).
     */
    public static function save_term_meta( int $term_id, int $tt_id ) : void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $nonce = isset( $_POST['modep_term_default_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['modep_term_default_nonce'] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'modep_save_term_default' ) ) {
            return;
        }

        if ( ! isset( $_POST['modep_catalog_default'] ) ) {
            return;
        }

        $val = sanitize_text_field( wp_unslash( $_POST['modep_catalog_default'] ) );

        if ( ! in_array( $val, [ '', 'sell', 'catalog' ], true ) ) {
            $val = '';
        }

        update_term_meta( $term_id, '_modep_catalog_default', $val );
    }
}