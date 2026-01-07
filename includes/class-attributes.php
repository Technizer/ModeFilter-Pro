<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MODEP_Attributes
 * Utility class for handling WooCommerce Global Attributes as Filter Taxonomies.
 */
final class MODEP_Attributes {

	/**
	 * Get all registered WooCommerce product attributes.
	 * Returns: [ 'pa_color' => 'Color', 'pa_size' => 'Size' ]
	 */
	public static function get_registered_attribute_taxonomies() : array {
		$out = [];
		
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $out;
		}

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		
		if ( empty( $attribute_taxonomies ) ) {
			return $out;
		}

		foreach ( $attribute_taxonomies as $tax ) {
			$taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
			
			// Ensure the taxonomy actually exists in the current WP instance
			if ( taxonomy_exists( $taxonomy_name ) ) {
				// Use the label if provided, otherwise fallback to the capitalized slug
				$out[ $taxonomy_name ] = $tax->attribute_label ? $tax->attribute_label : ucfirst( $tax->attribute_name );
			}
		}

		return $out;
	}

	/**
	 * Get terms for a specific attribute taxonomy.
	 * Optimized for frontend performance.
	 */
	public static function get_terms_for_attribute( string $taxonomy, bool $hide_empty = false ) : array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
			'number'     => 100, // Safety cap to prevent UI overflow
		] );

		return is_wp_error( $terms ) ? [] : (array) $terms;
	}

	/**
	 * Build a tax_query compatible array from UI state.
	 * * @param array $selected Format: [ 'pa_color' => [12, 15], 'pa_size' => [33] ]
	 * @return array Standard WP_Query tax_query array
	 */
	public static function build_attribute_tax_query( array $selected ) : array {
		$tax_query = [];

		foreach ( $selected as $taxonomy => $ids ) {
			$term_ids = array_filter( array_map( 'intval', (array) $ids ) );

			if ( ! empty( $term_ids ) && taxonomy_exists( $taxonomy ) ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_ids,
					'operator' => 'IN',
				];
			}
		}

		// Use 'AND' relation if multiple attributes (e.g. Color AND Size) are selected
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}
}