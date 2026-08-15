<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Early exit check.
 */
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

/**
 * MODEP_Elementor_Widget_Catalog
 * Synchronized with MODEP_Elementor_Widget for Filters/Terms styling,
 * while preserving Catalog-specific Enquiry Button and Custom Builder features.
 */
class MODEP_Elementor_Widget_Catalog extends \Elementor\Widget_Base {

	public function get_name() : string {
		return 'modep_catalog';
	}

	public function get_title() : string {
		return __( 'ModeFilter Catalog Products', 'modefilter-pro' );
	}

	public function get_icon() : string {
		return 'eicon-products';
	}

	public function get_categories() : array {
		return [ 'modefilter-pro' ];
	}

	public function get_script_depends() : array {
		return [ 'modep-js' ];
	}

	public function get_style_depends() : array {
		return [ 'modep-style' ];
	}

	protected function register_controls() : void {
		// CONTENT TAB
		$this->register_section_query_controls();
		$this->register_section_grid_layout_controls();
		$this->register_section_layout_controls();
		$this->register_section_filters_controls();

		// STYLE TAB
		$this->register_style_filters_panel();
		$this->register_style_filter_chips();
		$this->register_style_grid_controls();
		$this->register_style_card_controls();
		$this->register_style_title_controls();
		$this->register_style_button_controls();
		$this->register_style_messages_controls();
	}

	/* -------------------------------------------------------------------------- */
	/* CONTENT SECTIONS                                                           */
	/* -------------------------------------------------------------------------- */

