<?php
/**
 * Helper functions for ModeFilter Pro.
 * Includes performance optimizations and security hardening.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the ModeFilter Pro subscribers table name with the global prefix.
 *
 * @return string
 */
function modep_table_name() : string {
    global $wpdb;
    return "{$wpdb->prefix}modep_subscribers";
}

/**
 * Resolve a CSV of IDs, slugs, or names into an array of unique term IDs.
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

    $parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
    $ids   = [];

    foreach ( $parts as $p ) {
        if ( ctype_digit( $p ) ) {
            $ids[] = absint( $p );
            continue;
        }

        $term = get_term_by( 'slug', sanitize_title( $p ), $taxonomy );
        if ( $term instanceof WP_Term ) {
            $ids[] = (int) $term->term_id;
            continue;
        }

        $term = get_term_by( 'name', sanitize_text_field( $p ), $taxonomy );
        if ( $term instanceof WP_Term ) {
            $ids[] = (int) $term->term_id;
        }
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Get terms used by products within a specific "sellable" category.
 *
 * @param string $taxonomy          Taxonomy to fetch (e.g. product_tag, product_brand).
 * @param string $sellable_cat_slug Base "sellable" product_cat slug.
 * @return WP_Term[]|array
 */
function modep_get_sellable_terms( string $taxonomy, string $sellable_cat_slug = 'bf-sale-2025' ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return [];
    }

    $cache_key = 'modep_terms_' . md5( $taxonomy . $sellable_cat_slug );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    global $wpdb;

    /**
     * Performance optimization:
     * We use a single JOIN to find terms associated with products in a specific category.
     * * We disable DirectQuery/NoCaching warnings because we are manually 
     * managing the cache via WordPress Transients above.
     */
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $term_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT tr.term_taxonomy_id 
         FROM {$wpdb->term_relationships} AS tr
         INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
         WHERE tt.taxonomy = %s
         AND tr.object_id IN (
             SELECT object_id FROM {$wpdb->term_relationships} AS tr2
             INNER JOIN {$wpdb->term_taxonomy} AS tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
             INNER JOIN {$wpdb->terms} AS t2 ON tt2.term_id = t2.term_id
             WHERE tt2.taxonomy = 'product_cat' AND t2.slug = %s
         )",
        $taxonomy,
        $sellable_cat_slug
    ) );
    // phpcs:enable

    if ( empty( $term_ids ) ) {
        set_transient( $cache_key, [], 12 * HOUR_IN_SECONDS );
        return [];
    }

    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'include'    => array_map( 'absint', $term_ids ),
        'hide_empty' => false,
    ] );

    $results = ( ! is_wp_error( $terms ) ) ? $terms : [];

    set_transient( $cache_key, $results, 12 * HOUR_IN_SECONDS );

    return $results;
}

/**
 * Clear the sellable terms cache. 
 */
function modep_clear_term_cache( string $taxonomy, string $sellable_cat_slug ) : void {
    $cache_key = 'modep_terms_' . md5( $taxonomy . $sellable_cat_slug );
    delete_transient( $cache_key );
}