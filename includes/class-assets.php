<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MODEP_Assets {

	/**
	 * Prevent double-localize across multiple hook invocations (Elementor + frontend).
	 *
	 * @var bool
	 */
	private static $localized = false;

	/**
	 * Register hooks.
	 */
	public static function init() : void {
		// Frontend.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ], 20 );

		// Elementor preview iframe (where widgets render).
		add_action( 'elementor/frontend/after_register_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'elementor/frontend/after_enqueue_scripts',  [ __CLASS__, 'enqueue' ], 20 );

		// Optional: Elementor editor PANEL (left sidebar).
		// add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'enqueue_panel' ] );
	}

	/**
	 * Compute a cache-busting version for assets.
	 * Prefers MODEP_VERSION if defined; otherwise uses filemtime.
	 *
	 * @param string $rel_path Relative path from plugin root, e.g. 'assets/js/modefilter-pro.js'
	 * @return string
	 */
	private static function ver( string $rel_path ) : string {
		if ( defined( 'MODEP_VERSION' ) && MODEP_VERSION ) {
			return (string) MODEP_VERSION;
		}

		$base_dir = defined( 'MODEP_PLUGIN_DIR' ) ? (string) MODEP_PLUGIN_DIR : plugin_dir_path( dirname( __FILE__ ) );
		$abs      = trailingslashit( $base_dir ) . ltrim( $rel_path, "/\\ \t\n\r\0\x0B" );

		return file_exists( $abs ) ? (string) filemtime( $abs ) : '1.0.0';
	}

	/**
	 * Determine assets base URL (always ends with trailing slash).
	 *
	 * @return string
	 */
	private static function assets_base_url() : string {
		if ( defined( 'MODEP_ASSETS_URL' ) && MODEP_ASSETS_URL ) {
			return trailingslashit( (string) MODEP_ASSETS_URL );
		}

		// Best: plugin root file constant.
		if ( defined( 'MODEP_PLUGIN_FILE' ) && MODEP_PLUGIN_FILE ) {
			return trailingslashit( plugins_url( 'assets/', (string) MODEP_PLUGIN_FILE ) );
		}

		// Fallback: relative to this file (includes/ -> assets/).
		return trailingslashit( plugins_url( '../assets/', __FILE__ ) );
	}

	/**
	 * Register styles/scripts (safe to call multiple times).
	 */
	public static function register() : void {
		$base_url = self::assets_base_url();

		// CSS
		if ( ! wp_style_is( 'modep-style', 'registered' ) ) {
			wp_register_style(
				'modep-style',
				$base_url . 'css/modefilter-pro.css',
				[],
				self::ver( 'assets/css/modefilter-pro.css' )
			);
		}

		// JS
		if ( ! wp_script_is( 'modep-js', 'registered' ) ) {
			wp_register_script(
				'modep-js',
				$base_url . 'js/modefilter-pro.js',
				[ 'jquery' ],
				self::ver( 'assets/js/modefilter-pro.js' ),
				true
			);
		}

		// Localize only once (Elementor calls can repeat).
		if ( ! self::$localized && wp_script_is( 'modep-js', 'registered' ) ) {
			self::$localized = true;

			wp_localize_script(
				'modep-js',
				'MODEP_VARS',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'modep_nonce' ), // must match MODEP_Ajax::NONCE_KEY
				]
			);
		}
	}

	/**
	 * Enqueue assets.
	 */
	public static function enqueue() : void {
		// Make sure they're registered even if a hook order changes.
		self::register();

		wp_enqueue_style( 'modep-style' );
		wp_enqueue_script( 'modep-js' );
	}

	/**
	 * Elementor editor PANEL (left sidebar) assets (optional).
	 */
	public static function enqueue_panel() : void {
		self::register();

		// Usually CSS only is enough for editor panel visuals.
		wp_enqueue_style( 'modep-style' );
	}
}