<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the Catalog Mode logic for products and taxonomies.
 */
final class MODEP_Catalog_Mode {

    /**
     * Option key for global catalog settings.
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

    public static function get_settings() : array {
        $defaults = [
            'global_mode'    => 'sell',
            'hide_prices'    => 'yes',
            'replace_button' => 'yes',
            'button_label'   => __( 'Enquire', 'modefilter-pro' ),
            'button_url'     => '',
        ];

        $opt = get_option( self::OPT, [] );
        return wp_parse_args( $opt, $defaults );
    }

    public static function is_global_catalog_mode() : bool {
        $settings = self::get_settings();
        return ( isset( $settings['global_mode'] ) && 'catalog' === $settings['global_mode'] );
    }

    public static function get_effective_mode( WC_Product $product ) : string {
        $product_id = $product->get_id();

        // 1) Per-product meta override.
        $ov = get_post_meta( $product_id, '_modep_catalog_override', true );

        if ( in_array( $ov, [ 'sell', 'catalog' ], true ) ) {
            $mode = $ov;
        } else {
            // 2) Taxonomy term default.
            $mode  = '';
            $taxes = [ 'product_cat', 'product_tag', 'product_brand' ];

            foreach ( $taxes as $tx ) {
                if ( ! taxonomy_exists( $tx ) ) continue;

                $terms = get_the_terms( $product_id, $tx );
                if ( empty( $terms ) || is_wp_error( $terms ) ) continue;

                foreach ( $terms as $term ) {
                    $term_mode = get_term_meta( $term->term_id, '_modep_catalog_default', true );
                    if ( in_array( $term_mode, [ 'sell', 'catalog' ], true ) ) {
                        $mode = $term_mode;
                        break 2;
                    }
                }
            }

            // 3) Global fallback.
            if ( '' === $mode ) {
                $settings = self::get_settings();
                $mode     = $settings['global_mode'] ?? 'sell';
            }
        }

        return (string) apply_filters( 'modep_effective_catalog_mode', $mode, $product );
    }

    /** ---------- Frontend Filters ---------- */

    public static function filter_is_purchasable( bool $purchasable, WC_Product $product ) : bool {
        return ( 'catalog' === self::get_effective_mode( $product ) ) ? false : $purchasable;
    }

    public static function filter_price_html( string $price_html, WC_Product $product ) : string {
        if ( 'catalog' !== self::get_effective_mode( $product ) ) {
            return $price_html;
        }
        $settings = self::get_settings();
        return ( 'yes' === $settings['hide_prices'] ) ? '' : $price_html;
    }

    public static function filter_loop_add_to_cart( string $html, WC_Product $product ) : string {
        if ( 'catalog' !== self::get_effective_mode( $product ) ) {
            return $html;
        }

        if ( ! class_exists( 'MODEP_Enquiry_Settings' ) ) {
            return $html;
        }

        $settings = MODEP_Enquiry_Settings::get_settings();
        $label    = esc_html__( 'Enquire Now', 'modefilter-pro' );
        $href     = '#';

        if ( in_array( $settings['action'], [ 'redirect_page', 'redirect_url' ], true ) ) {
            if ( ! empty( $settings['redirect_page_id'] ) ) {
                $href = get_permalink( (int) $settings['redirect_page_id'] );
            } elseif ( ! empty( $settings['redirect_url'] ) ) {
                $href = $settings['redirect_url'];
            }
        }

        $href  = apply_filters( 'modep_catalog_enquiry_button_href', $href, $product, $settings );
        $label = apply_filters( 'modep_catalog_enquiry_button_label', $label, $product, $settings );

        return sprintf(
            '<a class="button modep-enquire" href="%1$s" data-modep-enquire="1" data-modep-product="%2$d">%3$s</a>',
            esc_url( $href ),
            (int) $product->get_id(),
            esc_html( $label )
        );
    }

    /** ---------- Product Meta Box ---------- */

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

