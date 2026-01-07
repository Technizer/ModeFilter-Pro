<?php
/**
 * PayPal Promotional Logic for ModeFilter Pro.
 * Controls the visibility and thresholds for PayPal "Pay Later" messaging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the PayPal promo should be displayed.
 *
 * @param bool       $show    Original show status.
 * @param WC_Product $product The product object.
 * @return bool
 */
function modep_optioned_show_paypal_promo( $show, $product ) {
	// 0 is default (disabled). Ensure we treat it as a boolean.
	$enabled = ( '1' === get_option( 'modep_paypal_promo_enabled', '0' ) );

	if ( ! $enabled ) {
		return false;
	}

	return (bool) $show;
}
add_filter( 'modep_show_paypal_promo', 'modep_optioned_show_paypal_promo', 10, 2 );

/**
 * Determine the minimum price threshold for the PayPal promo.
 *
 * @param float      $min     Default minimum (usually 30).
 * @param WC_Product $product The product object.
 * @return float
 */
function modep_optioned_paypal_min_amount( $min, $product ) {
	$raw_value = get_option( 'modep_paypal_min_amount', '' );

	// If no custom setting is provided, return the default template value.
	if ( '' === $raw_value ) {
		return (float) $min;
	}

	/**
	 * Sanitize the price:
	 * 1. Convert commas to dots for decimal consistency.
	 * 2. Use WooCommerce wc_format_decimal if available for better accuracy.
	 */
	$sanitized_raw = str_replace( ',', '.', (string) $raw_value );
	
	if ( function_exists( 'wc_format_decimal' ) ) {
		$value = (float) wc_format_decimal( $sanitized_raw );
	} else {
		$value = (float) $sanitized_raw;
	}

	// Ensure we don't return a negative number or zero if invalid input was provided.
	if ( $value <= 0 ) {
		return (float) $min;
	}

	return $value;
}
add_filter( 'modep_paypal_min_amount', 'modep_optioned_paypal_min_amount', 10, 2 );

/**
 * Helper to check if PayPal promo is active globally.
 * Useful for enqueuing external PayPal scripts only when necessary.
 *
 * @return bool
 */
function modep_is_paypal_promo_active() : bool {
	return '1' === get_option( 'modep_paypal_promo_enabled', '0' );
}