<?php
/**
 * ModeFilter Pro — Admin Dashboard
 * File: includes/admin/class-admin-dashboard.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Admin_Dashboard {

    /**
     * Filterable documentation URL.
     */
    public static function docs_url() {
        return apply_filters( 'modep_docs_url', 'https://szeeshanali.com/modefilter-pro/docs' );
    }

    /**
     * Filterable YouTube channel URL.
     */
    public static function yt_url() {
        return apply_filters( 'modep_yt_url', 'https://www.youtube.com/@modefilter-pro' );
    }

    /**
     * URL to the internal Shortcode Builder page.
     */
    public static function builder_url() {
        return admin_url( 'admin.php?page=modefilter-pro-builder' );
    }

    /**
     * Main Render Method.
     */
    public static function render() : void {
        MODEP_Admin_UI::page_open(
            __( 'ModeFilter Pro — Dashboard', 'modefilter-pro' ),
            __( 'Welcome! Explore docs, watch quick tutorials, copy common shortcodes, or open the shortcode builder.', 'modefilter-pro' )
        );

        MODEP_Admin_UI::tabs( 'dashboard' );

        // --- Hero Section ---
        echo '<div class="modep-hero">';
            echo '<div class="modep-hero__cta">';
                MODEP_Admin_UI::button(
                    __( 'Open Shortcode Builder', 'modefilter-pro' ),
                    self::builder_url(),
                    true,
                    [ 'id' => 'modep-open-builder' ]
                );
                echo '&nbsp;';
                MODEP_Admin_UI::button(
                    __( 'View Documentation', 'modefilter-pro' ),
                    self::docs_url(),
                    false,
                    [
                        'target' => '_blank',
                        'rel'    => 'noopener',
                    ]
                );
            echo '</div>';
        echo '</div>';

        // --- 3-Column Resource Grid ---
        MODEP_Admin_UI::grid_open( 3 );

        // 1. Documentation Card
        ob_start();
        ?>
        <p><?php esc_html_e( 'Start with quick setup, presets, and best practices for speed & UX.', 'modefilter-pro' ); ?></p>
        <ul class="modep-list">
            <li><a href="<?php echo esc_url( self::docs_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Getting Started', 'modefilter-pro' ); ?></a></li>
            <li><a href="<?php echo esc_url( self::docs_url() . '/shortcodes' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'All Shortcodes', 'modefilter-pro' ); ?></a></li>
            <li><a href="<?php echo esc_url( self::docs_url() . '/catalog-mode' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Catalog Mode', 'modefilter-pro' ); ?></a></li>
        </ul>
        <?php
        $docs_body = ob_get_clean();

        MODEP_Admin_UI::card([
            'badge'  => __( 'Docs', 'modefilter-pro' ),
            'title'  => __( 'Documentation', 'modefilter-pro' ),
            'body'   => $docs_body,
            'footer' => '<a class="modep-link" href="' . esc_url( self::docs_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open full docs →', 'modefilter-pro' ) . '</a>',
        ]);

        // 2. Tutorials Card (YouTube)
        ob_start();
        ?>
        <div class="modep-media">
            <iframe class="modep-yt"
                    src="https://www.youtube.com/embed/dQw4w9WgXcQ"
                    title="<?php esc_attr_e( 'ModeFilter Pro – Quick Start', 'modefilter-pro' ); ?>"
                    allowfullscreen
                    loading="lazy"></iframe>
        </div>
        <ul class="modep-list">
            <li><a href="<?php echo esc_url( self::yt_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Channel: Latest Tutorials', 'modefilter-pro' ); ?></a></li>
            <li><?php esc_html_e( 'Building a minimal chips UI', 'modefilter-pro' ); ?></li>
            <li><?php esc_html_e( 'Catalog mode with custom buttons', 'modefilter-pro' ); ?></li>
        </ul>
        <?php
        $yt_body = ob_get_clean();

        MODEP_Admin_UI::card([
            'badge'  => __( 'Video', 'modefilter-pro' ),
            'title'  => __( 'YouTube Tutorials', 'modefilter-pro' ),
            'body'   => $yt_body,
            'footer' => '<a class="modep-link" href="' . esc_url( self::yt_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visit channel →', 'modefilter-pro' ) . '</a>',
        ]);

        // 3. Quick Shortcodes Card
        ob_start();
        ?>
        <p><?php esc_html_e( 'Copy basics and paste anywhere.', 'modefilter-pro' ); ?></p>
        <div class="modep-sc-list">
            <div class="modep-sc-row">
                <code>[modep_filters]</code>
                <button class="button modep-copy" data-copy="[modep_filters]">
                    <?php esc_html_e( 'Copy', 'modefilter-pro' ); ?>
                </button>
            </div>
            <div class="modep-sc-row">
                <code>[modep_filters columns="3" per_page="12" preset="minimal"]</code>
                <button class="button modep-copy" data-copy='[modep_filters columns="3" per_page="12" preset="minimal"]'>
                    <?php esc_html_e( 'Copy', 'modefilter-pro' ); ?>
                </button>
            </div>
            <div class="modep-sc-row">
                <code>[modep_filters filter_ui="chips" filter_position="top"]</code>
                <button class="button modep-copy" data-copy='[modep_filters filter_ui="chips" filter_position="top"]'>
                    <?php esc_html_e( 'Copy', 'modefilter-pro' ); ?>
                </button>
            </div>
            <div class="modep-sc-row">
                <code>[modep_filters only_catalog="yes"]</code>
                <button class="button modep-copy" data-copy='[modep_filters only_catalog="yes"]'>
                    <?php esc_html_e( 'Copy (Catalog)', 'modefilter-pro' ); ?>
                </button>
            </div>
        </div>
        <?php
        $sc_body = ob_get_clean();

        MODEP_Admin_UI::card([
            'badge'  => __( 'Shortcodes', 'modefilter-pro' ),
            'title'  => __( 'Quick Shortcodes', 'modefilter-pro' ),
            'body'   => $sc_body,
            'footer' => '<a class="modep-link" href="' . esc_url( self::builder_url() ) . '">' . esc_html__( 'Open Shortcode Builder →', 'modefilter-pro' ) . '</a>',
        ]);

        MODEP_Admin_UI::grid_close();

        // --- Usage Guide Section (Hybrid Logic) ---
        MODEP_Admin_UI::grid_open( 1 );

        ob_start();
        ?>
        <p><?php esc_html_e( 'ModeFilter Pro lets you run a hybrid store: some products are fully sellable, while others act as catalog / enquiry-only items.', 'modefilter-pro' ); ?></p>

        <div class="modep-columns modep-columns--2">
            <div class="modep-columns__col">
                <h3><?php esc_html_e( 'ModeFilter Shop Products', 'modefilter-pro' ); ?></h3>
                <ul class="modep-list">
                    <li><?php esc_html_e( 'Shows only sellable products — catalog-mode products are automatically excluded.', 'modefilter-pro' ); ?></li>
                    <li><?php esc_html_e( 'Uses normal WooCommerce pricing and Add to Cart behaviour.', 'modefilter-pro' ); ?></li>
                    <li><?php esc_html_e( 'Ideal for your main shop grids and sales layouts.', 'modefilter-pro' ); ?></li>
                </ul>
            </div>

            <div class="modep-columns__col">
                <h3><?php esc_html_e( 'ModeFilter Catalog Products', 'modefilter-pro' ); ?></h3>
                <ul class="modep-list">
                    <li><?php esc_html_e( 'Shows only catalog-mode products (items flagged as not directly sellable).', 'modefilter-pro' ); ?></li>
                    <li><?php esc_html_e( 'Replaces Add to Cart with your Enquire button or Popup.', 'modefilter-pro' ); ?></li>
                    <li><?php esc_html_e( 'Perfect for quotation-based or showcase product ranges.', 'modefilter-pro' ); ?></li>
                </ul>
            </div>
        </div>

        <hr class="modep-separator" />

        <h3><?php esc_html_e( 'Store Mode (Sell / Catalog / Hybrid)', 'modefilter-pro' ); ?></h3>
        <ul class="modep-list">
            <li><?php esc_html_e( 'Sell: Every product behaves like normal WooCommerce (Cart + Checkout).', 'modefilter-pro' ); ?></li>
            <li><?php esc_html_e( 'Catalog: Every product behaves as enquiry-only (No Cart).', 'modefilter-pro' ); ?></li>
            <li><?php esc_html_e( 'Hybrid: Mix both — Products are Sellable by default, and you can switch specific items to Catalog from the Product List.', 'modefilter-pro' ); ?></li>
        </ul>

        <p class="modep-note">
            <?php esc_html_e( 'Tip: the Product Mode toggle column only appears when Store Mode is set to Hybrid.', 'modefilter-pro' ); ?>
        </p>
        <?php
        $usage_body = ob_get_clean();

        MODEP_Admin_UI::card([
            'badge'  => __( 'Usage', 'modefilter-pro' ),
            'title'  => __( 'Shop vs Catalog Behaviour & Modes', 'modefilter-pro' ),
            'body'   => $usage_body,
            'footer' => '',
        ]);

        MODEP_Admin_UI::grid_close();
        MODEP_Admin_UI::page_close();
    }
}