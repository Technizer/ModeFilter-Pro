<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Admin {

    const OPT_UI = 'modep_ui_filters';

    public static function init() : void {

        // Admin UI + pages
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-ui.php';
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-dashboard.php';
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-filters.php';
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-catalog.php';
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-builder.php';
        require_once MODEP_PLUGIN_DIR . 'includes/admin/class-admin-help.php';

        // Enquiry settings (this file also contains frontend hooks, so it's okay in /includes/)
        if ( file_exists( MODEP_PLUGIN_DIR . 'includes/class-enquiry-settings.php' ) ) {
            require_once MODEP_PLUGIN_DIR . 'includes/class-enquiry-settings.php';
        }

        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );

        // Register settings only when the admin is fully ready
        add_action( 'admin_init', [ 'MODEP_Admin_Filters', 'register_settings' ] );
        add_action( 'admin_init', [ 'MODEP_Admin_Catalog', 'register_settings' ] );

        // Enquiry settings registration (options)
        if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
            add_action( 'admin_init', [ 'MODEP_Enquiry_Settings', 'register_settings' ] );
        }

        // Assets (only our pages)
        add_action( 'admin_enqueue_scripts', function( $hook ) {

            if ( false === strpos( (string) $hook, 'modefilter-pro' ) ) {
                return;
            }

            // Since this action doesn't change data, a capability check is sufficient.
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            wp_enqueue_style( 'modep-admin', MODEP_PLUGIN_URL . 'admin/admin-dashboard.css', [], MODEP_VERSION );
            wp_enqueue_script( 'modep-admin', MODEP_PLUGIN_URL . 'admin/admin.js', [ 'jquery' ], MODEP_VERSION, true );

            // Shortcode Builder only.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We only check the page slug to conditionally load assets.
            $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

            if ( 'modefilter-pro-builder' === $page ) {
                wp_enqueue_script(
                    'modep-shortcode-builder',
                    MODEP_PLUGIN_URL . 'admin/admin-shortcode-builder.js',
                    [ 'jquery' ],
                    MODEP_VERSION,
                    true
                );
            }
        } );
    }

    public static function menu() : void {

        add_menu_page(
            __( 'ModeFilter Pro', 'modefilter-pro' ),
            __( 'ModeFilter Pro', 'modefilter-pro' ),
            'manage_woocommerce',
            'modefilter-pro',
            [ 'MODEP_Admin_Dashboard', 'render' ],
            'dashicons-filter',
            56
        );

        // Order requested:
        // 1) ModeFilter Pro (Dashboard)
        // 2) Global Store Mode
        // 3) Store Filters
        // 4) Enquiry & Popup
        // 5) Shortcode Builder
        // 6) Help

        add_submenu_page(
            'modefilter-pro',
            __( 'Global Store Mode', 'modefilter-pro' ),
            __( 'Global Store Mode', 'modefilter-pro' ),
            'manage_woocommerce',
            'modefilter-pro-catalog',
            [ 'MODEP_Admin_Catalog', 'render' ]
        );

        add_submenu_page(
            'modefilter-pro',
            __( 'Store Filters', 'modefilter-pro' ),
            __( 'Store Filters', 'modefilter-pro' ),
            'manage_woocommerce',
            'modefilter-pro-filters',
            [ 'MODEP_Admin_Filters', 'render' ]
        );

        if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
            add_submenu_page(
                'modefilter-pro',
                __( 'Enquiry & Popup', 'modefilter-pro' ),
                __( 'Enquiry & Popup', 'modefilter-pro' ),
                'manage_woocommerce',
                'modefilter-pro-enquiry',
                [ 'MODEP_Enquiry_Settings', 'render_page' ]
            );
        }

        add_submenu_page(
            'modefilter-pro',
            __( 'Shortcode Builder', 'modefilter-pro' ),
            __( 'Shortcode Builder', 'modefilter-pro' ),
            'manage_woocommerce',
            'modefilter-pro-builder',
            [ 'MODEP_Admin_Builder', 'render' ]
        );

        add_submenu_page(
            'modefilter-pro',
            __( 'Help', 'modefilter-pro' ),
            __( 'Help', 'modefilter-pro' ),
            'manage_woocommerce',
            'modefilter-pro-help',
            [ 'MODEP_Admin_Help', 'render' ]
        );
    }
}
