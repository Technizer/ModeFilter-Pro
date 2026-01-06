<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class MODEP_Admin_Help {

    public static function render() : void {

        MODEP_Admin_UI::page_open(
            __( 'Help', 'modefilter-pro' ),
            __( 'Tips, FAQs, and links to reference docs & support.', 'modefilter-pro' )
        );

        MODEP_Admin_UI::tabs( 'help' );

        // stf-card stf-card-padded -> modep-card modep-card-padded
        echo '<div class="modep-card modep-card-padded">';
        echo '<p>' .
            esc_html__( 'Use the Filters screen to select available UI filters. Use Catalog Mode to control selling vs catalog behavior. The Shortcode Builder helps you insert preconfigured grids anywhere.', 'modefilter-pro' ) .
        '</p>';

        // stf-link -> modep-link
        echo '<p><a class="modep-link" href="' .
            esc_url(
                apply_filters(
                    'modep_docs_url', // renamed + properly prefixed
                    'https://szeeshanali.com/modefilter-pro/docs'
                )
            ) .
            '" target="_blank" rel="noopener">' .
            esc_html__( 'Open Documentation →', 'modefilter-pro' ) .
        '</a></p>';

        echo '</div>';

        MODEP_Admin_UI::page_close();
    }
}