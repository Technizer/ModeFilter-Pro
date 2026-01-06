<?php
// === PayPal promo: option-driven behaviour ===============================

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Should we show the PayPal promo for this product?
 *
 * Controlled by option: modep_paypal_promo_enabled.
 */
function modep_optioned_show_paypal_promo( $show, $product ) {
	$enabled = (bool) get_option( 'modep_paypal_promo_enabled', 0 );

	if ( ! $enabled ) {
		return false;
	}

	return (bool) $show;
}
add_filter( 'modep_show_paypal_promo', 'modep_optioned_show_paypal_promo', 10, 2 );

/**
 * Minimum price for PayPal promo.
 *
 * Controlled by option: modep_paypal_min_amount.
 */
function modep_optioned_paypal_min_amount( $min, $product ) {
	$raw = get_option( 'modep_paypal_min_amount', '' );

	if ( '' === $raw ) {
		return $min; // fall back to template default (e.g. 30).
	}

	$raw = str_replace( ',', '.', (string) $raw );
	$value = (float) $raw;

	if ( $value <= 0 ) {
		return $min;
	}

	return $value;
}
add_filter( 'modep_paypal_min_amount', 'modep_optioned_paypal_min_amount', 10, 2 );