	protected function register_section_query_controls() : void {
		$this->start_controls_section(
			'section_query',
			[ 'label' => __( 'Query Settings', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_CONTENT ]
		);

		$this->add_control(
			'cat_in',
			[
				'label'       => __( 'Include Categories', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_cat' ),
			]
		);

		$this->add_control(
			'catalog_button_text',
			[
				'label'       => __( 'Catalog Button Text', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Enquire Now', 'modefilter-pro' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'modefilter-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 3,
			]
		);

		$this->add_control(
			'per_page',
			[
				'label'   => __( 'Products Per Page', 'modefilter-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'default' => 12,
			]
		);

		$this->add_control(
			'sort',
			[
				'label'   => __( 'Default Sorting', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu_order',
				'options' => [
					'menu_order' => __( 'Default', 'modefilter-pro' ),
					'date'       => __( 'Newest', 'modefilter-pro' ),
					'popularity' => __( 'Popularity', 'modefilter-pro' ),
					'rating'     => __( 'Rating', 'modefilter-pro' ),
					'rand'       => __( 'Random', 'modefilter-pro' ),
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_section_grid_layout_controls() : void {
		$this->start_controls_section(
			'section_grid_layout',
			[ 'label' => __( 'Grid Layout', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_CONTENT ]
		);

		$this->add_control(
			'grid_layout',
			[
				'label'   => __( 'Layout Mode', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid'      => __( 'Standard Grid', 'modefilter-pro' ),
					'masonry'   => __( 'Masonry', 'modefilter-pro' ),
					'justified' => __( 'Justified', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'masonry_gap',
			[
				'label'     => __( 'Masonry Gap', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
				'default'   => [ 'size' => 20 ],
				'condition' => [ 'grid_layout' => 'masonry' ],
			]
		);

		$this->add_control(
			'justified_row_height',
			[
				'label'     => __( 'Justified Row Height', 'modefilter-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 100,
				'max'       => 600,
				'default'   => 250,
				'condition' => [ 'grid_layout' => 'justified' ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_section_layout_controls() : void {
		$this->start_controls_section(
			'section_layout',
			[ 'label' => __( 'Layout & UX', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_CONTENT ]
		);

		$this->add_control(
			'preset',
			[
				'label'   => __( 'Preset Style', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'normal',
				'options' => [
					'normal'  => __( 'Standard', 'modefilter-pro' ),
					'overlay' => __( 'Overlay', 'modefilter-pro' ),
					'minimal' => __( 'Minimal', 'modefilter-pro' ),
					'custom'  => __( 'Custom (Drag & Drop)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'pagination',
			[
				'label'   => __( 'Pagination Type', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'numbers',
				'options' => [
					'none'      => __( 'None', 'modefilter-pro' ),
					'load_more' => __( 'Load More Button', 'modefilter-pro' ),
					'numbers'   => __( 'Standard Numbers', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'link_whole_card',
			[
				'label'        => __( 'Link Entire Card', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'part',
			[
				'label'   => __( 'Component', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'image',
				'options' => [
					'image'   => __( 'Product Image', 'modefilter-pro' ),
					'title'   => __( 'Product Title', 'modefilter-pro' ),
					'brand'   => __( 'Brand Label', 'modefilter-pro' ),
					'button'  => __( 'Enquiry Button', 'modefilter-pro' ),
					'badge'   => __( 'Status Badge', 'modefilter-pro' ),
					'excerpt' => __( 'Short Description', 'modefilter-pro' ),
				],
			]
		);

		$repeater->add_control(
			'visible',
			[
				'label'        => __( 'Show/Hide', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'custom_layout',
			[
				'label'       => __( 'Builder: Order Components', 'modefilter-pro' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[ 'part' => 'image', 'visible' => 'yes' ],
					[ 'part' => 'title', 'visible' => 'yes' ],
					[ 'part' => 'button', 'visible' => 'yes' ],
				],
				'title_field' => '{{{ part.charAt(0).toUpperCase() + part.slice(1) }}}',
				'condition'   => [ 'preset' => 'custom' ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_section_filters_controls() : void {
		$this->start_controls_section(
			'section_filters',
			[ 'label' => __( 'Faceted Search (Filters)', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_CONTENT ]
		);

		$this->add_control(
			'modep_template_kit',
			[
				'label'       => __( '🎨 Template Kit (v1.0.7+)', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'catalog',
				'options'     => [
					'none'      => __( '— Manual Design —', 'modefilter-pro' ),
					'classic'   => __( 'Classic Grid', 'modefilter-pro' ),
					'minimal'   => __( 'Minimal (2 Column)', 'modefilter-pro' ),
					'masonry'   => __( 'Masonry', 'modefilter-pro' ),
					'hierarchy' => __( 'Hierarchy Browser', 'modefilter-pro' ),
					'justified' => __( 'Justified Gallery', 'modefilter-pro' ),
					'catalog'   => __( 'Catalog Mode', 'modefilter-pro' ),
				],
				'description' => __( 'Applies the selected kit’s complete visual system while this widget continues to show catalog-mode products only.', 'modefilter-pro' ),
			]
		);

		$this->add_control(
			'filters_mode',
			[
				'label'   => __( 'Selection Mode', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => [
					'manual' => __( 'Manual (Choose below)', 'modefilter-pro' ),
					'auto'   => __( 'Auto (Smart detection)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'enabled_filters',
			[
				'label'       => __( 'Active Filters', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => array_merge( [
					'categories' => __( 'Product Categories', 'modefilter-pro' ),
					'tags'       => __( 'Product Tags', 'modefilter-pro' ),
					'brands'     => __( 'Product Brands', 'modefilter-pro' ),
					'rating'     => __( 'Average Rating', 'modefilter-pro' ),
				], class_exists( 'MODEP_Attributes' ) ? MODEP_Attributes::get_registered_attribute_taxonomies() : [] ),
				'condition'   => [ 'filters_mode' => 'manual' ],
			]
		);

		$this->add_control(
			'filter_style',
			[
				'label'   => __( 'Filter Style', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'global',
				'options' => [
					'global'       => __( 'Inherit Global Default', 'modefilter-pro' ),
					'chips'        => __( 'Modern Chips', 'modefilter-pro' ),
					'checkboxes'   => __( 'Checkboxes', 'modefilter-pro' ),
					'radios'       => __( 'Radio Buttons', 'modefilter-pro' ),
					'toggles'      => __( 'Toggle Switches', 'modefilter-pro' ),
					'hierarchical' => __( 'Hierarchical Category Tree', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'category_hierarchy',
			[
				'label'   => __( 'Category Hierarchy', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'global',
				'options' => [
					'global' => __( 'Inherit Global Default', 'modefilter-pro' ),
					'yes'    => __( 'Parent / Child Tree', 'modefilter-pro' ),
					'no'     => __( 'Flat Category List', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'loader_style',
			[
				'label'   => __( 'AJAX Loader', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'global',
				'options' => [
					'global'   => __( 'Inherit Global Default', 'modefilter-pro' ),
					'spinner'  => __( 'Spinning Loader', 'modefilter-pro' ),
					'skeleton' => __( 'Product Skeletons', 'modefilter-pro' ),
					'dots'     => __( 'Animated Dots', 'modefilter-pro' ),
					'pulse'    => __( 'Pulsing Ring', 'modefilter-pro' ),
					'custom'   => __( 'Custom Image / GIF / SVG', 'modefilter-pro' ),
					'none'     => __( 'No Visual Loader', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'loader_image',
			[
				'label'     => __( 'Custom Loader Image', 'modefilter-pro' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => [ 'loader_style' => 'custom' ],
			]
		);

		$this->add_control(
			'filter_position',
			[
				'label'   => __( 'Display Position', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left'  => __( 'Left', 'modefilter-pro' ),
					'right' => __( 'Right', 'modefilter-pro' ),
					'top'   => __( 'Top Bar', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_limit',
			[
				'label'   => __( 'Max Terms per Filter', 'modefilter-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'default' => 12,
			]
		);

		$this->add_control(
			'terms_orderby',
			[
				'label'   => __( 'Sort Terms By', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'count',
				'options' => [
					'count' => __( 'Frequency', 'modefilter-pro' ),
					'name'  => __( 'Alphabetical', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_show_more',
			[
				'label'        => __( 'Enable "Show More"', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	/* -------------------------------------------------------------------------- */
	/* STYLE SECTIONS                                                             */
	/* -------------------------------------------------------------------------- */

	protected function register_style_filters_panel() : void {
		$this->start_controls_section(
			's_filters',
			[ 'label' => __( 'Filter Sidebar', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 'name' => 'f_bg', 'selector' => '{{WRAPPER}} .modep-sidebar' ]
		);

		$this->end_controls_section();
	}

	protected function register_style_filter_chips() : void {
		$this->start_controls_section(
			'section_style_chips',
			[ 'label' => __( 'Filter Chips / Tags', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'chip_typo', 'selector' => '{{WRAPPER}} .modep-chip' ]
		);

		$this->start_controls_tabs( 'tabs_chip_style' );

		$this->start_controls_tab( 'tab_chip_n', [ 'label' => __( 'Normal', 'modefilter-pro' ) ] );
		$this->add_control(
			'chip_color',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-chip' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'chip_bg',
			[
				'label'     => __( 'Background', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-chip' => 'background-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_chip_active', [ 'label' => __( 'Active', 'modefilter-pro' ) ] );
		$this->add_control(
			'chip_color_a',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				// JS uses .is-selected for the active chip state.
				'selectors' => [ '{{WRAPPER}} .modep-chip.is-selected' => 'color: {{VALUE}};' ],
			]
		);
		$this->add_control(
			'chip_bg_a',
			[
				'label'     => __( 'Background', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-chip.is-selected' => 'background-color: {{VALUE}};' ],
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'chip_pad',
			[
				'label'     => __( 'Padding', 'modefilter-pro' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .modep-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'chip_spacing',
			[
				'label'     => __( 'Gap', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [
					// Filter chip groups render as .modep-chips.
					'{{WRAPPER}} .modep-chips' => 'gap: {{SIZE}}{{UNIT}}; display: flex; flex-wrap: wrap;',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_style_grid_controls() : void {
		$this->start_controls_section(
			's_grid',
			[ 'label' => __( 'Grid Spacing', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_responsive_control(
			'g_gap',
			[
				'label'     => __( 'Column Gap', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [ '{{WRAPPER}} .modep-grid' => 'gap: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_style_card_controls() : void {
		$this->start_controls_section(
			's_card',
			[ 'label' => __( 'Product Card', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[ 'name' => 'card_bg', 'selector' => '{{WRAPPER}} .modep-product-inner' ]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[ 'name' => 'c_border', 'selector' => '{{WRAPPER}} .modep-product-inner' ]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[ 'name' => 'c_shadow', 'selector' => '{{WRAPPER}} .modep-product-inner' ]
		);

		$this->end_controls_section();
	}

	protected function register_style_title_controls() : void {
		$this->start_controls_section(
			's_title',
			[ 'label' => __( 'Product Title', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_control(
			't_color',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-title' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 't_typo', 'selector' => '{{WRAPPER}} .modep-title' ]
		);

		$this->end_controls_section();
	}

	protected function register_style_button_controls() : void {
		$this->start_controls_section(
			'section_style_button',
			[ 'label' => __( 'Enquiry Button Style', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		// The enquiry button in the product template is rendered as `.modep-enquire-btn`.
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'btn_typo', 'selector' => '{{WRAPPER}} .modep-enquire-btn' ]
		);

		$this->start_controls_tabs( 'tabs_btn_style' );

		$this->start_controls_tab( 'tab_btn_normal', [ 'label' => __( 'Normal', 'modefilter-pro' ) ] );
		$this->add_control(
			'btn_color',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-enquire-btn' => 'color: {{VALUE}} !important;' ],
			]
		);

		// Use Elementor's background control so users can apply solid, image or gradient backgrounds.
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'btn_bg',
				'types'    => [ 'classic', 'gradient' ],
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'exclude'  => [ 'image' ], //Elementor UI, not WordPress DB
				'selector' => '{{WRAPPER}} .modep-enquire-btn',
			]
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_btn_hover', [ 'label' => __( 'Hover', 'modefilter-pro' ) ] );
		$this->add_control(
			'btn_color_hover',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-enquire-btn:hover' => 'color: {{VALUE}} !important;' ],
			]
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'btn_bg_hover',
				'types'    => [ 'classic', 'gradient' ],
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'exclude'  => [ 'image' ], //Elementor UI, not WordPress DB
				'selector' => '{{WRAPPER}} .modep-enquire-btn:hover',
			]
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_padding',
			[
				'label'     => __( 'Padding', 'modefilter-pro' ),
				'type'      => Controls_Manager::DIMENSIONS,
				'separator' => 'before',
				'selectors' => [
				'{{WRAPPER}} .modep-enquire-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[ 'name' => 'btn_border', 'selector' => '{{WRAPPER}} .modep-enquire-btn' ]
		);

		$this->add_control(
			'btn_radius',
			[
				'label'     => __( 'Border Radius', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => [ '{{WRAPPER}} .modep-enquire-btn' => 'border-radius: {{SIZE}}{{UNIT}} !important;' ],
			]
		);

		$this->end_controls_section();
	}

	protected function register_style_messages_controls() : void {
		$this->start_controls_section(
			's_msgs',
			[ 'label' => __( 'Status Messages', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[ 'name' => 'm_typo', 'selector' => '{{WRAPPER}} .modep-no-products' ]
		);

		$this->end_controls_section();
	}

	/* -------------------------------------------------------------------------- */
	/* RENDER                                                                     */
	/* -------------------------------------------------------------------------- */

	protected function render() : void {
		$s = $this->get_settings_for_display();

		$template_kit = class_exists( 'MODEP_Template_Kits' )
			? MODEP_Template_Kits::sanitize_kit_id( (string) ( $s['modep_template_kit'] ?? 'catalog' ) )
			: 'none';
		if ( 'none' !== $template_kit ) {
			$s = array_merge( $s, MODEP_Template_Kits::get_config( $template_kit ) );
		}

		// Pass-through: Include Categories.
		$cat = ! empty( $s['cat_in'] ) ? implode( ',', array_map( 'intval', (array) $s['cat_in'] ) ) : '';

		// Process Custom Layout String: part1|part2|!part3 (where ! denotes hidden)
		$custom_layout = '';
		if ( is_string( $s['custom_layout'] ?? null ) ) {
			$custom_layout = sanitize_text_field( $s['custom_layout'] );
		} elseif ( ( $s['preset'] ?? '' ) === 'custom' && ! empty( $s['custom_layout'] ) ) {
			$layout_parts = [];
			foreach ( (array) $s['custom_layout'] as $row ) {
				$part_key = sanitize_key( (string) ( $row['part'] ?? '' ) );
				if ( ! $part_key ) {
					continue;
				}
				$is_hidden = ( empty( $row['visible'] ) || $row['visible'] !== 'yes' );
				$layout_parts[] = ( $is_hidden ? '!' : '' ) . $part_key;
			}
			$custom_layout = implode( '|', $layout_parts );
		}

		// Filters CSV (manual only)
		$filters_csv = '';
		if ( ! empty( $s['enabled_filters'] ) && is_array( $s['enabled_filters'] ) ) {
			$filters_csv = implode( ',', array_map( 'sanitize_key', $s['enabled_filters'] ) );
		}

		$filters_mode = ( ! empty( $s['filters_mode'] ) && in_array( (string) $s['filters_mode'], [ 'manual', 'auto' ], true ) )
			? (string) $s['filters_mode']
			: 'manual';

		$attributes = [
			// Catalog wrapper contract.
			'template_kit'        => $template_kit,
			'cat_in'              => $cat,
			'catalog_button_text' => (string) ( $s['catalog_button_text'] ?? __( 'Enquire Now', 'modefilter-pro' ) ),

			// HARD FORCE: ensure the JS payload always contains these.
			'only_catalog'        => 'yes',
			'context'             => 'catalog',

			'columns'             => (string) ( (int) ( $s['columns'] ?? 3 ) ),
			'per_page'            => (string) ( (int) ( $s['per_page'] ?? 12 ) ),
			'sort'                => (string) ( $s['sort'] ?? 'menu_order' ),

			'preset'              => (string) ( $s['preset'] ?? 'normal' ),
			'custom_layout'       => (string) $custom_layout,
			'pagination'          => (string) ( $s['pagination'] ?? 'numbers' ),
			'link_whole_card'     => ( ! empty( $s['link_whole_card'] ) && 'yes' === (string) $s['link_whole_card'] ) ? 'yes' : 'no',

			// Filter architecture
			'filters_mode'        => $filters_mode,
			'filter_position'     => (string) ( $s['filter_position'] ?? 'left' ),
			'filter_style'        => (string) ( $s['filter_style'] ?? 'global' ),
			'category_hierarchy'  => (string) ( $s['category_hierarchy'] ?? 'global' ),
			'filters'             => (string) $filters_csv,
			'terms_limit'         => (string) ( (int) ( $s['terms_limit'] ?? 12 ) ),
			'terms_orderby'       => (string) ( $s['terms_orderby'] ?? 'count' ),
			'terms_show_more'     => ( ! empty( $s['terms_show_more'] ) && 'yes' === (string) $s['terms_show_more'] ) ? 'yes' : 'no',
			'loader_style'        => (string) ( $s['loader_style'] ?? 'global' ),
			'loader_image'        => ! empty( $s['loader_image']['url'] ) ? esc_url( (string) $s['loader_image']['url'] ) : '',

			// Grid layout modes
			'grid_layout'          => (string) ( $s['grid_layout'] ?? 'grid' ),
			'masonry_gap'          => (string) ( isset( $s['masonry_gap']['size'] )
				? (int) $s['masonry_gap']['size']
				: (int) ( $s['masonry_gap'] ?? 20 ) ),
			'justified_row_height' => (string) ( (int) ( $s['justified_row_height'] ?? 250 ) ),
		];

		$attr_string = '';
		foreach ( $attributes as $key => $val ) {
			$attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( (string) $val ) );
		}

		echo '<div class="modep-elementor-catalog-wrapper">';
		echo do_shortcode( '[modep_catalog' . $attr_string . ']' );
		echo '</div>';
	}

	/**
	 * HELPERS: GET TERMS SAFE
	 * Fetches taxonomy terms for Elementor dropdowns.
	 * Limited to 200 terms to prevent editor slowdown.
	 */
	protected function get_terms_safe( string $taxonomy ) : array {
		$options = [];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $options;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ (int) $term->term_id ] = $term->name;
		}

		return $options;
	}
}
