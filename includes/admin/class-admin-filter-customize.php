<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ModeFilter Pro — Filter Customization Settings
 * Allows users to customize colors, spacing, and borders.
 */
final class MODEP_Admin_Filter_Customize {

	public static function register_settings() : void {
		register_setting( 'modep_customize', MODEP_Admin::OPT_UI, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize_customize' ],
			'default'           => [],
		] );

		add_settings_section( 'modep_customize_main', '', '__return_false', 'modep_customize' );
		add_settings_field( 'modep_customize_colors', '', [ __CLASS__, 'field_colors' ], 'modep_customize', 'modep_customize_main' );
		add_settings_field( 'modep_customize_spacing', '', [ __CLASS__, 'field_spacing' ], 'modep_customize', 'modep_customize_main' );
		add_settings_field( 'modep_customize_borders', '', [ __CLASS__, 'field_borders' ], 'modep_customize', 'modep_customize_main' );
	}

	public static function sanitize_customize( $input ) {
		$input = is_array( $input ) ? $input : [];

		$out = [
			'customize' => [
				'chip_bg'           => esc_hex_color( $input['customize']['chip_bg'] ?? '#ffffff' ),
				'chip_text'         => esc_hex_color( $input['customize']['chip_text'] ?? '#111827' ),
				'chip_active_bg'    => esc_hex_color( $input['customize']['chip_active_bg'] ?? '#0b66ff' ),
				'chip_active_text'  => esc_hex_color( $input['customize']['chip_active_text'] ?? '#ffffff' ),
				'chip_spacing'      => absint( $input['customize']['chip_spacing'] ?? 8 ),
				'chip_gap'          => absint( $input['customize']['chip_gap'] ?? 8 ),
				'chip_border_style' => sanitize_key( $input['customize']['chip_border_style'] ?? 'solid' ),
				'chip_border_width' => absint( $input['customize']['chip_border_width'] ?? 1 ),
				'chip_border_color' => esc_hex_color( $input['customize']['chip_border_color'] ?? '#e5e7eb' ),
				'chip_radius'       => absint( $input['customize']['chip_radius'] ?? 24 ),
			],
		];

		return $out;
	}

	public static function field_colors() : void {
		$opt  = (array) get_option( MODEP_Admin::OPT_UI, [] );
		$customize = isset( $opt['customize'] ) && is_array( $opt['customize'] ) ? $opt['customize'] : [];
		?>
		<div class="modep-form-section">
			<h2><?php esc_html_e( 'Filter Chip Colors', 'modefilter-pro' ); ?></h2>

			<div class="modep-filter-customize">
				<!-- Chip Background Color -->
				<div class="modep-color-group">
					<label><?php esc_html_e( 'Chip Background', 'modefilter-pro' ); ?></label>
					<input
						type="color"
						class="modep-color-picker"
						name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_bg]"
						value="<?php echo esc_attr( $customize['chip_bg'] ?? '#ffffff' ); ?>"
					/>
					<span class="description"><?php esc_html_e( 'Default chip background color', 'modefilter-pro' ); ?></span>
				</div>

				<!-- Chip Text Color -->
				<div class="modep-color-group">
					<label><?php esc_html_e( 'Chip Text', 'modefilter-pro' ); ?></label>
					<input
						type="color"
						class="modep-color-picker"
						name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_text]"
						value="<?php echo esc_attr( $customize['chip_text'] ?? '#111827' ); ?>"
					/>
					<span class="description"><?php esc_html_e( 'Default chip text color', 'modefilter-pro' ); ?></span>
				</div>

				<!-- Active Chip Background -->
				<div class="modep-color-group">
					<label><?php esc_html_e( 'Active Chip Background', 'modefilter-pro' ); ?></label>
					<input
						type="color"
						class="modep-color-picker"
						name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_active_bg]"
						value="<?php echo esc_attr( $customize['chip_active_bg'] ?? '#0b66ff' ); ?>"
					/>
					<span class="description"><?php esc_html_e( 'Selected chip background color', 'modefilter-pro' ); ?></span>
				</div>

				<!-- Active Chip Text -->
				<div class="modep-color-group">
					<label><?php esc_html_e( 'Active Chip Text', 'modefilter-pro' ); ?></label>
					<input
						type="color"
						class="modep-color-picker"
						name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_active_text]"
						value="<?php echo esc_attr( $customize['chip_active_text'] ?? '#ffffff' ); ?>"
					/>
					<span class="description"><?php esc_html_e( 'Selected chip text color', 'modefilter-pro' ); ?></span>
				</div>

				<!-- Border Color -->
				<div class="modep-color-group">
					<label><?php esc_html_e( 'Chip Border Color', 'modefilter-pro' ); ?></label>
					<input
						type="color"
						class="modep-color-picker"
						name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_border_color]"
						value="<?php echo esc_attr( $customize['chip_border_color'] ?? '#e5e7eb' ); ?>"
					/>
					<span class="description"><?php esc_html_e( 'Chip border color', 'modefilter-pro' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	public static function field_spacing() : void {
		$opt  = (array) get_option( MODEP_Admin::OPT_UI, [] );
		$customize = isset( $opt['customize'] ) && is_array( $opt['customize'] ) ? $opt['customize'] : [];
		?>
		<div class="modep-form-section">
			<h2><?php esc_html_e( 'Filter Chip Spacing & Sizing', 'modefilter-pro' ); ?></h2>

			<div class="modep-form-grid">
				<!-- Chip Padding -->
				<label>
					<strong><?php esc_html_e( 'Chip Padding', 'modefilter-pro' ); ?></strong>
					<div class="modep-spacing-control">
						<input
							type="range"
							min="4"
							max="20"
							step="1"
							name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_spacing]"
							value="<?php echo esc_attr( (string) ( $customize['chip_spacing'] ?? 8 ) ); ?>"
							class="modep-spacing-slider"
						/>
						<span class="modep-value"><?php echo esc_html( (string) ( $customize['chip_spacing'] ?? 8 ) ); ?>px</span>
					</div>
					<span class="description"><?php esc_html_e( 'Inner padding of filter chips', 'modefilter-pro' ); ?></span>
				</label>

				<!-- Chip Gap -->
				<label>
					<strong><?php esc_html_e( 'Chip Gap', 'modefilter-pro' ); ?></strong>
					<div class="modep-spacing-control">
						<input
							type="range"
							min="4"
							max="24"
							step="1"
							name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_gap]"
							value="<?php echo esc_attr( (string) ( $customize['chip_gap'] ?? 8 ) ); ?>"
							class="modep-spacing-slider"
						/>
						<span class="modep-value"><?php echo esc_html( (string) ( $customize['chip_gap'] ?? 8 ) ); ?>px</span>
					</div>
					<span class="description"><?php esc_html_e( 'Space between filter chips', 'modefilter-pro' ); ?></span>
				</label>

				<!-- Border Radius -->
				<label>
					<strong><?php esc_html_e( 'Chip Border Radius', 'modefilter-pro' ); ?></strong>
					<div class="modep-spacing-control">
						<input
							type="range"
							min="2"
							max="32"
							step="1"
							name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_radius]"
							value="<?php echo esc_attr( (string) ( $customize['chip_radius'] ?? 24 ) ); ?>"
							class="modep-spacing-slider"
						/>
						<span class="modep-value"><?php echo esc_html( (string) ( $customize['chip_radius'] ?? 24 ) ); ?>px</span>
					</div>
					<span class="description"><?php esc_html_e( 'Corner roundness of chips', 'modefilter-pro' ); ?></span>
				</label>
			</div>
		</div>
		<?php
	}

	public static function field_borders() : void {
		$opt  = (array) get_option( MODEP_Admin::OPT_UI, [] );
		$customize = isset( $opt['customize'] ) && is_array( $opt['customize'] ) ? $opt['customize'] : [];
		?>
		<div class="modep-form-section">
			<h2><?php esc_html_e( 'Filter Chip Borders', 'modefilter-pro' ); ?></h2>

			<div class="modep-form-grid">
				<!-- Border Style -->
				<label>
					<strong><?php esc_html_e( 'Border Style', 'modefilter-pro' ); ?></strong>
					<div class="modep-border-style">
						<label>
							<input
								type="radio"
								name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_border_style]"
								value="solid"
								<?php checked( $customize['chip_border_style'] ?? 'solid', 'solid' ); ?>
							/>
							<span><?php esc_html_e( 'Solid', 'modefilter-pro' ); ?></span>
						</label>
						<label>
							<input
								type="radio"
								name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_border_style]"
								value="dashed"
								<?php checked( $customize['chip_border_style'] ?? 'solid', 'dashed' ); ?>
							/>
							<span><?php esc_html_e( 'Dashed', 'modefilter-pro' ); ?></span>
						</label>
						<label>
							<input
								type="radio"
								name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_border_style]"
								value="none"
								<?php checked( $customize['chip_border_style'] ?? 'solid', 'none' ); ?>
							/>
							<span><?php esc_html_e( 'None', 'modefilter-pro' ); ?></span>
						</label>
					</div>
				</label>

				<!-- Border Width -->
				<label>
					<strong><?php esc_html_e( 'Border Width', 'modefilter-pro' ); ?></strong>
					<div class="modep-spacing-control">
						<input
							type="range"
							min="0"
							max="4"
							step="1"
							name="<?php echo esc_attr( MODEP_Admin::OPT_UI ); ?>[customize][chip_border_width]"
							value="<?php echo esc_attr( (string) ( $customize['chip_border_width'] ?? 1 ) ); ?>"
							class="modep-spacing-slider"
						/>
						<span class="modep-value"><?php echo esc_html( (string) ( $customize['chip_border_width'] ?? 1 ) ); ?>px</span>
					</div>
				</label>
			</div>

			<div class="modep-form-grid">
				<div class="modep-preview-box">
					<span>Filter Chip Preview</span>
				</div>
			</div>
		</div>
		<?php
	}
}
