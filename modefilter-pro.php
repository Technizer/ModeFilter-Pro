<?php
/**
 * Plugin Name:       ModeFilter Pro
 * Plugin URI:        https://modefilterpro.com
 * Description:       Flexible WooCommerce AJAX filters with multiple styles, template kits, Elementor widgets, and catalog mode.
 * Version:           1.0.7
 * Author:            Syed Zeeshan Ali
 * Author URI:        https://syedzeeshanali.com
 * Text Domain:       modefilter-pro
 *
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 *
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** ------------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
define( 'MODEP_PLUGIN_FILE', __FILE__ );
define( 'MODEP_VERSION', '1.0.7' );
define( 'MODEP_PLUGIN_BASENAME', plugin_basename( MODEP_PLUGIN_FILE ) );
define( 'MODEP_PLUGIN_DIR', plugin_dir_path( MODEP_PLUGIN_FILE ) );
define( 'MODEP_INCLUDES_DIR', trailingslashit( MODEP_PLUGIN_DIR . 'includes' ) );
define( 'MODEP_TEMPLATES_DIR', trailingslashit( MODEP_PLUGIN_DIR . 'templates' ) );
define( 'MODEP_PLUGIN_URL', plugin_dir_url( MODEP_PLUGIN_FILE ) );

/** ------------------------------------------------------------------------
 * Core Loader
 * --------------------------------------------------------------------- */

/**
 * Load core plugin files.
 */
function modep_load_core_files() : void {
    $files = [
        MODEP_INCLUDES_DIR . 'helpers.php',
        MODEP_INCLUDES_DIR . 'class-assets.php',
        MODEP_INCLUDES_DIR . 'class-ajax.php',
        MODEP_INCLUDES_DIR . 'class-template-kits.php',
        MODEP_INCLUDES_DIR . 'class-shortcode.php',
        MODEP_INCLUDES_DIR . 'class-stock.php',
        MODEP_INCLUDES_DIR . 'class-attributes.php',
        MODEP_INCLUDES_DIR . 'class-catalog-mode.php',
        MODEP_INCLUDES_DIR . 'class-catalog-index.php',
        MODEP_INCLUDES_DIR . 'class-catalog-ext.php',
        MODEP_INCLUDES_DIR . 'class-review.php',
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

/** ------------------------------------------------------------------------
 * Lifecycle Hooks
 * --------------------------------------------------------------------- */

/**
 * Activation Hook
 */
register_activation_hook( MODEP_PLUGIN_FILE, function() {
    // We only perform database setup here. 
    // We do NOT check for WC or use wp_die() here because the 
    // "Requires Plugins" header handles dependency blocking safely.
    $db_file = MODEP_INCLUDES_DIR . 'class-db.php';
    if ( file_exists( $db_file ) ) {
        require_once $db_file;
        if ( class_exists( 'MODEP_DB' ) ) {
            MODEP_DB::create_table();
        }
    }
    update_option( 'modep_do_activation_redirect', true );
    add_option( 'modep_installed_at', time(), '', false );
    delete_option( 'modep_catalog_index_ready' );
    delete_option( 'modep_catalog_index_offset' );
});

/**
 * Deactivation Hook
 */
register_deactivation_hook( MODEP_PLUGIN_FILE, function() {
    delete_option( 'modep_do_activation_redirect' );
    wp_clear_scheduled_hook( 'modep_rebuild_catalog_index' );
});

/** ------------------------------------------------------------------------
 * Bootstrap (Deferred until plugins_loaded)
 * --------------------------------------------------------------------- */

add_action( 'plugins_loaded', function() : void {
    
    // Check if WooCommerce is actually loaded.
    // This is now safe and won't throw notices because it's inside plugins_loaded.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Load and Initialize
    modep_load_core_files();

    if ( class_exists( 'MODEP_Plugin' ) ) {
        MODEP_Plugin::instance()->init();
    }
    if ( class_exists( 'MODEP_Enquiry_Settings' ) ) {
        MODEP_Enquiry_Settings::init();
    }
    if ( is_admin() && class_exists( 'MODEP_Admin' ) ) {
        MODEP_Admin::init();
    }
}, 20 );

/** ------------------------------------------------------------------------
 * Admin Redirect & Compatibility
 * --------------------------------------------------------------------- */

add_action( 'admin_init', function() : void {
    if ( get_option( 'modep_do_activation_redirect' ) ) {
        delete_option( 'modep_do_activation_redirect' );
        if ( ! is_network_admin() && current_user_can( 'manage_woocommerce' ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=modefilter-pro' ) );
            exit;
        }
    }
});

// HPOS Compatibility
add_action( 'before_woocommerce_init', function() : void {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MODEP_PLUGIN_FILE, true );
    }
});

/** ------------------------------------------------------------------------
 * Template helper
 * --------------------------------------------------------------------- */
if ( ! function_exists( 'modep_template_path' ) ) {
    function modep_template_path( string $file ) : string {
        return trailingslashit( MODEP_PLUGIN_DIR . 'templates' ) . ltrim( $file, "/\\\t\n\r\0\x0B" );
    }
}
