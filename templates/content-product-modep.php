<?php
/**
 * Template: ModeFilter Pro — Product item
 * File: templates/content-product-modep.php
 *
 * Outputs a single <li> per product (no <ul> wrapper).
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

/**
 * IMPORTANT:
 * WooCommerce templates use `$args` conventionally, but PHPCS sometimes flags
 * unprefixed file-scope variables in plugin templates. We keep compatibility
 * while also using prefixed locals internally.
 */
$modep_args = ( isset( $args ) && is_array( $args ) ) ? $args : []; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

/**
 * Read template attrs in the most reliable way:
 * 1) $args['modep_attrs'] (ideal, when AJAX passes it)
 * 2) a global set by runtime (legacy fallback)
 * 3) filter fallback for devs
 */
$modep_attrs = [];
if ( isset( $modep_args['modep_attrs'] ) && is_array( $modep_args['modep_attrs'] ) ) {
	$modep_attrs = $modep_args['modep_attrs'];
} elseif ( isset( $GLOBALS['modep_current_attrs'] ) && is_array( $GLOBALS['modep_current_attrs'] ) ) {
	$modep_attrs = $GLOBALS['modep_current_attrs'];
}
$modep_attrs = apply_filters( 'modep_template_attrs', $modep_attrs, $product );

/**
 * Helpers
 */
$modep_get = static function ( array $modep_a, string $modep_k, $modep_default = '' ) {
	return array_key_exists( $modep_k, $modep_a ) ? $modep_a[ $modep_k ] : $modep_default;
};

$modep_yes = static function ( $modep_v, bool $modep_default_yes = true ) : bool {
	if ( '' === $modep_v || null === $modep_v ) {
		return $modep_default_yes;
	}
	return ( 'yes' === (string) $modep_v || '1' === (string) $modep_v || true === $modep_v );
};

$modep_clamp_int = static function ( $modep_v, int $modep_min, int $modep_max, int $modep_fallback ) : int {
	$modep_n = is_numeric( $modep_v ) ? (int) $modep_v : $modep_fallback;
	if ( $modep_n < $modep_min ) {
		return $modep_min;
	}
	if ( $modep_n > $modep_max ) {
		return $modep_max;
	}
	return $modep_n;
};

// ------------------------------
// Preset/layout
// ------------------------------
$modep_preset        = sanitize_key( (string) $modep_get( $modep_attrs, 'preset', 'normal' ) );
$modep_custom_layout = sanitize_text_field( (string) $modep_get( $modep_attrs, 'custom_layout', '' ) );

// ------------------------------
// Catalog options / behavior
// ------------------------------
$modep_only_catalog = ( 'yes' === (string) $modep_get( $modep_attrs, 'only_catalog', '' ) );

// Elementor / widget visibility controls
$modep_show_image = $modep_yes(
	$modep_get( $modep_attrs, 'show_image', $modep_get( $modep_attrs, 'show_thumbnail', 'yes' ) ),
	true
);

// Excerpt controls
$modep_show_excerpt = $modep_yes( $modep_get( $modep_attrs, 'show_excerpt', 'yes' ), true );

$modep_excerpt_length_type = (string) $modep_get( $modep_attrs, 'excerpt_length_type', 'words' );
$modep_excerpt_length_type = in_array( $modep_excerpt_length_type, [ 'words', 'chars' ], true ) ? $modep_excerpt_length_type : 'words';

$modep_excerpt_length = $modep_clamp_int( $modep_get( $modep_attrs, 'excerpt_length', 20 ), 1, 500, 20 );

// Catalog button / message
$modep_catalog_btn_text = sanitize_text_field(
	(string) $modep_get( $modep_attrs, 'catalog_button_text', __( 'Enquire Now', 'modefilter-pro' ) )
);

$modep_message_enable  = ( 'yes' === (string) $modep_get( $modep_attrs, 'catalog_message_enable', 'no' ) );
$modep_catalog_message = sanitize_textarea_field( (string) $modep_get( $modep_attrs, 'catalog_message', '' ) );

$modep_inquire_action = (string) $modep_get( $modep_attrs, 'inquire_action', 'popup' );
$modep_inquire_action = in_array( $modep_inquire_action, [ 'popup', 'single' ], true ) ? $modep_inquire_action : 'popup';

