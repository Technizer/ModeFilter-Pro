<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MODEP_Admin_Builder {

	public static function render() : void {

		MODEP_Admin_UI::page_open(
			__( 'Shortcode Builder', 'modefilter-pro' ),
			__( 'Choose options and generate a ready-to-paste shortcode.', 'modefilter-pro' )
		);
		MODEP_Admin_UI::tabs( 'builder' );
		?>
		<div class="modep-card modep-card-padded">
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

						<input type="hidden" id="modep_sc_ui" value="chips" />
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
						<input type="number" id="modep_sc_masonry_gap" min="0" max="200" value="20" class="modep-input" style="width:120px;" />
						<input type="number" id="modep_sc_justified_row_height" min="50" max="1200" value="220" class="modep-input" style="width:150px;" />
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

			<p>
				<button type="button" class="button button-primary" id="modep_sc_build"><?php esc_html_e( 'Build Shortcode', 'modefilter-pro' ); ?></button>
				&nbsp;<button type="button" class="button" id="modep_sc_copy"><?php esc_html_e( 'Copy', 'modefilter-pro' ); ?></button>
			</p>
		<?php

		// Output.
		echo '<hr class="modep-separator" />';
		echo '<h2 class="title">' . esc_html__( 'Generated shortcode', 'modefilter-pro' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Click “Build Shortcode” to generate the shortcode string, then copy and paste it into any page, post, widget, or template.', 'modefilter-pro' ) . '</p>';
		echo '<textarea id="modep_sc_output" class="large-text code" rows="3" readonly="readonly"></textarea>';
		echo '</div>';

		MODEP_Admin_UI::page_close();
	}
}
