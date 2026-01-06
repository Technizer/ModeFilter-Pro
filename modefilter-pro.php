<?php
/**
 * Plugin Name:       ModeFilter Pro
 * Plugin URI:        https://szeeshanali.com/modefilter-pro
 * Description:       Elementor-ready product grid + faceted filters for WooCommerce with multiple presets and AJAX pagination.
 * Version:           1.0.5
 * Author:            Syed Zeeshan Ali
 * Author URI:        https://profiles.wordpress.org/szeeshanali/
 * Text Domain:       modefilter-pro
 *
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * WC tested up to:   10.3
 *
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** ------------------------------------------------------------------------
 * Constants - Consistent MODEP Prefix
 * --------------------------------------------------------------------- */

// Plugin information.
define( 'MODEP_PLUGIN_FILE', __FILE__ );
define( 'MODEP_VERSION', '1.0.5' );
define( 'MODEP_PLUGIN_BASENAME', plugin_basename( MODEP_PLUGIN_FILE ) );

// Directory paths.
define( 'MODEP_PLUGIN_DIR', plugin_dir_path( MODEP_PLUGIN_FILE ) );
define( 'MODEP_INCLUDES_DIR', trailingslashit( MODEP_PLUGIN_DIR . 'includes' ) );
define( 'MODEP_TEMPLATES_DIR', trailingslashit( MODEP_PLUGIN_DIR . 'templates' ) );
define( 'MODEP_ADMIN_DIR', trailingslashit( MODEP_PLUGIN_DIR . 'admin' ) );

// URL paths.
define( 'MODEP_PLUGIN_URL', plugin_dir_url( MODEP_PLUGIN_FILE ) );
define( 'MODEP_ASSETS_URL', trailingslashit( MODEP_PLUGIN_URL . 'assets' ) );

/** ------------------------------------------------------------------------
 * Load Plugin Core Files (Using a central autoload pattern is recommended
 * but sticking to includes for immediate execution here)
 * --------------------------------------------------------------------- */

/**
 * Load core plugin files.
 */
function modep_load_core_files() : void {
    $files = [
        MODEP_INCLUDES_DIR . 'helpers.php',
        MODEP_INCLUDES_DIR . 'class-db.php', // Required for MODEP_DB class used in activation/uninstall.
        MODEP_INCLUDES_DIR . 'class-assets.php',
        MODEP_INCLUDES_DIR . 'class-ajax.php',
        MODEP_INCLUDES_DIR . 'class-shortcode.php',
        MODEP_INCLUDES_DIR . 'class-stock.php',
        MODEP_INCLUDES_DIR . 'class-attributes.php',
        MODEP_INCLUDES_DIR . 'class-catalog-mode.php',
        MODEP_INCLUDES_DIR . 'class-catalog-ext.php',
        MODEP_INCLUDES_DIR . 'class-plugin.php',
        MODEP_INCLUDES_DIR . 'admin/class-admin.php',
        MODEP_INCLUDES_DIR . 'class-enquiry-settings.php',
    ];

    foreach ( $files as $file ) {
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}

// Load immediately so classes are available for hooks below.
modep_load_core_files();

/** ------------------------------------------------------------------------
 * Activation Hook: WooCommerce check + DB table creation + redirect flag
 * --------------------------------------------------------------------- */

/**
 * Actions taken on plugin activation.
 */
function modep_on_activation() : void {

    // WooCommerce must exist.
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( MODEP_PLUGIN_BASENAME );

        wp_die(
            '<p>' . esc_html__( 'ModeFilter Pro requires WooCommerce to be installed and active. Please install/activate WooCommerce and try again.', 'modefilter-pro' ) . '</p>' .
            '<p><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">&laquo; ' . esc_html__( 'Return to Plugins', 'modefilter-pro' ) . '</a></p>'
        );
    }

    // Create database table for Back-in-Stock subscribers.
    if ( class_exists( 'MODEP_DB' ) ) {
        MODEP_DB::create_table();
    }

    // Flag for one-time activation redirect (using option instead of transient).
    update_option( 'modep_do_activation_redirect', true );
}
register_activation_hook( MODEP_PLUGIN_FILE, 'modep_on_activation' );

/** ------------------------------------------------------------------------
 * Deactivation Hook: Cleanup (Required for review standards)
 * --------------------------------------------------------------------- */

