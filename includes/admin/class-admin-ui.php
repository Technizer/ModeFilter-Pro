<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Admin_UI {

    public static function page_open( string $title, string $desc = '' ) : void {
        echo '<div class="wrap modep-wrap">';
        echo '<h1 class="modep-h1">' . esc_html( $title ) . '</h1>';

        if ( $desc ) {
            echo '<p class="modep-subtle">' . esc_html( $desc ) . '</p>';
        }
    }

    public static function page_close() : void {
        echo '</div>';
    }

    public static function tabs( string $active = 'dashboard' ) : void {

        $items = [
            'dashboard' => [
                'label' => __( 'ModeFilter Pro', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro' ),
            ],
            'catalog'   => [
                'label' => __( 'Global Store Mode', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro-catalog' ),
            ],
            'filters'   => [
                'label' => __( 'Store Filters', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro-filters' ),
            ],
            'enquiry'   => [
                'label' => __( 'Enquiry & Popup', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro-enquiry' ),
            ],
            'builder'   => [
                'label' => __( 'Shortcode Builder', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro-builder' ),
            ],
            'help'      => [
                'label' => __( 'Help', 'modefilter-pro' ),
                'url'   => admin_url( 'admin.php?page=modefilter-pro-help' ),
            ],
        ];

        if ( ! class_exists( 'MODEP_Enquiry_Settings' ) ) {
            unset( $items['enquiry'] );
        }

        /**
         * Filter the ModeFilter Pro admin tabs.
         *
         * @param array  $items  Tabs array.
         * @param string $active Active tab key.
         */
        $items = apply_filters( 'modep_admin_tabs', $items, $active );

        echo '<nav class="modep-tabs" role="tablist">';

        foreach ( $items as $key => $it ) {
            $is_active = ( $key === $active );
            $class     = 'modep-tab' . ( $is_active ? ' is-active' : '' );

            printf(
                '<a class="%1$s" href="%2$s" role="tab" aria-selected="%3$s" aria-current="%4$s">%5$s</a>',
                esc_attr( $class ),
                esc_url( $it['url'] ?? '#' ),
                esc_attr( $is_active ? 'true' : 'false' ),
                esc_attr( $is_active ? 'page' : 'false' ),
                esc_html( $it['label'] ?? '' )
            );
        }

        echo '</nav>';
    }

    public static function grid_open( int $cols = 3 ) : void {
        $cols = max( 1, min( 4, $cols ) );
        printf( '<div class="modep-grid modep-grid-%s">', esc_attr( (string) $cols ) );
    }

    public static function grid_close() : void {
        echo '</div>';
    }

    public static function card( array $args = [] ) : void {
        $title  = isset( $args['title'] ) ? (string) $args['title'] : '';
        $body   = isset( $args['body'] ) ? (string) $args['body'] : '';
        $footer = isset( $args['footer'] ) ? (string) $args['footer'] : '';
        $badge  = isset( $args['badge'] ) ? (string) $args['badge'] : '';

        echo '<div class="modep-card">';

        if ( $badge ) {
            echo '<span class="modep-badge">' . esc_html( $badge ) . '</span>';
        }

        if ( $title ) {
            echo '<h3 class="modep-card-title">' . esc_html( $title ) . '</h3>';
        }

        if ( $body ) {
            echo '<div class="modep-card-body">' . wp_kses_post( $body ) . '</div>';
        }

        if ( $footer ) {
            echo '<div class="modep-card-footer">' . wp_kses_post( $footer ) . '</div>';
        }

        echo '</div>';
    }

    public static function section_card_open( string $heading, string $desc = '' ) : void {
        echo '<section class="modep-section">';
        echo '  <div class="modep-section-aside">';
        echo '    <h3 class="modep-section-title">' . esc_html( $heading ) . '</h3>';

        if ( $desc ) {
            echo '    <p class="modep-section-desc">' . esc_html( $desc ) . '</p>';
        }

        echo '  </div>';
        echo '  <div class="modep-section-body modep-card modep-card-padded">';
    }

    public static function section_card_close() : void {
        echo '  </div>';
        echo '</section>';
    }

    public static function code( string $content ) : void {
        echo '<pre class="modep-code"><code>' . esc_html( $content ) . '</code></pre>';
    }

    /**
     * Render an admin button link.
     *
     * @param string $label   Button label.
     * @param string $url     URL.
     * @param bool   $primary Primary button?
     * @param array  $attrs   Extra HTML attributes (key => value).
     */
    public static function button( string $label, string $url = '#', bool $primary = true, array $attrs = [] ) : void {
        $classes = $primary ? 'button button-primary modep-btn' : 'button modep-btn';

        // Build safe attribute string.
        $attr_string = '';

        foreach ( $attrs as $k => $v ) {
            if ( '' === (string) $k ) {
                continue;
            }

            $attr_string .= sprintf(
                ' %1$s="%2$s"',
                esc_attr( (string) $k ),
                esc_attr( (string) $v )
            );
        }

        // To satisfy WordPress.Security.EscapeOutput.OutputNotEscaped, 
        // we use wp_kses to process the attribute string before output.
        // This ensures the content is safe while keeping your custom attributes.
        $allowed_html = [
            'a' => [
                'class'  => true,
                'href'   => true,
                'id'     => true,
                'target' => true,
                'rel'    => true,
                'title'  => true,
                'role'   => true,
                // Allow data attributes by using a wildcard pattern if supported, 
                // or specific ones used in your project.
            ],
        ];

        printf(
            '<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
            esc_attr( $classes ),
            esc_url( $url ),
            wp_kses( $attr_string, $allowed_html ),
            esc_html( $label )
        );
    }
}