    public static function render_meta_box( WP_Post $post ) : void {
        wp_nonce_field( 'modep_save_product_catalog', 'modep_catalog_mode_nonce' );
        $val = get_post_meta( $post->ID, '_modep_catalog_override', true );
        ?>
        <p>
            <label for="modep_catalog_override">
                <strong><?php esc_html_e( 'Mode override', 'modefilter-pro' ); ?></strong>
            </label>
        </p>
        <select id="modep_catalog_override" name="modep_catalog_override" class="widefat">
            <option value=""><?php esc_html_e( 'Inherit (taxonomy/global)', 'modefilter-pro' ); ?></option>
            <option value="sell" <?php selected( $val, 'sell' ); ?>><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></option>
            <option value="catalog" <?php selected( $val, 'catalog' ); ?>><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></option>
        </select>
        <?php
    }

    public static function save_product_meta( int $post_id ) : void {
        if ( ! isset( $_POST['modep_catalog_mode_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['modep_catalog_mode_nonce'] ) ), 'modep_save_product_catalog' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_product', $post_id ) ) return;

        $val = isset( $_POST['modep_catalog_override'] ) ? sanitize_key( wp_unslash( $_POST['modep_catalog_override'] ) ) : '';
        if ( in_array( $val, [ 'sell', 'catalog' ], true ) ) {
            update_post_meta( $post_id, '_modep_catalog_override', $val );
        } else {
            delete_post_meta( $post_id, '_modep_catalog_override' );
        }
    }

    /** ---------- Term Meta ---------- */

    public static function term_field() : void {
        wp_nonce_field( 'modep_save_term_catalog', 'modep_term_catalog_nonce' );
        ?>
        <div class="form-field">
            <label for="modep_catalog_default"><?php esc_html_e( 'ModeFilter Pro — Default Mode', 'modefilter-pro' ); ?></label>
            <select id="modep_catalog_default" name="modep_catalog_default">
                <option value=""><?php esc_html_e( 'Inherit (global)', 'modefilter-pro' ); ?></option>
                <option value="sell"><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></option>
                <option value="catalog"><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'Default mode for products in this term.', 'modefilter-pro' ); ?></p>
        </div>
        <?php
    }

    public static function term_field_edit( $term, string $taxonomy ) : void {
        $val = get_term_meta( $term->term_id, '_modep_catalog_default', true );
        wp_nonce_field( 'modep_save_term_catalog', 'modep_term_catalog_nonce' );
        ?>
        <tr class="form-field">
            <th scope="row"><label for="modep_catalog_default"><?php esc_html_e( 'ModeFilter Pro — Default Mode', 'modefilter-pro' ); ?></label></th>
            <td>
                <select id="modep_catalog_default" name="modep_catalog_default">
                    <option value=""><?php esc_html_e( 'Inherit (global)', 'modefilter-pro' ); ?></option>
                    <option value="sell" <?php selected( $val, 'sell' ); ?>><?php esc_html_e( 'Sell', 'modefilter-pro' ); ?></option>
                    <option value="catalog" <?php selected( $val, 'catalog' ); ?>><?php esc_html_e( 'Catalog', 'modefilter-pro' ); ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'Default mode for products in this term.', 'modefilter-pro' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public static function save_term_meta( int $term_id, int $tt_id ) : void {
        if ( ! isset( $_POST['modep_term_catalog_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['modep_term_catalog_nonce'] ) ), 'modep_save_term_catalog' ) ) {
            return;
        }
        
        // Security: Check if user can edit terms for this taxonomy
        $term = get_term( $term_id );
        if ( ! $term || ! current_user_can( get_taxonomy( $term->taxonomy )->cap->edit_terms ) ) {
            return;
        }

        $val = isset( $_POST['modep_catalog_default'] ) ? sanitize_key( wp_unslash( $_POST['modep_catalog_default'] ) ) : '';
        if ( in_array( $val, [ 'sell', 'catalog' ], true ) ) {
            update_term_meta( $term_id, '_modep_catalog_default', $val );
        } else {
            delete_term_meta( $term_id, '_modep_catalog_default' );
        }
    }
}