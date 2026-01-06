<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class MODEP_Plugin {
    private static $instance = null;

    public static function instance() : self {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function init() : void {

        // Load global functions (PayPal promo logic etc.)
        require_once __DIR__ . '/functions-payments.php';

        // Subsystems (init only once here)
        if ( class_exists( 'MODEP_Assets' ) )    MODEP_Assets::init();
        if ( class_exists( 'MODEP_Shortcode' ) ) MODEP_Shortcode::init();
        if ( class_exists( 'MODEP_Ajax' ) )      MODEP_Ajax::init();
        if ( class_exists( 'MODEP_Stock' ) )     MODEP_Stock::init();

        // Elementor integration — our single source of truth
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_elementor_category' ] );
        add_action( 'elementor/widgets/register',               [ $this, 'register_elementor_widget' ] );

        // (Optional) legacy compatibility
        // add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_elementor_widget_legacy' ] );
    }

    public function register_elementor_category( $manager ) {
        $manager->add_category(
            'modefilter-pro',
            [ 'title' => __( 'ModeFilter Pro', 'modefilter-pro' ), 'icon' => 'eicon-filter' ],
            1
        );
    }

    public function register_elementor_widget( $widgets_manager ) {

    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }

    // Sellable / main widget
    $file = MODEP_INCLUDES_DIR . 'class-elementor-widget.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
    if ( class_exists( 'MODEP_Elementor_Widget' ) ) {
        $widgets_manager->register( new \MODEP_Elementor_Widget() );
    }

    // Catalog widget
    $catalog_file = MODEP_INCLUDES_DIR . 'class-elementor-widget-catalog.php';
    if ( file_exists( $catalog_file ) ) {
        require_once $catalog_file;
    }

    // IMPORTANT: this must match the class defined inside class-elementor-widget-catalog.php
    if ( class_exists( 'MODEP_Elementor_Widget_Catalog' ) ) {
        $widgets_manager->register( new \MODEP_Elementor_Widget_Catalog() );
    }
}



    // Optional legacy hook support
    public function register_elementor_widget_legacy() {
        if ( ! did_action( 'elementor/loaded' ) ) return;
        $file = MODEP_INCLUDES_DIR . 'class-elementor-widget.php';
        if ( file_exists( $file ) ) require $file;
        if ( class_exists( 'MODEP_Elementor_Widget' ) ) {
            \Elementor\Plugin::instance()->widgets_manager->register( new \MODEP_Elementor_Widget() );
        }
    }
}