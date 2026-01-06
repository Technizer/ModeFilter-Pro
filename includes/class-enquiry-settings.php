<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Enquiry_Settings {

    const OPTION_KEY = 'modep_enquiry_settings';

    public static function init() : void {

        // IMPORTANT:
        // Do NOT register submenu here, because it breaks the ordering (it gets added late).
        // The submenu is registered centrally in MODEP_Admin::menu().

        if ( is_admin() ) {
            add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        }

        // Frontend assets + modal.
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal_container' ] );
    }

    /**
     * Get stored settings merged with defaults.
     */
    public static function get_settings() : array {
        $defaults = [
            'action'             => 'popup_builtin', // popup_builtin|popup_elementor|redirect_page|redirect_url.
            'form_shortcode'     => '',
            'elementor_popup_id' => '',
            'redirect_page_id'   => '',
            'redirect_url'       => '',
        ];

        $saved = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );
    }

    /* ---------------- Admin: Settings ---------------- */

    public static function register_settings() : void {
        register_setting(
            'modep_enquiry_group',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
                'default'           => [],
            ]
        );

        add_settings_section(
            'modep_enquiry_main',
            __( 'Enquiry Behaviour', 'modefilter-pro' ),
            function () {
                printf(
                    '<p>%s</p>',
                    esc_html__( 'Control how the Enquire button behaves for catalog products.', 'modefilter-pro' )
                );
            },
            'modep_enquiry_page'
        );

        add_settings_field(
            'modep_enquiry_action',
            __( 'Click Action', 'modefilter-pro' ),
            [ __CLASS__, 'field_action' ],
            'modep_enquiry_page',
            'modep_enquiry_main'
        );

        add_settings_field(
            'modep_enquiry_form_shortcode',
            __( 'Form Shortcode', 'modefilter-pro' ),
            [ __CLASS__, 'field_form_shortcode' ],
            'modep_enquiry_page',
            'modep_enquiry_main'
        );

        add_settings_field(
            'modep_enquiry_elementor_popup',
            __( 'Elementor Popup', 'modefilter-pro' ),
            [ __CLASS__, 'field_elementor_popup' ],
            'modep_enquiry_page',
            'modep_enquiry_main'
        );

        add_settings_field(
            'modep_enquiry_redirect',
            __( 'Redirect Target', 'modefilter-pro' ),
            [ __CLASS__, 'field_redirect' ],
            'modep_enquiry_page',
            'modep_enquiry_main'
        );
    }

    /**
     * Sanitize settings array before saving.
     *
     * @param array|string $input Raw settings input.
     * @return array
     */
    public static function sanitize_settings( $input ) : array {
        $out = self::get_settings();

        if ( ! is_array( $input ) ) {
            $input = [];
        }

        $out['action']             = isset( $input['action'] ) ? sanitize_text_field( wp_unslash( $input['action'] ) ) : $out['action'];
        $out['form_shortcode']     = isset( $input['form_shortcode'] ) ? wp_kses_post( wp_unslash( $input['form_shortcode'] ) ) : '';
        $out['elementor_popup_id'] = isset( $input['elementor_popup_id'] ) ? absint( $input['elementor_popup_id'] ) : 0;
        $out['redirect_page_id']   = isset( $input['redirect_page_id'] ) ? absint( $input['redirect_page_id'] ) : 0;
        $out['redirect_url']       = isset( $input['redirect_url'] ) ? esc_url_raw( wp_unslash( $input['redirect_url'] ) ) : '';

        return $out;
    }

    /* ---------------- Admin Page ---------------- */

    public static function render_page() : void {

        // Use the shared admin UI shell + tabs.
        if ( class_exists( 'MODEP_Admin_UI' ) ) {
            MODEP_Admin_UI::page_open(
                __( 'Enquiry & Popup', 'modefilter-pro' ),
                __( 'Control how enquiry buttons behave for catalog products: popup, Elementor popup, or redirect.', 'modefilter-pro' )
            );
            MODEP_Admin_UI::tabs( 'enquiry' );

            echo '<form method="post" action="options.php" class="modep-form">';
            settings_fields( 'modep_enquiry_group' );

            MODEP_Admin_UI::section_card_open(
                __( 'Enquiry Behaviour', 'modefilter-pro' ),
                __( 'Choose what happens when visitors click the enquiry button on catalog products.', 'modefilter-pro' )
            );

            // Use WordPress Settings API fields (already sanitized on save).
            do_settings_sections( 'modep_enquiry_page' );

            MODEP_Admin_UI::section_card_close();

            submit_button( __( 'Save Settings', 'modefilter-pro' ) );
            echo '</form>';

            MODEP_Admin_UI::page_close();
            return;
        }

        // Fallback if MODEP_Admin_UI isn't loaded for any reason.
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Enquiry & Popup Settings', 'modefilter-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'modep_enquiry_group' );
                do_settings_sections( 'modep_enquiry_page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function field_action() : void {
        $s = self::get_settings();
        ?>
        <select class="modep-select" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[action]">
            <option value="popup_builtin" <?php selected( $s['action'], 'popup_builtin' ); ?>>
                <?php esc_html_e( 'Popup (Plugin Built-in)', 'modefilter-pro' ); ?>
            </option>
            <option value="popup_elementor" <?php selected( $s['action'], 'popup_elementor' ); ?>>
                <?php esc_html_e( 'Popup (Elementor Popup)', 'modefilter-pro' ); ?>
            </option>
            <option value="redirect_page" <?php selected( $s['action'], 'redirect_page' ); ?>>
                <?php esc_html_e( 'Redirect to Page', 'modefilter-pro' ); ?>
            </option>
            <option value="redirect_url" <?php selected( $s['action'], 'redirect_url' ); ?>>
                <?php esc_html_e( 'Redirect to Custom URL', 'modefilter-pro' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Choose how the Enquire button should behave for catalog products.', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    public static function field_form_shortcode() : void {
        $s = self::get_settings();
        ?>
        <textarea
            name="<?php echo esc_attr( self::OPTION_KEY ); ?>[form_shortcode]"
            rows="4"
            class="large-text"
            placeholder="[contact-form-7 id=&quot;123&quot;] or [wpforms id=&quot;456&quot;]"
        ><?php echo esc_textarea( $s['form_shortcode'] ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Paste any form shortcode (Contact Form 7, WPForms, Gravity Forms, Fluent Forms, etc.). Used in the built-in popup.', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    public static function field_elementor_popup() : void {
        $s      = self::get_settings();
        $popups = self::get_elementor_popups();

        if ( empty( $popups ) ) {
            printf(
                '<p>%s</p>',
                esc_html__( 'No Elementor Popups detected. Make sure Elementor Pro popup templates exist.', 'modefilter-pro' )
            );
            return;
        }
        ?>
        <select class="modep-select" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[elementor_popup_id]">
            <option value="0"><?php esc_html_e( '— Select a popup —', 'modefilter-pro' ); ?></option>
            <?php foreach ( $popups as $popup ) : ?>
                <option value="<?php echo esc_attr( (string) $popup->ID ); ?>" <?php selected( (int) $s['elementor_popup_id'], (int) $popup->ID ); ?>>
                    <?php
                    printf(
                        '%1$s (ID: %2$d)',
                        esc_html( $popup->post_title ),
                        (int) $popup->ID
                    );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Used when action is set to "Popup (Elementor Popup)".', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    public static function field_redirect() : void {
        $s     = self::get_settings();
        $pages = get_pages();
        ?>
        <div class="modep-row modep-gap" style="align-items:flex-start;">
            <div>
                <label>
                    <?php esc_html_e( 'Redirect Page', 'modefilter-pro' ); ?><br/>
                    <select class="modep-select" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_page_id]">
                        <option value="0"><?php esc_html_e( '— None —', 'modefilter-pro' ); ?></option>
                        <?php if ( ! empty( $pages ) ) : ?>
                            <?php foreach ( $pages as $page ) : ?>
                                <option
                                    value="<?php echo esc_attr( (string) $page->ID ); ?>"
                                    <?php selected( (int) $s['redirect_page_id'], (int) $page->ID ); ?>
                                >
                                    <?php echo esc_html( $page->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>
            </div>

            <div>
                <label>
                    <?php esc_html_e( 'Redirect Custom URL', 'modefilter-pro' ); ?><br/>
                    <input
                        type="url"
                        name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_url]"
                        value="<?php echo esc_attr( $s['redirect_url'] ); ?>"
                        class="regular-text"
                        placeholder="https://yourdomain.com/enquiry"
                    />
                </label>
            </div>
        </div>

        <p class="description">
            <?php esc_html_e( 'When using redirect actions, the plugin will prefer Page URL if selected, otherwise the custom URL.', 'modefilter-pro' ); ?>
        </p>
        <?php
    }

    /* ---------------- Detect Elementor Popups ---------------- */

    /**
     * Get Elementor popup templates (if Elementor Pro is active).
     *
     * @return WP_Post[]
     */
    protected static function get_elementor_popups() : array {
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return [];
        }

        $args = [
            'post_type'              => 'elementor_library',
            'posts_per_page'         => 50,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Small, bounded admin-only lookup for Elementor popup templates.
                [
                    'taxonomy' => 'elementor_library_type',
                    'field'    => 'slug',
                    'terms'    => [ 'popup' ],
                ],
            ],
        ];

        $posts = get_posts( $args );

        return is_array( $posts ) ? $posts : [];
    }

    /* ---------------- Frontend: assets + modal ---------------- */

    public static function enqueue_frontend_assets() : void {
        $handle = 'modep-enquiry';

        wp_register_script(
            $handle,
            plugins_url( 'assets/js/modep-enquiry.js', MODEP_PLUGIN_FILE ),
            [ 'jquery' ],
            MODEP_VERSION,
            true
        );

        $s            = self::get_settings();
        $redirect_url = '';

        if ( ! empty( $s['redirect_page_id'] ) ) {
            $redirect_url = (string) get_permalink( (int) $s['redirect_page_id'] );
        } elseif ( ! empty( $s['redirect_url'] ) ) {
            $redirect_url = $s['redirect_url'];
        }

        wp_localize_script(
            $handle,
            'MODEP_Enquiry',
            [
                'action'           => $s['action'],
                'redirectUrl'      => $redirect_url,
                'hasShortcodeForm' => ! empty( $s['form_shortcode'] ),
                'elementorPopupId' => (int) $s['elementor_popup_id'],
            ]
        );

        wp_enqueue_script( $handle );
    }

    public static function render_modal_container() : void {
        $s = self::get_settings();
        if ( empty( $s['form_shortcode'] ) ) {
            return;
        }
        ?>
        <div id="modep-enquiry-modal" class="modep-enquiry-modal" style="display:none;" aria-hidden="true">
            <div class="modep-enquiry-backdrop"></div>
            <div class="modep-enquiry-dialog" role="dialog" aria-modal="true">
                <button type="button" class="modep-enquiry-close" aria-label="<?php esc_attr_e( 'Close', 'modefilter-pro' ); ?>">&times;</button>
                <div class="modep-enquiry-content">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Form plugin output is expected HTML.
                    echo do_shortcode( $s['form_shortcode'] );
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}
