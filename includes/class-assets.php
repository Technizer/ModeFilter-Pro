<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MODEP_Assets
 * Handles registration and enqueuing of Pro scripts and styles.
 */
final class MODEP_Assets {

	private static $localized = false;

	public static function init() : void {
		// Standard Frontend
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_and_enqueue' ], 10 );

		// Elementor Editor Specific
		add_action( 'elementor/frontend/after_register_scripts', [ __CLASS__, 'register' ], 10 );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'enqueue_editor_styles' ] );
	}

	/**
	 * Dynamic versioning for cache busting.
	 */
	private static function ver( string $rel_path ) : string {
		if ( defined( 'MODEP_VERSION' ) && MODEP_VERSION ) {
			return (string) MODEP_VERSION;
		}
		
		$base_dir = defined( 'MODEP_PLUGIN_DIR' ) ? MODEP_PLUGIN_DIR : plugin_dir_path( dirname( __FILE__, 1 ) );
		$file_path = trailingslashit( (string)$base_dir ) . $rel_path;

		return file_exists( $file_path ) ? (string) filemtime( $file_path ) : '1.0.0';
	}

	private static function assets_base_url() : string {
		if ( defined( 'MODEP_PLUGIN_URL' ) ) {
			return trailingslashit( MODEP_PLUGIN_URL ) . 'assets/';
		}
		return trailingslashit( plugins_url( 'assets/', dirname( __FILE__, 1 ) ) );
	}

	/**
	 * Register assets without enqueuing (useful for Elementor dependency mapping).
	 */
	public static function register() : void {
		$base_url = self::assets_base_url();

		// 1. Styles
		wp_register_style(
			'modep-style',
			$base_url . 'css/modefilter-pro.css',
			[],
			self::ver( 'assets/css/modefilter-pro.css' )
		);

		// 2. Scripts (Added dependencies for Isotope/ImagesLoaded)
		wp_register_script(
			'modep-js',
			$base_url . 'js/modefilter-pro.js',
			[ 'jquery', 'imagesloaded' ], 
			self::ver( 'assets/js/modefilter-pro.js' ),
			true
		);

		// 3. Localization
		if ( ! self::$localized ) {
			wp_localize_script(
				'modep-js',
				'MODEP_VARS',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'modep_nonce' ),
					'i18n'     => [
						'loading' => __( 'Updating products...', 'modefilter-pro' ),
						'error'   => __( 'Something went wrong. Please try again.', 'modefilter-pro' ),
					]
				]
			);
			self::$localized = true;
		}
	}

	/**
	 * Enqueue registered assets.
	 */
	public static function register_and_enqueue() : void {
		self::register();
		wp_enqueue_style( 'modep-style' );
		wp_enqueue_script( 'modep-js' );
	}

	/**
	 * Load specific styles for the Elementor Editor UI.
	 */
	public static function enqueue_editor_styles() : void {
		// Ensures our widget icon and editor-specific styling loads in the panel
		wp_enqueue_style( 'modep-editor-styles', self::assets_base_url() . 'css/editor.css', [], self::ver( 'assets/css/editor.css' ) );
	}
}