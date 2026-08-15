<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides respectful, dismissible review prompts after meaningful use.
 */
final class MODEP_Review {
	private const DISMISSED_META = '_modep_review_notice_dismissed';
	private const REVIEW_URL = 'https://wordpress.org/support/plugin/modefilter-pro/reviews/#new-post';

	public static function init() : void {
		add_filter( 'plugin_action_links_' . MODEP_PLUGIN_BASENAME, [ __CLASS__, 'plugin_links' ] );
		if ( is_admin() ) {
			add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
			add_action( 'admin_post_modep_dismiss_review', [ __CLASS__, 'dismiss' ] );
		}
	}

	public static function mark_used() : void {
		if ( ! get_option( 'modep_first_used_at' ) ) {
			add_option( 'modep_first_used_at', time(), '', false );
		}
	}

	public static function review_url() : string {
		return self::REVIEW_URL;
	}

	public static function plugin_links( array $links ) : array {
		$links[] = '<a href="' . esc_url( self::REVIEW_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Write a Review', 'modefilter-pro' ) . '</a>';
		return $links;
	}

	public static function notice() : void {
		if ( ! current_user_can( 'manage_woocommerce' ) || get_user_meta( get_current_user_id(), self::DISMISSED_META, true ) ) {
			return;
		}

		$first_used = (int) get_option( 'modep_first_used_at', 0 );
		if ( ! $first_used || time() < $first_used + ( 14 * DAY_IN_SECONDS ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=modep_dismiss_review' ),
			'modep_dismiss_review'
		);
		?>
		<div class="notice notice-info modep-review-notice">
			<p><strong><?php esc_html_e( 'Enjoying ModeFilter Pro?', 'modefilter-pro' ); ?></strong></p>
			<p><?php esc_html_e( 'A short WordPress.org review helps other WooCommerce store owners discover the plugin and helps us keep improving it.', 'modefilter-pro' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Write a Review', 'modefilter-pro' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=modefilter-pro-help' ) ); ?>"><?php esc_html_e( 'I Need Help', 'modefilter-pro' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Do not show again', 'modefilter-pro' ); ?></a>
			</p>
		</div>
		<?php
	}

	public static function dismiss() : void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'modefilter-pro' ) );
		}
		check_admin_referer( 'modep_dismiss_review' );
		update_user_meta( get_current_user_id(), self::DISMISSED_META, 'yes' );
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'plugins.php' ) );
		exit;
	}
}
