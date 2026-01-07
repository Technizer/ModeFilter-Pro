<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MODEP_Admin_Help
 * The Help & Support center for ModeFilter Pro.
 */
final class MODEP_Admin_Help {

    public static function render() : void {

        MODEP_Admin_UI::page_open(
            __( 'Help & Support', 'modefilter-pro' ),
            __( 'Get the most out of ModeFilter Pro with documentation and system diagnostics.', 'modefilter-pro' )
        );

        MODEP_Admin_UI::tabs( 'help' );

        // 1. Documentation & Support Grid
        MODEP_Admin_UI::grid_open( 2 );

        MODEP_Admin_UI::card([
            'title'  => __( 'Documentation', 'modefilter-pro' ),
            'body'   => __( 'Learn how to configure the AJAX filters, customize the masonry grid, and set up the Inquiry popup logic.', 'modefilter-pro' ),
            'footer' => sprintf(
                '<a class="button button-secondary" href="%1$s" target="_blank">%2$s</a>',
                esc_url( apply_filters( 'modep_docs_url', 'https://szeeshanali.com/modefilter-pro/docs' ) ),
                __( 'View Docs →', 'modefilter-pro' )
            )
        ]);

        MODEP_Admin_UI::card([
            'title'  => __( 'Get Support', 'modefilter-pro' ),
            'body'   => __( 'Running into issues? Our technical team is available to help with configuration or bug reports.', 'modefilter-pro' ),
            'footer' => sprintf(
                '<a class="button button-secondary" href="%1$s" target="_blank">%2$s</a>',
                esc_url( 'https://szeeshanali.com/support' ),
                __( 'Open Ticket', 'modefilter-pro' )
            )
        ]);

        MODEP_Admin_UI::grid_close();

        // 2. System Status & FAQ Section
        echo '<div class="modep-help-content" style="margin-top: 30px;">';
        
        MODEP_Admin_UI::section_card_open( 
            __( 'System Health', 'modefilter-pro' ), 
            __( 'Ensure your environment is correctly configured for the Pro features.', 'modefilter-pro' ) 
        );
        
        echo '<ul class="modep-status-list">';
        self::render_status_item( 'WooCommerce', class_exists( 'WooCommerce' ) );
        self::render_status_item( 'Elementor', did_action( 'elementor/loaded' ) );
        self::render_status_item( 'PHP Version (7.4+)', version_compare( PHP_VERSION, '7.4', '>=' ) );
        echo '</ul>';

        MODEP_Admin_UI::section_card_close();

        MODEP_Admin_UI::section_card_open( 
            __( 'Common Questions', 'modefilter-pro' ), 
            __( 'Quick solutions for common setup scenarios.', 'modefilter-pro' ) 
        );

        echo '<div class="modep-faq">';
        echo '<h4>' . esc_html__( 'How do I change the Enquiry button color?', 'modefilter-pro' ) . '</h4>';
        echo '<p>' . esc_html__( 'You can style the button directly within the Elementor Widget "Style" tab under the "Enquiry Button" section.', 'modefilter-pro' ) . '</p>';
        
        echo '<h4>' . esc_html__( 'Why is my Masonry grid not aligning?', 'modefilter-pro' ) . '</h4>';
        echo '<p>' . esc_html__( 'Ensure all product images have a consistent aspect ratio, or set a fixed height in the "Layout" settings of the widget.', 'modefilter-pro' ) . '</p>';
        echo '</div>';

        MODEP_Admin_UI::section_card_close();
        echo '</div>';

        MODEP_Admin_UI::page_close();
    }

    /**
     * Helper to render a status check item
     */
    private static function render_status_item( string $label, bool $status ) : void {
        $icon  = $status ? 'dashicons-yes-alt' : 'dashicons-warning';
        $color = $status ? '#46b450' : '#dc3232';
        $text  = $status ? __( 'Active/Correct', 'modefilter-pro' ) : __( 'Issues Detected', 'modefilter-pro' );

        printf(
            '<li style="margin-bottom: 8px;"><span class="dashicons %s" style="color:%s; vertical-align: middle;"></span> <strong>%s:</strong> %s</li>',
            esc_attr( $icon ),
            esc_attr( $color ),
            esc_html( $label ),
            esc_html( $text )
        );
    }
}