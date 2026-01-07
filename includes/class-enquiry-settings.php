<?php
declare(strict_types=1);

/**
 * ModeFilter Pro — Enquiry & Popup Settings
 * File: includes/class-enquiry-settings.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles Enquiry settings, redirects, and built-in popup logic.
 */
final class MODEP_Enquiry_Settings {

    const OPTION_KEY = 'modep_enquiry_settings';

    /**
     * Bootstrap hooks.
     */
    public static function init() : void {
        if ( is_admin() ) {
            add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        }

        // Frontend assets + modal container.
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal_container' ] );
    }

    /**
     * Get stored settings merged with defaults.
     */
    public static function get_settings() : array {
        $defaults = [
            'action'             => 'popup_builtin', // popup_builtin|popup_elementor|redirect_page|redirect_url
            'form_shortcode'     => '',
            'elementor_popup_id' => 0,
            'redirect_page_id'   => 0,
            'redirect_url'       => '',
        ];

        $saved = get_option( self::OPTION_KEY, [] );
        return wp_parse_args( is_array( $saved ) ? $saved : [], $defaults );
    }

    /* ---------------- Admin: Settings Registration ---------------- */

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

        // Settings sections are defined here but we render them manually in render_page() 
        // to maintain the MODEP Pro Card-based UI.
        add_settings_section(
            'modep_enquiry_main',
            __( 'Enquiry Behaviour', 'modefilter-pro' ),
            '__return_empty_string',
            'modep_enquiry_page'
        );