// ------------------------------
// Linking controls
// ------------------------------
$modep_link_mode_raw = sanitize_key( (string) $modep_get( $modep_attrs, 'link_mode', '' ) );
$modep_link_mode     = $modep_link_mode_raw;

if ( '' === $modep_link_mode ) {
	$modep_link_target_legacy_raw = sanitize_key( (string) $modep_get( $modep_attrs, 'link_target', '' ) );
	switch ( $modep_link_target_legacy_raw ) {
		case 'none':
			$modep_link_mode = 'none';
			break;
		case 'image':
			$modep_link_mode = 'image';
			break;
		case 'title':
			$modep_link_mode = 'title';
			break;
		case 'button':
			$modep_link_mode = 'button';
			break;
		default:
			$modep_link_mode = 'image_title';
			break;
	}
}

$modep_allowed_link_modes = [ 'none', 'image', 'title', 'image_title', 'button', 'custom' ];
if ( ! in_array( $modep_link_mode, $modep_allowed_link_modes, true ) ) {
	$modep_link_mode = 'image_title';
}

$modep_custom_link = (string) $modep_get( $modep_attrs, 'custom_link_url', '' );
if ( '' === $modep_custom_link ) {
	$modep_custom_link = (string) $modep_get( $modep_attrs, 'custom_link', '' );
}
$modep_custom_link = $modep_custom_link ? esc_url_raw( $modep_custom_link ) : '';

$modep_link_external = ( '1' === (string) $modep_get( $modep_attrs, 'custom_link_external', '0' ) );
$modep_link_nofollow = ( '1' === (string) $modep_get( $modep_attrs, 'custom_link_nofollow', '0' ) );

$modep_rel = [];
if ( $modep_link_external ) {
	$modep_rel[] = 'noopener';
}
if ( $modep_link_nofollow ) {
	$modep_rel[] = 'nofollow';
}
$modep_rel_attr    = $modep_rel ? implode( ' ', array_unique( $modep_rel ) ) : '';
$modep_target_attr = $modep_link_external ? '_blank' : '';

// ------------------------------
// Build product data
// ------------------------------
$modep_product_id    = (int) $product->get_id();
$modep_product_link  = $modep_custom_link ? $modep_custom_link : $product->get_permalink();
$modep_product_title = (string) $product->get_name();
$modep_img_html      = $product->get_image( 'woocommerce_thumbnail' );
$modep_price_html    = $product->get_price_html();
$modep_stock_status  = (string) $product->get_stock_status();

// Stock badge label
$modep_badge_label = '';
if ( 'outofstock' === $modep_stock_status ) {
	$modep_badge_label = __( 'Out of stock', 'modefilter-pro' );
} elseif ( 'onbackorder' === $modep_stock_status ) {
	$modep_badge_label = __( 'Pre-Order', 'modefilter-pro' );
} elseif ( $product->is_on_sale() ) {
	$modep_badge_label = __( 'Sale', 'modefilter-pro' );
}

// PayPal promo (off by default)
$modep_show_paypal_promo = (bool) apply_filters( 'modep_show_paypal_promo', false, $product, $modep_attrs );
$modep_paypal_promo_url  = (string) apply_filters( 'modep_paypal_promo_url', '#', $product, $modep_attrs );
$modep_paypal_logo_src   = (string) apply_filters( 'modep_paypal_logo_src', '', $product, $modep_attrs );
$modep_paypal_promo_text = (string) apply_filters( 'modep_paypal_promo_text', __( 'Pay in 3 available', 'modefilter-pro' ), $product, $modep_attrs );

// Excerpt
$modep_excerpt_raw = wp_strip_all_tags( (string) $product->get_short_description() );
$modep_excerpt     = '';

if ( $modep_show_excerpt && '' !== $modep_excerpt_raw ) {
	if ( 'chars' === $modep_excerpt_length_type ) {
		$modep_text = trim( $modep_excerpt_raw );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			$modep_excerpt = ( mb_strlen( $modep_text ) > $modep_excerpt_length )
				? mb_substr( $modep_text, 0, $modep_excerpt_length ) . '…'
				: $modep_text;
		} else {
			$modep_excerpt = ( strlen( $modep_text ) > $modep_excerpt_length )
				? substr( $modep_text, 0, $modep_excerpt_length ) . '…'
				: $modep_text;
		}
	} else {
		$modep_excerpt = wp_trim_words( $modep_excerpt_raw, $modep_excerpt_length );
	}
}

