<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class MODEP_Attributes {

    public static function get_registered_attribute_taxonomies() : array {
        // Returns an array like [ 'pa_color' => 'Color', 'pa_size' => 'Size', ... ]
        $out = [];
        if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) return $out;

        $taxes = wc_get_attribute_taxonomies();
        if ( empty( $taxes ) ) return $out;

        foreach ( $taxes as $tax ) {
            $tax_name = wc_attribute_taxonomy_name( $tax->attribute_name ); // pa_color
            $label    = $tax->attribute_label ?: $tax->attribute_name;
            if ( taxonomy_exists( $tax_name ) ) {
                $out[ $tax_name ] = $label;
            }
        }
        return $out;
    }

    public static function get_terms_for_attribute( string $taxonomy ) : array {
        if ( ! taxonomy_exists( $taxonomy ) ) return [];
        $terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ] );
        return is_wp_error( $terms ) ? [] : $terms;
    }

    /**
     * Build tax_query from selected attribute taxonomies + selected term IDs (UI state)
     * $selected is array like [ 'pa_color' => [12,15], 'pa_size' => [33], ... ]
     */
    public static function build_attribute_tax_query( array $selected ) : array {
        $tax_query = [];
        foreach ( $selected as $tax => $ids ) {
            if ( $ids && taxonomy_exists( $tax ) ) {
                $tax_query[] = [
                    'taxonomy' => $tax,
                    'field'    => 'term_id',
                    'terms'    => array_map( 'intval', (array) $ids ),
                ];
            }
        }
        return $tax_query;
    }
}