        add_settings_field( 'modep_enquiry_action', __( 'Click Action', 'modefilter-pro' ), [ __CLASS__, 'field_action' ], 'modep_enquiry_page', 'modep_enquiry_main' );
        add_settings_field( 'modep_enquiry_form_shortcode', __( 'Form Shortcode', 'modefilter-pro' ), [ __CLASS__, 'field_form_shortcode' ], 'modep_enquiry_page', 'modep_enquiry_main' );
        add_settings_field( 'modep_enquiry_elementor_popup', __( 'Elementor Popup', 'modefilter-pro' ), [ __CLASS__, 'field_elementor_popup' ], 'modep_enquiry_page', 'modep_enquiry_main' );
        add_settings_field( 'modep_enquiry_redirect', __( 'Redirect Target', 'modefilter-pro' ), [ __CLASS__, 'field_redirect' ], 'modep_enquiry_page', 'modep_enquiry_main' );
    }

    /**
     * Sanitize settings before saving.
     */
    public static function sanitize_settings( $input ) : array {
        $out = self::get_settings();

        if ( ! is_array( $input ) ) {
            return $out;
        }

        $allowed_actions = [ 'popup_builtin', 'popup_elementor', 'redirect_page', 'redirect_url' ];
        if ( isset( $input['action'] ) && in_array( $input['action'], $allowed_actions, true ) ) {
            $out['action'] = $input['action'];
        }

        $out['form_shortcode']     = isset( $input['form_shortcode'] ) ? wp_kses_post( wp_unslash( $input['form_shortcode'] ) ) : '';
        $out['elementor_popup_id'] = isset( $input['elementor_popup_id'] ) ? absint( $input['elementor_popup_id'] ) : 0;
        $out['redirect_page_id']   = isset( $input['redirect_page_id'] ) ? absint( $input['redirect_page_id'] ) : 0;
        $out['redirect_url']       = isset( $input['redirect_url'] ) ? esc_url_raw( wp_unslash( $input['redirect_url'] ) ) : '';

        return $out;
    }

    /* ---------------- Admin Fields ---------------- */

    public static function field_action() : void {
        $s = self::get_settings();
        ?>
        <select class="modep-select" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[action]" style="min-width:280px;">
            <option value="popup_builtin" <?php selected( $s['action'], 'popup_builtin' ); ?>><?php esc_html_e( 'Popup (Plugin Built-in)', 'modefilter-pro' ); ?></option>
            <option value="popup_elementor" <?php selected( $s['action'], 'popup_elementor' ); ?>><?php esc_html_e( 'Popup (Elementor Popup)', 'modefilter-pro' ); ?></option>
            <option value="redirect_page" <?php selected( $s['action'], 'redirect_page' ); ?>><?php esc_html_e( 'Redirect to Page', 'modefilter-pro' ); ?></option>
            <option value="redirect_url" <?php selected( $s['action'], 'redirect_url' ); ?>><?php esc_html_e( 'Redirect to Custom URL', 'modefilter-pro' ); ?></option>
        </select>
        <?php
    }

    public static function field_form_shortcode() : void {
        $s = self::get_settings();
        ?>
        <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[form_shortcode]" rows="3" class="large-text" style="max-width:500px;" placeholder='[contact-form-7 id="123"]'><?php echo esc_textarea( $s['form_shortcode'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Used when "Plugin Built-in" popup is selected.', 'modefilter-pro' ); ?></p>
        <?php
    }

    public static function field_elementor_popup() : void {
        $s      = self::get_settings();
        $popups = self::get_elementor_popups();

        if ( empty( $popups ) ) {
            echo '<p class="description" style="color:#d63638;">' . esc_html__( 'No Elementor Popups found in your library.', 'modefilter-pro' ) . '</p>';
            return;
        }
        ?>
        <select class="modep-select" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[elementor_popup_id]" style="min-width:280px;">
            <option value="0"><?php esc_html_e( '— Select a popup —', 'modefilter-pro' ); ?></option>
            <?php foreach ( $popups as $popup ) : ?>
                <option value="<?php echo absint( $popup->ID ); ?>" <?php selected( (int) $s['elementor_popup_id'], (int) $popup->ID ); ?>>
                    <?php echo esc_html( $popup->post_title ) . ' (ID: ' . absint( $popup->ID ) . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public static function field_redirect() : void {
        $s     = self::get_settings();
        $pages = get_pages( [ 'number' => 100 ] );
        ?>
        <div class="modep-row modep-gap" style="align-items: flex-start;">
            <div style="flex:1;">
                <label><strong><?php esc_html_e( 'Internal Page', 'modefilter-pro' ); ?></strong></label><br/>
                <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_page_id]" class="modep-select" style="width:100%; margin-top:5px;">
                    <option value="0"><?php esc_html_e( '— Select Page —', 'modefilter-pro' ); ?></option>
                    <?php foreach ( $pages as $page ) : ?>
                        <option value="<?php echo absint( $page->ID ); ?>" <?php selected( (int) $s['redirect_page_id'], (int) $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label><strong><?php esc_html_e( 'Custom URL', 'modefilter-pro' ); ?></strong></label><br/>
                <input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_url]" value="<?php echo esc_attr( $s['redirect_url'] ); ?>" class="regular-text" style="width:100%; margin-top:5px;" placeholder="https://example.com/contact" />
            </div>
        </div>
        <?php
    }

    /**
     * Fetch available Elementor popups from the library.
     * * @return array List of popup post objects.
     */
    protected static function get_elementor_popups() : array {
        if ( ! taxonomy_exists( 'elementor_library_type' ) ) {
            return [];
        }
        
        return get_posts( [
            'post_type'      => 'elementor_library',
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query'      => [
                [
                    'taxonomy' => 'elementor_library_type',
                    'field'    => 'slug',
                    'terms'    => 'popup',
                ],
            ],
        ] );
    }

    /**
     * RENDER PAGE
     * This method resolves the "Argument #1 ($callback) must be a valid callback" Fatal Error.
     */
    public static function render_page() : void {
        MODEP_Admin_UI::page_open(
            __( 'Enquiry & Popup', 'modefilter-pro' ),
            __( 'Choose what happens when a customer clicks the Enquiry button on Catalog products.', 'modefilter-pro' )
        );

        MODEP_Admin_UI::tabs( 'enquiry' );

        echo '<form method="post" action="options.php" class="modep-form">';
        settings_fields( 'modep_enquiry_group' );

        MODEP_Admin_UI::section_card_open( __( 'Enquiry Click Action', 'modefilter-pro' ), __( 'Define the primary behavior for the enquiry trigger.', 'modefilter-pro' ) );
        self::field_action();
        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( __( 'Popup Content', 'modefilter-pro' ), __( 'Configuration for built-in or Elementor-based popups.', 'modefilter-pro' ) );
        echo '<h4>' . esc_html__( 'Built-in Shortcode Form', 'modefilter-pro' ) . '</h4>';
        self::field_form_shortcode();
        echo '<hr style="margin:20px 0; border:0; border-top:1px solid #eee;" />';
        echo '<h4>' . esc_html__( 'Elementor Template', 'modefilter-pro' ) . '</h4>';
        self::field_elementor_popup();
        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( __( 'Redirect Settings', 'modefilter-pro' ), __( 'If redirecting, choose your destination page or URL.', 'modefilter-pro' ) );
        self::field_redirect();
        MODEP_Admin_UI::section_card_close();

        submit_button( __( 'Save Enquiry Settings', 'modefilter-pro' ) );
        echo '</form>';

        MODEP_Admin_UI::page_close();
    }

    /* ---------------- Frontend ---------------- */

    public static function enqueue_frontend_assets() : void {
        $s = self::get_settings();
        $handle = 'modep-enquiry-js';

        wp_enqueue_script(
            $handle,
            plugins_url( 'assets/js/modep-enquiry.js', MODEP_PLUGIN_FILE ),
            [ 'jquery' ],
            MODEP_VERSION,
            true
        );

        $redirect_url = '';
        if ( ! empty( $s['redirect_page_id'] ) ) {
            $redirect_url = get_permalink( (int) $s['redirect_page_id'] );
        } elseif ( ! empty( $s['redirect_url'] ) ) {
            $redirect_url = $s['redirect_url'];
        }

        wp_localize_script( $handle, 'MODEP_Enquiry', [
            'action'           => $s['action'],
            'redirectUrl'      => esc_url( (string) $redirect_url ),
            'hasShortcodeForm' => ! empty( $s['form_shortcode'] ),
            'elementorPopupId' => absint( $s['elementor_popup_id'] ),
        ] );
    }

    public static function render_modal_container() : void {
        $s = self::get_settings();
        if ( 'popup_builtin' !== $s['action'] || empty( $s['form_shortcode'] ) ) {
            return;
        }
        ?>
        <div id="modep-enquiry-modal" class="modep-enquiry-modal" style="display:none;" aria-hidden="true" role="dialog">
            <div class="modep-enquiry-backdrop"></div>
            <div class="modep-enquiry-dialog">
                <button type="button" class="modep-enquiry-close" aria-label="<?php esc_attr_e( 'Close', 'modefilter-pro' ); ?>">&times;</button>
                <div class="modep-enquiry-content">
                    <?php echo do_shortcode( $s['form_shortcode'] ); ?>
                </div>
            </div>
        </div>
        <?php
    }
}