// ------------------------------
// Layout parts + visibility
// ------------------------------
$modep_default_parts = $modep_only_catalog
	? [ 'badge', 'image', 'title', 'excerpt', 'add_to_cart', 'paypal' ]
	: [ 'badge', 'image', 'title', 'price', 'add_to_cart', 'paypal', 'excerpt' ];

$modep_parts_order = $modep_default_parts;
$modep_visible     = array_fill_keys( $modep_default_parts, true );

if ( '' !== $modep_custom_layout ) {
	$modep_tokens      = array_filter( array_map( 'trim', explode( '|', $modep_custom_layout ) ) );
	$modep_parts_order = [];
	$modep_visible     = array_fill_keys( $modep_default_parts, false );

	foreach ( $modep_tokens as $modep_tok ) {
		$modep_hide = false;
		if ( 0 === strpos( $modep_tok, '!' ) ) {
			$modep_hide = true;
			$modep_tok  = ltrim( $modep_tok, '!' );
		}

		$modep_key = sanitize_key( $modep_tok );
		if ( in_array( $modep_key, $modep_default_parts, true ) ) {
			$modep_parts_order[]         = $modep_key;
			$modep_visible[ $modep_key ] = ! $modep_hide;
		}
	}

	if ( empty( $modep_parts_order ) ) {
		$modep_parts_order = $modep_default_parts;
		$modep_visible     = array_fill_keys( $modep_default_parts, true );
	}
}

// Enforce catalog pricing rule
if ( $modep_only_catalog && isset( $modep_visible['price'] ) && '' === $modep_custom_layout ) {
	$modep_visible['price'] = false;
}

if ( ! $modep_show_image && isset( $modep_visible['image'] ) ) {
	$modep_visible['image'] = false;
}

$modep_link_image  = in_array( $modep_link_mode, [ 'image', 'image_title' ], true );
$modep_link_title  = in_array( $modep_link_mode, [ 'title', 'image_title' ], true );
$modep_link_button = ( 'button' === $modep_link_mode || 'custom' === $modep_link_mode );

