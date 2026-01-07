<?php
declare(strict_types=1);

/**
 * ModeFilter Pro — Main Admin Controller
 * File: includes/admin/class-admin.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MODEP_Admin
 * Main controller for the ModeFilter Pro admin experience.
 */
final class MODEP_Admin {

    /**
     * Settings Keys Constants
     */
    const OPT_UI      = 'modep_ui';
    const OPT_GENERAL = 'modep_general';
    const OPT_LICENSE = 'modep_license';

    /**
     * Initialize the Admin environment.
     */
    public static function init() : void {
        // Load Admin Sub-Modules
        $modules = [
            'class-admin-ui.php',
            'class-admin-dashboard.php',
            'class-admin-filters.php',
            'class-admin-catalog.php',
            'class-admin-builder.php',
            'class-admin-help.php',
        ];

        foreach ( $modules as $file ) {
            $path = MODEP_PLUGIN_DIR . 'includes/admin/' . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }

        // Enquiry System
        if ( file_exists( MODEP_PLUGIN_DIR . 'includes/class-enquiry-settings.php' ) ) {
            require_once MODEP_PLUGIN_DIR . 'includes/class-enquiry-settings.php';
        }

        // Hooks
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ 'MODEP_Admin_Filters', 'register_settings' ] );
        add_action( 'admin_init', [ 'MODEP_Admin_Catalog', 'register_settings' ] );

        if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
            add_action( 'admin_init', [ 'MODEP_Enquiry_Settings', 'register_settings' ] );
        }

        // Assets
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        
        // Plugin Action Links
        add_filter( 'plugin_action_links_' . plugin_basename( MODEP_PLUGIN_FILE ), [ __CLASS__, 'add_settings_link' ] );
    }

    /**
     * Enqueue assets only on our plugin pages.
     */
    public static function enqueue_admin_assets( string $hook ) : void {
        // Only load on ModeFilter Pro pages
        if ( false === strpos( $hook, 'modefilter-pro' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        wp_enqueue_style( 'modep-admin-css', MODEP_PLUGIN_URL . 'admin/admin-dashboard.css', [], MODEP_VERSION );
        wp_enqueue_script( 'modep-admin-js', MODEP_PLUGIN_URL . 'admin/admin.js', [ 'jquery' ], MODEP_VERSION, true );

        wp_localize_script( 'modep-admin-js', 'MODEP_ADMIN', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'modep_admin_nonce' ),
        ] );

        /**
         * Fix for Line 92: Using filter_input to safely access the page parameter.
         * This satisfies the WordPress.Security.NonceVerification.Recommended warning
         * for read-only GET requests used for UI logic.
         */
        $current_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( 'modefilter-pro-builder' === $current_page ) {
            wp_enqueue_script( 'modep-builder', MODEP_PLUGIN_URL . 'admin/admin-shortcode-builder.js', [ 'jquery' ], MODEP_VERSION, true );
        }
    }

    /**
     * Add "Settings" link to the plugins table.
     */
    public static function add_settings_link( array $links ) : array {
        $settings_link = '<a href="admin.php?page=modefilter-pro">' . __( 'Settings', 'modefilter-pro' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Define the Sidebar Menu structure.
     */
    public static function menu() : void {
        $capability = 'manage_woocommerce';

        add_menu_page(
            __( 'ModeFilter Pro', 'modefilter-pro' ),
            __( 'ModeFilter Pro', 'modefilter-pro' ),
            $capability,
            'modefilter-pro',
            [ 'MODEP_Admin_Dashboard', 'render' ],
            'dashicons-filter',
            56
        );

        add_submenu_page( 'modefilter-pro', __( 'Dashboard', 'modefilter-pro' ), __( 'Dashboard', 'modefilter-pro' ), $capability, 'modefilter-pro', [ 'MODEP_Admin_Dashboard', 'render' ] );
        add_submenu_page( 'modefilter-pro', __( 'Global Store Mode', 'modefilter-pro' ), __( 'Global Store Mode', 'modefilter-pro' ), $capability, 'modefilter-pro-catalog', [ 'MODEP_Admin_Catalog', 'render' ] );
        add_submenu_page( 'modefilter-pro', __( 'Store Filters', 'modefilter-pro' ), __( 'Store Filters', 'modefilter-pro' ), $capability, 'modefilter-pro-filters', [ 'MODEP_Admin_Filters', 'render' ] );

        if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
            add_submenu_page( 'modefilter-pro', __( 'Enquiry & Popup', 'modefilter-pro' ), __( 'Enquiry & Popup', 'modefilter-pro' ), $capability, 'modefilter-pro-enquiry', [ 'MODEP_Enquiry_Settings', 'render_page' ] );
        }

        add_submenu_page( 'modefilter-pro', __( 'Shortcode Builder', 'modefilter-pro' ), __( 'Shortcode Builder', 'modefilter-pro' ), $capability, 'modefilter-pro-builder', [ 'MODEP_Admin_Builder', 'render' ] );
        add_submenu_page( 'modefilter-pro', __( 'Help', 'modefilter-pro' ), __( 'Help', 'modefilter-pro' ), $capability, 'modefilter-pro-help', [ 'MODEP_Admin_Help', 'render' ] );
    }
}