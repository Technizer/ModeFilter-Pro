<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset management for ModeFilter Pro.
 */
final class MODEP_Assets {

	private static $localized = false;

	public static function init() : void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ], 20 );

		add_action( 'elementor/frontend/after_register_scripts', [ __CLASS__, 'register' ], 5 );
		add_action( 'elementor/frontend/after_enqueue_scripts',  [ __CLASS__, 'enqueue' ], 20 );
	}

	private static function ver( string $rel_path ) : string {
		if ( defined( 'MODEP_VERSION' ) && MODEP_VERSION ) {
			return (string) MODEP_VERSION;
		}

		$base_dir = defined( 'MODEP_PLUGIN_DIR' ) ? (string) MODEP_PLUGIN_DIR : plugin_dir_path( dirname( __FILE__ ) );
		$abs      = trailingslashit( $base_dir ) . ltrim( $rel_path, '/\\' );

		return file_exists( $abs ) ? (string) filemtime( $abs ) : '1.0.0';
	}

	private static function assets_base_url() : string {
		if ( defined( 'MODEP_PLUGIN_FILE' ) ) {
			return trailingslashit( plugins_url( 'assets/', MODEP_PLUGIN_FILE ) );
		}
		return trailingslashit( plugins_url( '../assets/', __FILE__ ) );
	}

	public static function register() : void {
		$base_url = self::assets_base_url();

		if ( ! wp_style_is( 'modep-style', 'registered' ) ) {
			wp_register_style(
				'modep-style',
				$base_url . 'css/modefilter-pro.css',
				[],
				self::ver( 'assets/css/modefilter-pro.css' )
			);
		}

		if ( ! wp_script_is( 'modep-js', 'registered' ) ) {
			wp_register_script(
				'modep-js',
				$base_url . 'js/modefilter-pro.js',
				[ 'jquery' ],
				self::ver( 'assets/js/modefilter-pro.js' ),
				true
			);
		}

		if ( ! self::$localized && wp_script_is( 'modep-js', 'registered' ) ) {
			self::$localized = true;
			wp_localize_script(
				'modep-js',
				'MODEP_VARS',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'modep_nonce' ),
				]
			);
		}
	}

	public static function enqueue() : void {
		self::register();
		wp_enqueue_style( 'modep-style' );
		wp_enqueue_script( 'modep-js' );
	}
}