if ( 'none' === $modep_link_mode ) {
	$modep_link_image  = false;
	$modep_link_title  = false;
	$modep_link_button = false;
}
?>
<li <?php wc_product_class( 'modep-product modep-product--preset-' . esc_attr( $modep_preset ), $product ); ?>>
	<div class="modep-product-inner">

		<?php foreach ( $modep_parts_order as $modep_part ) : ?>
			<?php if ( empty( $modep_visible[ $modep_part ] ) ) { continue; } ?>

			<?php if ( 'badge' === $modep_part ) : ?>
				<?php if ( $modep_badge_label ) : ?>
					<span class="modep-stock-badge modep-<?php echo esc_attr( $modep_stock_status ); ?>">
						<?php echo esc_html( $modep_badge_label ); ?>
					</span>
				<?php endif; ?>

			<?php elseif ( 'image' === $modep_part ) : ?>
				<?php if ( $modep_img_html ) : ?>
					<?php if ( $modep_link_image && $modep_product_link ) : ?>
						<a class="modep-thumb-link"
						   href="<?php echo esc_url( $modep_product_link ); ?>"
						   <?php if ( $modep_target_attr ) : ?>target="<?php echo esc_attr( $modep_target_attr ); ?>"<?php endif; ?>
						   <?php if ( $modep_rel_attr ) : ?>rel="<?php echo esc_attr( $modep_rel_attr ); ?>"<?php endif; ?>
						   aria-hidden="true"
						   data-title="<?php echo esc_attr( $modep_product_title ); ?>">
							<?php echo wp_kses_post( $modep_img_html ); ?>
						</a>
					<?php else : ?>
						<span class="modep-thumb modep-thumb--nolink" aria-hidden="true">
							<?php echo wp_kses_post( $modep_img_html ); ?>
						</span>
					<?php endif; ?>
				<?php endif; ?>

			<?php elseif ( 'title' === $modep_part ) : ?>
				<h4 class="modep-title">
					<?php if ( $modep_link_title && $modep_product_link ) : ?>
						<a href="<?php echo esc_url( $modep_product_link ); ?>"
						   <?php if ( $modep_target_attr ) : ?>target="<?php echo esc_attr( $modep_target_attr ); ?>"<?php endif; ?>
						   <?php if ( $modep_rel_attr ) : ?>rel="<?php echo esc_attr( $modep_rel_attr ); ?>"<?php endif; ?>>
							<?php echo esc_html( $modep_product_title ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $modep_product_title ); ?>
					<?php endif; ?>
				</h4>

			<?php elseif ( 'price' === $modep_part ) : ?>
				<?php if ( ! $modep_only_catalog && $modep_price_html ) : ?>
					<div class="modep-price">
						<?php echo wp_kses_post( $modep_price_html ); ?>
					</div>
				<?php endif; ?>

			<?php elseif ( 'excerpt' === $modep_part ) : ?>
				<?php if ( $modep_excerpt ) : ?>
					<p class="modep-excerpt"><?php echo esc_html( $modep_excerpt ); ?></p>
				<?php endif; ?>

			<?php elseif ( 'add_to_cart' === $modep_part ) : ?>

				<?php if ( $modep_only_catalog ) : ?>

					<?php if ( 'single' === $modep_inquire_action ) : ?>
						<a class="modep-enquire-btn"
						   href="<?php echo esc_url( $product->get_permalink() ); ?>"
						   data-product-id="<?php echo esc_attr( (string) $modep_product_id ); ?>">
							<?php echo esc_html( $modep_catalog_btn_text ); ?>
						</a>
					<?php else : ?>
						<?php if ( $modep_link_button && $modep_product_link ) : ?>
							<a class="modep-enquire-btn"
							   href="<?php echo esc_url( $modep_product_link ); ?>"
							   <?php if ( $modep_target_attr ) : ?>target="<?php echo esc_attr( $modep_target_attr ); ?>"<?php endif; ?>
							   <?php if ( $modep_rel_attr ) : ?>rel="<?php echo esc_attr( $modep_rel_attr ); ?>"<?php endif; ?>
							   data-product-id="<?php echo esc_attr( (string) $modep_product_id ); ?>">
								<?php echo esc_html( $modep_catalog_btn_text ); ?>
							</a>
						<?php else : ?>
							<button type="button"
									class="modep-enquire-btn modep-enquire-btn--popup modep-enquire"
									data-product-id="<?php echo esc_attr( (string) $modep_product_id ); ?>"
									data-product-title="<?php echo esc_attr( $modep_product_title ); ?>">
								<?php echo esc_html( $modep_catalog_btn_text ); ?>
							</button>
						<?php endif; ?>
					<?php endif; ?>

				<?php else : ?>

					<?php if ( 'outofstock' === $modep_stock_status ) : ?>
						<button type="button"
								class="modep-notify-btn"
								data-product-id="<?php echo esc_attr( (string) $modep_product_id ); ?>"
								data-product-title="<?php echo esc_attr( $modep_product_title ); ?>">
							<?php esc_html_e( 'Notify Me', 'modefilter-pro' ); ?>
						</button>
					<?php else : ?>
						<div class="modep-add-to-cart">
							<?php woocommerce_template_loop_add_to_cart(); ?>
						</div>
					<?php endif; ?>

				<?php endif; ?>

			<?php elseif ( 'paypal' === $modep_part ) : ?>
				<?php if ( ! $modep_only_catalog && $modep_show_paypal_promo && $modep_paypal_logo_src && '#' !== $modep_paypal_promo_url ) : ?>
					<div class="modep-paypal" aria-hidden="true">
						<a href="<?php echo esc_url( $modep_paypal_promo_url ); ?>"
						   target="_blank"
						   rel="noopener"
						   class="modep-paypal-link">
							<img loading="lazy"
								 class="modep-paypal-logo"
								 src="<?php echo esc_url( $modep_paypal_logo_src ); ?>"
								 alt="<?php esc_attr_e( 'PayPal', 'modefilter-pro' ); ?>"
								 width="80"
								 height="25" />
						</a>
						<p class="modep-paypal-text">
							<?php echo esc_html( $modep_paypal_promo_text ); ?>
						</p>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		<?php endforeach; ?>

		<?php if ( $modep_only_catalog && $modep_message_enable && '' !== $modep_catalog_message ) : ?>
			<p class="modep-catalog-message"><?php echo esc_html( $modep_catalog_message ); ?></p>
		<?php endif; ?>

	</div>
</li>