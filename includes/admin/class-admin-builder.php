<?php
/**
 * ModeFilter Pro — Shortcode Builder
 * File: includes/admin/class-admin-builder.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MODEP_Admin_Builder {

    /**
     * Renders the Shortcode Builder Page.
     * Note: This page relies on admin-builder.js to handle the generation logic.
     */
    public static function render() : void {
		$requested_kit = filter_input( INPUT_GET, 'kit', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$selected_kit  = class_exists( 'MODEP_Template_Kits' )
			? MODEP_Template_Kits::sanitize_kit_id( is_string( $requested_kit ) ? $requested_kit : 'classic' )
			: 'none';
		if ( 'none' === $selected_kit ) {
			$selected_kit = 'classic';
		}

        MODEP_Admin_UI::page_open(
            __( 'Shortcode Builder', 'modefilter-pro' ),
            __( 'Choose options and generate a ready-to-paste shortcode for your shop or catalog.', 'modefilter-pro' )
        );

        MODEP_Admin_UI::tabs( 'builder' );
        ?>
		<div id="modep-shortcode-builder" class="modep-card modep-card-padded" data-selected-kit="<?php echo esc_attr( $selected_kit ); ?>">
			<div class="modep-builder-kits">
				<h2><?php esc_html_e( 'Start with a Template Kit', 'modefilter-pro' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Each kit applies a different layout engine, filter presentation, palette, card design, pagination style, and loader. You can continue adjusting the fields below before copying the shortcode.', 'modefilter-pro' ); ?></p>
				<?php MODEP_Template_Kits::render_selector( $selected_kit ); ?>
			</div>
			<hr class="modep-separator" />
            <table class="form-table modep-form-table">

                <tr>
                    <th><label><?php esc_html_e( 'Mode', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_only_catalog" class="modep-select">
                            <option value="no"><?php esc_html_e( 'Sellable / Normal Products', 'modefilter-pro' ); ?></option>
                            <option value="yes"><?php esc_html_e( 'Catalog Products (only_catalog=yes)', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Columns', 'modefilter-pro' ); ?></label></th>
                    <td><input type="number" id="modep_sc_columns" min="1" max="6" value="3" class="modep-input" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Per Page', 'modefilter-pro' ); ?></label></th>
                    <td><input type="number" id="modep_sc_per" min="1" max="60" value="12" class="modep-input" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Preset', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_preset" class="modep-select">
                            <option value="normal"><?php esc_html_e( 'Normal', 'modefilter-pro' ); ?></option>
                            <option value="overlay"><?php esc_html_e( 'Overlay', 'modefilter-pro' ); ?></option>
                            <option value="minimal"><?php esc_html_e( 'Minimal', 'modefilter-pro' ); ?></option>
                            <option value="custom"><?php esc_html_e( 'Custom (use custom_layout)', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Pagination', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_pagination" class="modep-select">
                            <option value="load_more"><?php esc_html_e( 'Load More', 'modefilter-pro' ); ?></option>
                            <option value="numbers"><?php esc_html_e( 'Numbers', 'modefilter-pro' ); ?></option>
                            <option value="none"><?php esc_html_e( 'None', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Load More Text', 'modefilter-pro' ); ?></label></th>
                    <td><input type="text" id="modep_sc_load_more_text" value="<?php echo esc_attr__( 'Load more', 'modefilter-pro' ); ?>" class="modep-input" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Filters Mode', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_filters_mode" class="modep-select">
                            <option value="manual"><?php esc_html_e( 'Manual (opt-in)', 'modefilter-pro' ); ?></option>
                            <option value="auto"><?php esc_html_e( 'Auto-detect available', 'modefilter-pro' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'In auto mode, leave “Enable Filters” empty to auto-render what exists.', 'modefilter-pro' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="modep_sc_filter_style"><?php esc_html_e( 'Filter Style', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_filter_style" class="modep-select">
                            <option value="global"><?php esc_html_e( 'Inherit Global Default', 'modefilter-pro' ); ?></option>
                            <option value="chips"><?php esc_html_e( 'Modern Chips', 'modefilter-pro' ); ?></option>
                            <option value="checkboxes"><?php esc_html_e( 'Checkboxes', 'modefilter-pro' ); ?></option>
                            <option value="radios"><?php esc_html_e( 'Radio Buttons', 'modefilter-pro' ); ?></option>
                            <option value="toggles"><?php esc_html_e( 'Toggle Switches', 'modefilter-pro' ); ?></option>
                            <option value="hierarchical"><?php esc_html_e( 'Hierarchical Category Tree', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="modep_sc_category_hierarchy"><?php esc_html_e( 'Category Hierarchy', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_category_hierarchy" class="modep-select">
                            <option value="global"><?php esc_html_e( 'Inherit Global Default', 'modefilter-pro' ); ?></option>
                            <option value="yes"><?php esc_html_e( 'Show Parent / Child Tree', 'modefilter-pro' ); ?></option>
                            <option value="no"><?php esc_html_e( 'Flat Category List', 'modefilter-pro' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'The Hierarchical style always enables the category tree.', 'modefilter-pro' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Enable Filters', 'modefilter-pro' ); ?></label></th>
                    <td class="modep-row modep-gap">
                        <select id="modep_sc_filters" class="modep-select" multiple size="5" style="min-width:260px;">
                            <option value="categories"><?php esc_html_e( 'Categories', 'modefilter-pro' ); ?></option>
                            <option value="tags"><?php esc_html_e( 'Tags', 'modefilter-pro' ); ?></option>
                            <option value="brands"><?php esc_html_e( 'Brands', 'modefilter-pro' ); ?></option>
                            <option value="price"><?php esc_html_e( 'Price', 'modefilter-pro' ); ?></option>
                            <option value="rating"><?php esc_html_e( 'Rating', 'modefilter-pro' ); ?></option>
                        </select>

                        <select id="modep_sc_pos" class="modep-select" style="min-width:140px;">
                            <option value="left"><?php esc_html_e( 'Position: Left', 'modefilter-pro' ); ?></option>
                            <option value="top"><?php esc_html_e( 'Position: Top', 'modefilter-pro' ); ?></option>
                            <option value="right"><?php esc_html_e( 'Position: Right', 'modefilter-pro' ); ?></option>
                        </select>

                    </td>
                </tr>

                <tr>
                    <th><label for="modep_sc_loader_style"><?php esc_html_e( 'AJAX Loader', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_loader_style" class="modep-select">
                            <option value="global"><?php esc_html_e( 'Inherit Global Default', 'modefilter-pro' ); ?></option>
                            <option value="spinner"><?php esc_html_e( 'Spinning Loader', 'modefilter-pro' ); ?></option>
                            <option value="skeleton"><?php esc_html_e( 'Product Skeletons', 'modefilter-pro' ); ?></option>
                            <option value="dots"><?php esc_html_e( 'Animated Dots', 'modefilter-pro' ); ?></option>
                            <option value="pulse"><?php esc_html_e( 'Pulsing Ring', 'modefilter-pro' ); ?></option>
                            <option value="custom"><?php esc_html_e( 'Custom Image / GIF / SVG', 'modefilter-pro' ); ?></option>
                            <option value="none"><?php esc_html_e( 'No Visual Loader', 'modefilter-pro' ); ?></option>
                        </select>
                        <input type="url" id="modep_sc_loader_image" value="" class="modep-input" placeholder="https://example.com/loader.gif" />
                        <button type="button" class="button modep-loader-media-select" data-target="modep_sc_loader_image"><?php esc_html_e( 'Choose Media', 'modefilter-pro' ); ?></button>
                        <button type="button" class="button-link-delete modep-loader-media-clear" data-target="modep_sc_loader_image"><?php esc_html_e( 'Clear', 'modefilter-pro' ); ?></button>
                        <p class="description"><?php esc_html_e( 'The image URL is used only with the Custom loader.', 'modefilter-pro' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Terms Limit', 'modefilter-pro' ); ?></label></th>
                    <td><input type="number" id="modep_sc_terms_limit" min="1" max="200" value="12" class="modep-input" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Terms Order By', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_terms_orderby" class="modep-select">
                            <option value="count"><?php esc_html_e( 'Count', 'modefilter-pro' ); ?></option>
                            <option value="name"><?php esc_html_e( 'Name', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Terms Order', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_terms_order" class="modep-select">
                            <option value="DESC"><?php esc_html_e( 'DESC', 'modefilter-pro' ); ?></option>
                            <option value="ASC"><?php esc_html_e( 'ASC', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Show “More” Toggle', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_terms_show_more" class="modep-select">
                            <option value="yes"><?php esc_html_e( 'Yes', 'modefilter-pro' ); ?></option>
                            <option value="no"><?php esc_html_e( 'No', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Exclude Categories', 'modefilter-pro' ); ?></label></th>
                    <td><input type="text" id="modep_sc_exclude_cat" value="" class="modep-input" placeholder="uncategorized,12,15" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Exclude Tags', 'modefilter-pro' ); ?></label></th>
                    <td><input type="text" id="modep_sc_exclude_tag" value="" class="modep-input" placeholder="featured,10" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Exclude Brands', 'modefilter-pro' ); ?></label></th>
                    <td><input type="text" id="modep_sc_exclude_brand" value="" class="modep-input" placeholder="acme,7" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Sellable Base Category (slug)', 'modefilter-pro' ); ?></label></th>
                    <td><input type="text" id="modep_sc_sellable" value="" class="modep-input" placeholder="bf-sale-2025" /></td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Grid Layout Mode', 'modefilter-pro' ); ?></label></th>
                    <td class="modep-row modep-gap">
                        <select id="modep_sc_grid_layout" class="modep-select" style="min-width:160px;">
                            <option value="grid"><?php esc_html_e( 'Grid', 'modefilter-pro' ); ?></option>
                            <option value="masonry"><?php esc_html_e( 'Masonry', 'modefilter-pro' ); ?></option>
                            <option value="justified"><?php esc_html_e( 'Justified', 'modefilter-pro' ); ?></option>
                        </select>
                        <span class="modep-inline-label"><?php esc_html_e( 'Gap:', 'modefilter-pro' ); ?></span>
                        <input type="number" id="modep_sc_masonry_gap" min="0" max="200" value="20" class="modep-input" style="width:80px;" />
                        <span class="modep-inline-label"><?php esc_html_e( 'Row Height:', 'modefilter-pro' ); ?></span>
                        <input type="number" id="modep_sc_justified_row_height" min="50" max="1200" value="220" class="modep-input" style="width:100px;" />
                    </td>
                </tr>

                <tr>
                    <th><label><?php esc_html_e( 'Link Whole Card', 'modefilter-pro' ); ?></label></th>
                    <td>
                        <select id="modep_sc_link_whole_card" class="modep-select">
                            <option value="no"><?php esc_html_e( 'No', 'modefilter-pro' ); ?></option>
                            <option value="yes"><?php esc_html_e( 'Yes', 'modefilter-pro' ); ?></option>
                        </select>
                    </td>
                </tr>

            </table>

            <div class="modep-builder-actions" style="margin-top: 30px;">
                <button type="button" class="button button-primary button-large" id="modep_sc_build">
                    <?php esc_html_e( 'Build Shortcode', 'modefilter-pro' ); ?>
                </button>
                &nbsp;
                <button type="button" class="button button-large" id="modep_sc_copy">
                    <?php esc_html_e( 'Copy to Clipboard', 'modefilter-pro' ); ?>
                </button>
            </div>

            <hr class="modep-separator" />
            
            <h2 class="title"><?php esc_html_e( 'Generated Shortcode', 'modefilter-pro' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Click “Build Shortcode” to generate the string. Copy and paste it into any Gutenberg block, Elementor text widget, or classic editor.', 'modefilter-pro' ); ?></p>
            <textarea id="modep_sc_output" class="large-text code" rows="3" readonly="readonly" placeholder="[modep_filters ...]"></textarea>
        </div>
        <?php

        MODEP_Admin_UI::page_close();
    }
}
