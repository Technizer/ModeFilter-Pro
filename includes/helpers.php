<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the ModeFilter Pro subscribers table name.
 *
 * @return string
 */
function modep_table_name() : string {
    global $wpdb;
    // FIX: Renamed table prefix from sft_ to modep_
    return "{$wpdb->prefix}modep_subscribers";
}

/**
 * Resolve a CSV of IDs / slugs / names into an array of term IDs.
 *
 * @param string $csv      Comma-separated list of IDs, slugs, or names.
 * @param string $taxonomy Taxonomy name.
 * @return int[]
 */
function modep_resolve_terms_to_ids( string $csv, string $taxonomy ) : array {
    $csv = trim( $csv );
    if ( '' === $csv ) {
        return [];
    }

    $parts = array_filter(
        array_map(
            'trim',
            preg_split( '/\s*,\s*/', $csv )
        )
    );

    $ids = [];

    foreach ( $parts as $p ) {
        // If it's numeric, treat directly as a term ID.
        if ( ctype_digit( $p ) ) {
            $ids[] = (int) $p;
            continue;
        }

        // First try slug.
        $term = get_term_by( 'slug', sanitize_title( $p ), $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            $ids[] = (int) $term->term_id;
            continue;
        }

        // Fallback to name.
        $term = get_term_by( 'name', $p, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            $ids[] = (int) $term->term_id;
        }
    }

    return array_values( array_unique( $ids ) );
}

/**
 * Get terms used by products within the "sellable" category set.
 *
 * @param string $taxonomy          Taxonomy to fetch (e.g. product_tag, product_brand).
 * @param string $sellable_cat_slug Base "sellable" product_cat slug.
 * @return WP_Term[]|array
 */
function modep_get_sellable_terms( string $taxonomy, string $sellable_cat_slug = 'bf-sale-2025' ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return [];
    }

    $q = new WP_Query(
        [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false, // We only need IDs for term lookup.
            // Filter by a single base "sellable" category to scope term collection.
            'tax_query'              => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Bounded single-taxonomy filter to derive term options for ModeFilter Pro UI.
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => [ $sellable_cat_slug ],
                ],
            ],
        ]
    );

    if ( empty( $q->posts ) ) {
        return [];
    }

    return wp_get_object_terms( $q->posts, $taxonomy );
}