/**
 * Actions taken on plugin deactivation.
 * Note: Data is retained on deactivation, to be removed only upon uninstall.
 */
function modep_on_deactivation() : void {
    // Clean up activation flag just in case.
    delete_option( 'modep_do_activation_redirect' );
}
register_deactivation_hook( MODEP_PLUGIN_FILE, 'modep_on_deactivation' );

/** ------------------------------------------------------------------------
 * Uninstall Hook: Complete Data Cleanup (Mandatory for review standards)
 * --------------------------------------------------------------------- */

/**
 * Actions taken on plugin uninstall. Removes all persistent data.
 */
function modep_on_uninstall() : void {
    // 1. Remove database table.
    if ( class_exists( 'MODEP_DB' ) ) {
        MODEP_DB::drop_table();
    }

    // 2. Remove all plugin options (e.g., settings).
    // Note: You must ensure this function iterates through all options created by the plugin.
    delete_option( 'modep_do_activation_redirect' );
    // Add other plugin options here as they are defined:
    // delete_option( 'modep_settings_key' );
}
register_uninstall_hook( MODEP_PLUGIN_FILE, 'modep_on_uninstall' );

/** ------------------------------------------------------------------------
 * Post-activation redirect
 * --------------------------------------------------------------------- */
add_action(
    'admin_init',
    function () : void {

        if ( ! get_option( 'modep_do_activation_redirect' ) ) {
            return;
        }

        delete_option( 'modep_do_activation_redirect' );

        // Do not redirect in network admin.
        if ( is_network_admin() ) {
            return;
        }

        if ( current_user_can( 'manage_woocommerce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=modefilter-pro' ) );
            exit;
        }
    }
);

/** ------------------------------------------------------------------------
 * HPOS compatibility
 * --------------------------------------------------------------------- */
add_action(
    'before_woocommerce_init',
    function () : void {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                MODEP_PLUGIN_FILE,
                true
            );
        }
    }
);

/** ------------------------------------------------------------------------
 * Admin notice if WooCommerce missing - Now properly prefixed
 * --------------------------------------------------------------------- */

/**
 * Displays an admin notice if WooCommerce is missing.
 */
function modep_wc_missing_notice() : void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error is-dismissible">';
    echo '<p><strong>' . esc_html__( 'ModeFilter Pro is almost ready!', 'modefilter-pro' ) . '</strong></p>';
    echo '<p>' . esc_html__( 'This plugin requires WooCommerce to be installed and active. Please activate WooCommerce to start using ModeFilter Pro.', 'modefilter-pro' ) . '</p>';
    echo '</div>';
}

/** ------------------------------------------------------------------------
 * Bootstrap plugin after WooCommerce is loaded
 * --------------------------------------------------------------------- */
add_action(
    'plugins_loaded',
    function () : void {

        // Check for WooCommerce and display notice if missing.
        if ( ! class_exists( 'WooCommerce' ) ) {
            if ( is_admin() ) {
                add_action( 'admin_notices', 'modep_wc_missing_notice' );
            }
            return;
        }

        // Core systems.
        if ( class_exists( 'MODEP_Plugin' ) ) {
            // Note: If you have the ability, rename MODEP_Plugin to MODEP_Plugin for full consistency.
            MODEP_Plugin::instance()->init();
        }

        // Catalog mode extensions.
        if ( class_exists( 'MODEP_Catalog_Ext' ) ) {
            MODEP_Catalog_Ext::init();
        }

        // Enquiry popup + redirect settings.
        if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
            MODEP_Enquiry_Settings::init();
        }

        // Admin menus & UI.
        if ( is_admin() && class_exists( 'MODEP_Admin' ) ) {
            MODEP_Admin::init();
        }

        // PayPal settings (admin logo etc.).
        if ( is_admin() && class_exists( 'MODEP_Admin_PayPal_Settings' ) ) {
            MODEP_Admin_PayPal_Settings::init();
        }
    },
    20
);

/** ------------------------------------------------------------------------
 * Template helper - Now properly prefixed
 * --------------------------------------------------------------------- */
if ( ! function_exists( 'modep_template_path' ) ) {
    /**
     * Resolve a template path within the plugin templates directory.
     *
     * @param string $file Relative path to template file.
     * @return string
     */
    function modep_template_path( string $file ) : string {
        return trailingslashit( MODEP_TEMPLATES_DIR ) . ltrim( $file, '/\\' );
    }
}