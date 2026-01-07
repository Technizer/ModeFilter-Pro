<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Loader.
 * Uses the Singleton pattern to coordinate all subsystems.
 */
final class MODEP_Plugin {

    /**
     * @var MODEP_Plugin|null
     */
    private static $instance = null;

    /**
     * Access the single instance of the class.
     */
    public static function instance() : self {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent external instantiation.
     */
    private function __construct() {}

    /**
     * Disable cloning and wakeup.
     */
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception( "Cannot unserialize a singleton." );
    }

    /**
     * Initialize the plugin.
     */
    public function init() : void {
        // 1. Load global helper functions and payment logic.
        $pay_logic = MODEP_INCLUDES_DIR . 'functions-payments.php';
        if ( file_exists( $pay_logic ) ) {
            require_once $pay_logic;
        }

        // 2. Initialize Core Subsystems.
        if ( class_exists( 'MODEP_Assets' ) )    MODEP_Assets::init();
        if ( class_exists( 'MODEP_Shortcode' ) ) MODEP_Shortcode::init();
        if ( class_exists( 'MODEP_Ajax' ) )      MODEP_Ajax::init();
        if ( class_exists( 'MODEP_Stock' ) )     MODEP_Stock::init();

        // 3. Elementor Integration.
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_elementor_category' ] );
        add_action( 'elementor/widgets/register',               [ $this, 'register_elementor_widgets' ] );
    }

    /**
     * Register a custom category in the Elementor Editor sidebar.
     *
     * @param \Elementor\Elements_Manager $manager
     */
    public function register_elementor_category( $manager ) : void {
        $manager->add_category(
            'modefilter-pro',
            [
                'title' => __( 'ModeFilter Pro', 'modefilter-pro' ),
                'icon'  => 'eicon-filter',
            ]
        );
    }

    /**
     * Register Elementor Widgets.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_elementor_widgets( $widgets_manager ) : void {
        // Ensure Elementor is fully loaded and functional.
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Define the widgets we want to load.
        $widgets = [
            'MODEP_Elementor_Widget'         => 'class-elementor-widget.php',
            'MODEP_Elementor_Widget_Catalog' => 'class-elementor-widget-catalog.php',
        ];

        foreach ( $widgets as $class_name => $file_name ) {
            $path = MODEP_INCLUDES_DIR . $file_name;

            if ( file_exists( $path ) ) {
                require_once $path;

                if ( class_exists( $class_name ) ) {
                    // Modern registration method for Elementor 3.5+.
                    $widgets_manager->register( new $class_name() );
                }
            }
        }
    }
}