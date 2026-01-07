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

    /**
     * Render a consistent admin badge (e.g., "Pro", "New", "Active")
     */
    public static function badge( string $text, string $type = 'primary' ) : void {
        printf( 
            '<span class="modep-badge modep-badge--%s">%s</span>', 
            esc_attr( $type ), 
            esc_html( $text ) 
        );
    }

    /**
     * Optimized Section Card
     * Used for grouping related settings together.
     */
    public static function section_card_open( string $heading, string $desc = '' ) : void {
        echo '<div class="modep-section-wrapper">';
        echo '  <div class="modep-section-info">';
        echo '    <h3 class="modep-section-heading">' . esc_html( $heading ) . '</h3>';
        if ( $desc ) {
            echo '    <p class="modep-section-subtext">' . esc_html( $desc ) . '</p>';
        }
        echo '  </div>';
        echo '  <div class="modep-section-content modep-card">';
    }

    public static function section_card_close() : void {
        echo '  </div>';
        echo '</div>';
    }

    public static function code( string $content ) : void {
        echo '<pre class="modep-code"><code>' . esc_html( $content ) . '</code></pre>';
    }

    /**
     * Improved Button Helper
     * Supports arbitrary data attributes and target settings.
     */
    public static function button( string $label, string $url = '#', bool $primary = true, array $attrs = [] ) : void {
        $classes = $primary ? 'button button-primary modep-btn--main' : 'button modep-btn--secondary';
        
        $html_attrs = '';
        foreach ( $attrs as $key => $val ) {
            // Individual attributes are properly escaped here.
            $html_attrs .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( (string) $val ) );
        }

        printf(
            '<a class="%s" href="%s" %s>%s</a>',
            esc_attr( $classes ),
            esc_url( $url ),
            $html_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            esc_html( $label )
        );
    }

    /**
     * Helper for displaying a shortcode that can be clicked to copy.
     */
    public static function shortcode_display( string $code ) : void {
        echo '<div class="modep-shortcode-box">';
        echo '  <code>' . esc_html( $code ) . '</code>';
        echo '  <button class="modep-copy-btn" data-copy="' . esc_attr( $code ) . '">' . esc_html__( 'Copy', 'modefilter-pro' ) . '</button>';
        echo '</div>';
    }
}