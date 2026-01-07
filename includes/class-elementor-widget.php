<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Early exit check.
 * Note: Elementor registration is handled via class-plugin.php, 
 * but we keep this for direct-access safety.
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
 * MODEP_Elementor_Widget
 * Handles the Pro Product Grid and Faceted Search.
 */
class MODEP_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget Name/Slug.
	 */
	public function get_name() : string {
		return 'modep_filters';
	}

	/**
	 * Widget Display Title.
	 */
	public function get_title() : string {
		return __( 'ModeFilter Products', 'modefilter-pro' );
	}

	/**
	 * Widget Icon.
	 */
	public function get_icon() : string {
		return 'eicon-filter';
	}

	/**
	 * Categories where the widget appears.
	 */
	public function get_categories() : array {
		return [ 'modefilter-pro' ];
	}

	/**
	 * Scripts the widget depends on.
	 */
	public function get_script_depends() : array {
		return [ 'modep-js' ];
	}

	/**
	 * Styles the widget depends on.
	 */
	public function get_style_depends() : array {
		return [ 'modep-style' ];
	}

	/**
	 * Register All Control Sections.
	 */
	protected function register_controls() : void {

		// Content Tab Sections
		$this->register_section_query_controls();
		$this->register_section_grid_layout_controls();
		$this->register_section_layout_controls();
		$this->register_section_filters_controls();

		// Style Tab Sections
		$this->register_style_filters_controls();
		$this->register_style_sort_controls();
		$this->register_style_grid_controls();
		$this->register_style_pagination_controls();
		$this->register_style_messages_controls();

		$this->register_style_card_controls();
		$this->register_style_title_controls();
		$this->register_style_price_controls();

		// Advanced Preset Styling
		$this->register_preset_style_sections();
	}

	/**
	 * SECTION: QUERY
	 * Defines which products enter the filter pool.
	 */
	protected function register_section_query_controls() : void {

		$this->start_controls_section(
			'section_query',
			[
				'label' => __( 'Query Settings', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
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
			'tag_in',
			[
				'label'       => __( 'Include Tags', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_tag' ),
			]
		);

		$this->add_control(
			'brand_in',
			[
				'label'       => __( 'Include Brands', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_brand' ),
			]
		);

		$this->add_control(
			'sellable_cat_slug',
			[
				'label'       => __( 'Filter Base Category (Slug)', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'e.g. sale-2025',
				'description' => __( 'Scope the entire filter system to products within this single category slug.', 'modefilter-pro' ),
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
				'default' => '',
				'options' => [
					''             => __( 'Default (Newest)', 'modefilter-pro' ),
					'price_asc'    => __( 'Price: Low to High', 'modefilter-pro' ),
					'price_desc'   => __( 'Price: High to Low', 'modefilter-pro' ),
					'in_stock'     => __( 'In Stock First', 'modefilter-pro' ),
					'preorder'     => __( 'Pre-Order Items', 'modefilter-pro' ),
					'out_of_stock' => __( 'Out of Stock', 'modefilter-pro' ),
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * SECTION: GRID LAYOUT
	 * Controls the visual distribution of cards.
	 */
	protected function register_section_grid_layout_controls() : void {

		$this->start_controls_section(
			'section_grid_layout',
			[
				'label' => __( 'Grid Layout', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
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
					'justified' => __( 'Justified (Variable Width)', 'modefilter-pro' ),
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

	/**
	 * SECTION: LAYOUT & UX
	 * Controls presets, pagination, and the custom part-ordering repeater.
	 */
	protected function register_section_layout_controls() : void {

		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout & UX', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'preset',
			[
				'label'   => __( 'Preset Style', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'normal',
				'options' => [
					'normal'  => __( 'Standard (Clean)', 'modefilter-pro' ),
					'overlay' => __( 'Image Overlay', 'modefilter-pro' ),
					'minimal' => __( 'Minimalist', 'modefilter-pro' ),
					'custom'  => __( 'Custom (Drag & Drop)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'pagination',
			[
				'label'   => __( 'Pagination Type', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'load_more',
				'options' => [
					'none'      => __( 'None', 'modefilter-pro' ),
					'load_more' => __( 'Load More Button', 'modefilter-pro' ),
					'numbers'   => __( 'Standard Numbers', 'modefilter-pro' ),
					'infinite'  => __( 'Infinite Scroll', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'load_more_text',
			[
				'label'     => __( 'Button Text', 'modefilter-pro' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Load more', 'modefilter-pro' ),
				'condition' => [ 'pagination' => 'load_more' ],
			]
		);

		$this->add_control(
			'link_whole_card',
			[
				'label'        => __( 'Link Entire Card', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'modefilter-pro' ),
				'label_off'    => __( 'No', 'modefilter-pro' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Wraps the whole product card in a link to the product page.', 'modefilter-pro' ),
			]
		);

		// Custom Parts Repeater
		$repeater = new Repeater();

		$repeater->add_control(
			'part',
			[
				'label'   => __( 'Component', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'image',
				'options' => [
					'badge'       => __( 'Stock/Status Badge', 'modefilter-pro' ),
					'image'       => __( 'Product Image', 'modefilter-pro' ),
					'title'       => __( 'Product Title', 'modefilter-pro' ),
					'price'       => __( 'Price Label', 'modefilter-pro' ),
					'add_to_cart' => __( 'Action Button', 'modefilter-pro' ),
					'paypal'      => __( 'PayPal Promo Banner', 'modefilter-pro' ),
					'excerpt'     => __( 'Short Description', 'modefilter-pro' ),
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
					[ 'part' => 'badge', 'visible' => 'yes' ],
					[ 'part' => 'image', 'visible' => 'yes' ],
					[ 'part' => 'title', 'visible' => 'yes' ],
					[ 'part' => 'price', 'visible' => 'yes' ],
					[ 'part' => 'add_to_cart', 'visible' => 'yes' ],
				],
				'title_field' => '{{{ part.charAt(0).toUpperCase() + part.slice(1).replace("_", " ") }}}',
				'condition'   => [ 'preset' => 'custom' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * SECTION: FILTERS
	 * Configures the front-facing faceted search sidebar/top bar.
	 */
	protected function register_section_filters_controls() : void {

		$this->start_controls_section(
			'section_filters',
			[
				'label' => __( 'Faceted Search (Filters)', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
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
				'options'     => [
					'categories' => __( 'Product Categories', 'modefilter-pro' ),
					'tags'       => __( 'Product Tags', 'modefilter-pro' ),
					'brands'     => __( 'Product Brands', 'modefilter-pro' ),
					'price'      => __( 'Price Slider', 'modefilter-pro' ),
					'rating'     => __( 'Average Rating', 'modefilter-pro' ),
				],
				'description' => __( 'In Manual mode, only selected filters appear. In Auto mode, these act as an override.', 'modefilter-pro' ),
			]
		);

		$this->add_control(
			'filter_position',
			[
				'label'   => __( 'Display Position', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left'  => __( 'Left Sidebar', 'modefilter-pro' ),
					'right' => __( 'Right Sidebar', 'modefilter-pro' ),
					'top'   => __( 'Horizontal Top Bar', 'modefilter-pro' ),
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
					'count' => __( 'Frequency (Count)', 'modefilter-pro' ),
					'name'  => __( 'Alphabetical (Name)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_show_more',
			[
				'label'        => __( 'Enable "Show More"', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'modefilter-pro' ),
				'label_off'    => __( 'Off', 'modefilter-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'exclude_cat',
			[
				'label'       => __( 'Exclude Categories', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_cat' ),
			]
		);

		$this->add_control(
			'exclude_tag',
			[
				'label'       => __( 'Exclude Tags', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_tag' ),
			]
		);

		$this->add_control(
			'exclude_brand',
			[
				'label'       => __( 'Exclude Brands', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_terms_safe( 'product_brand' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: FILTERS PANEL
	 * Controls the sidebar/topbar container, titles, and faceted search chips.
	 */
	protected function register_style_filters_controls() : void {

		// 1. Panel Container
		$this->start_controls_section(
			'section_style_filters_panel',
			[
				'label' => __( 'Filters Panel (Container)', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'filters_panel_bg',
				'selector' => '{{WRAPPER}} .modep-sidebar',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'filters_panel_border',
				'selector' => '{{WRAPPER}} .modep-sidebar',
			]
		);

		$this->add_responsive_control(
			'filters_panel_padding',
			[
				'label'      => __( 'Padding', 'modefilter-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .modep-sidebar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'filters_panel_gap',
			[
				'label'     => __( 'Space Between Blocks', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors' => [
					'{{WRAPPER}} .modep-sidebar' => 'gap: {{SIZE}}{{UNIT}}; display: flex; flex-direction: column;',
				],
			]
		);

		$this->end_controls_section();

		// 2. Filter Block Titles
		$this->start_controls_section(
			'section_style_filter_titles',
			[
				'label' => __( 'Filter Block Titles', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'filter_title_color',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .modep-filter-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'filter_title_typo',
				'selector' => '{{WRAPPER}} .modep-filter-title',
			]
		);

		$this->add_responsive_control(
			'filter_title_margin',
			[
				'label'     => __( 'Bottom Spacing', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [
					'{{WRAPPER}} .modep-filter-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// 3. Filter Chips (Facets)
		$this->start_controls_section(
			'section_style_filter_chips',
			[
				'label' => __( 'Filter Chips / Tags', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'chip_typo',
				'selector' => '{{WRAPPER}} .modep-chip',
			]
		);

		$this->add_responsive_control(
			'chip_spacing',
			[
				'label'     => __( 'Gap Between Chips', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
				'selectors' => [
					'{{WRAPPER}} .modep-chips' => 'gap: {{SIZE}}{{UNIT}}; display: flex; flex-wrap: wrap;',
				],
			]
		);

		$this->add_responsive_control(
			'chip_padding',
			[
				'label'      => __( 'Chip Padding', 'modefilter-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .modep-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'chip_radius',
			[
				'label'     => __( 'Border Radius', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
				'selectors' => [
					'{{WRAPPER}} .modep-chip' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_chip_states' );

		// Normal State
		$this->start_controls_tab( 'tab_chip_normal', [ 'label' => __( 'Normal', 'modefilter-pro' ) ] );
		$this->add_control( 'chip_text_color', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_bg', [
			'label'     => __( 'Background', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_border_color', [
			'label'     => __( 'Border Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip' => 'border-color: {{VALUE}}; border-style: solid; border-width: 1px;' ],
		] );
		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab( 'tab_chip_hover', [ 'label' => __( 'Hover', 'modefilter-pro' ) ] );
		$this->add_control( 'chip_text_color_hover', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_bg_hover', [
			'label'     => __( 'Background', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_border_color_hover', [
			'label'     => __( 'Border Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip:hover' => 'border-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();

		// Active State (Selected)
		$this->start_controls_tab( 'tab_chip_active', [ 'label' => __( 'Active', 'modefilter-pro' ) ] );
		$this->add_control( 'chip_text_color_active', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip.is-selected' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_bg_active', [
			'label'     => __( 'Background', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip.is-selected' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'chip_border_color_active', [
			'label'     => __( 'Border Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-chip.is-selected' => 'border-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();

		// 4. Show More / Less Toggle
		$this->start_controls_section(
			'section_style_filter_more',
			[
				'label' => __( 'Show More / Less', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'more_typo',
				'selector' => '{{WRAPPER}} .modep-terms-more',
			]
		);

		$this->add_control(
			'more_color',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-terms-more' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'more_color_hover',
			[
				'label'     => __( 'Hover Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-terms-more:hover' => 'color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: SORT BAR
	 * Controls the top/bottom sorting dropdown and its label.
	 */
	protected function register_style_sort_controls() : void {

		$this->start_controls_section(
			'section_style_sort',
			[
				'label' => __( 'Sort Bar', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'sort_bg',
				'selector' => '{{WRAPPER}} .modep-sort-bar',
			]
		);

		$this->add_responsive_control(
			'sort_padding',
			[
				'label'      => __( 'Padding', 'modefilter-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'selectors'  => [ '{{WRAPPER}} .modep-sort-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->add_control(
			'sort_label_color',
			[
				'label'     => __( 'Label Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-sort-bar label' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sort_label_typo',
				'selector' => '{{WRAPPER}} .modep-sort-bar label',
			]
		);

		$this->add_control(
			'sort_select_heading',
			[
				'label'     => __( 'Select Box', 'modefilter-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'sort_select_color',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-sort' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'sort_select_bg',
			[
				'label'     => __( 'Background Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-sort' => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sort_select_typo',
				'selector' => '{{WRAPPER}} .modep-sort',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: GRID
	 * Spacing between columns and rows.
	 */
	protected function register_style_grid_controls() : void {

		$this->start_controls_section(
			'section_style_grid',
			[
				'label' => __( 'Grid Spacing', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'grid_gap',
			[
				'label'     => __( 'Column Gap', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors' => [ '{{WRAPPER}} .modep-grid' => 'gap: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'grid_top_spacing',
			[
				'label'     => __( 'Grid Top Spacing', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors' => [ '{{WRAPPER}} .modep-grid' => 'margin-top: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: PAGINATION
	 * Controls the appearance of the Load More button or Numerical page links.
	 */
	protected function register_style_pagination_controls() : void {

		$this->start_controls_section(
			'section_style_pagination',
			[
				'label' => __( 'Pagination & Load More', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'pagination_spacing',
			[
				'label'     => __( 'Top Spacing', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
				'selectors' => [ '{{WRAPPER}} .modep-pagination' => 'margin-top: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_typo',
				'selector' => '{{WRAPPER}} .modep-pagination button, {{WRAPPER}} .modep-pagination .modep-page-btn',
			]
		);

		$this->start_controls_tabs( 'tabs_pagination_btn' );

		// Normal State
		$this->start_controls_tab( 'tab_pagi_normal', [ 'label' => __( 'Normal', 'modefilter-pro' ) ] );
		$this->add_control( 'pagi_color', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination button, {{WRAPPER}} .modep-pagination .modep-page-btn' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'pagi_bg', [
			'label'     => __( 'Background Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination button, {{WRAPPER}} .modep-pagination .modep-page-btn' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'pagi_border_color', [
			'label'     => __( 'Border Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination button, {{WRAPPER}} .modep-pagination .modep-page-btn' => 'border-color: {{VALUE}}; border-style: solid; border-width: 1px;' ],
		]);
		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab( 'tab_pagi_hover', [ 'label' => __( 'Hover', 'modefilter-pro' ) ] );
		$this->add_control( 'pagi_color_hover', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination button:hover, {{WRAPPER}} .modep-pagination .modep-page-btn:hover' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'pagi_bg_hover', [
			'label'     => __( 'Background Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination button:hover, {{WRAPPER}} .modep-pagination .modep-page-btn:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->end_controls_tab();

		// Active State (Current Page)
		$this->start_controls_tab( 'tab_pagi_active', [ 'label' => __( 'Active', 'modefilter-pro' ) ] );
		$this->add_control( 'pagi_active_color', [
			'label'     => __( 'Text Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination .modep-page-btn.is-active' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'pagi_active_bg', [
			'label'     => __( 'Background Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .modep-pagination .modep-page-btn.is-active' => 'background-color: {{VALUE}};' ],
		]);
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	/**
	 * STYLE: MESSAGES
	 * Empty results, loading indicators, and error messages.
	 */
	protected function register_style_messages_controls() : void {

		$this->start_controls_section(
			'section_style_messages',
			[
				'label' => __( 'Status Messages', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'msg_typo',
				'selector' => '{{WRAPPER}} .modep-no-products',
			]
		);

		$this->add_control(
			'msg_color',
			[
				'label'     => __( 'Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-no-products' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'msg_bg',
				'selector' => '{{WRAPPER}} .modep-no-products',
			]
		);

		$this->add_responsive_control(
			'msg_padding',
			[
				'label'      => __( 'Padding', 'modefilter-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [ '{{WRAPPER}} .modep-no-products' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->add_responsive_control(
			'msg_radius',
			[
				'label'     => __( 'Border Radius', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
				'selectors' => [ '{{WRAPPER}} .modep-no-products' => 'border-radius: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: PRODUCT CARD
	 * The main container for individual product items.
	 */
	protected function register_style_card_controls() : void {

		$this->start_controls_section(
			'section_style_card',
			[
				'label' => __( 'Product Card', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_bg',
				'selector' => '{{WRAPPER}} .modep-product-inner',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .modep-product-inner',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .modep-product-inner',
			]
		);

		$this->add_responsive_control(
			'card_radius',
			[
				'label'     => __( 'Border Radius', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
				'selectors' => [
					'{{WRAPPER}} .modep-product-inner' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;',
					'{{WRAPPER}} .modep-thumb-link img' => 'border-radius: calc({{SIZE}}{{UNIT}} - 1px);',
				],
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Inner Padding', 'modefilter-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [ '{{WRAPPER}} .modep-product-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * STYLE: PRODUCT TITLE
	 */
	protected function register_style_title_controls() : void {

		$this->start_controls_section(
			'section_style_title',
			[
				'label' => __( 'Product Title', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_title_style' );

		// Normal Title State
		$this->start_controls_tab( 'tab_title_normal', [ 'label' => __( 'Normal', 'modefilter-pro' ) ] );

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-title, {{WRAPPER}} .modep-title a' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typo',
				'selector' => '{{WRAPPER}} .modep-title, {{WRAPPER}} .modep-title a',
			]
		);

		$this->end_controls_tab();

		// Hover Title State
		$this->start_controls_tab( 'tab_title_hover', [ 'label' => __( 'Hover', 'modefilter-pro' ) ] );

		$this->add_control(
			'title_color_hover',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ 
					'{{WRAPPER}} .modep-product-inner:hover .modep-title',
					'{{WRAPPER}} .modep-title a:hover' => 'color: {{VALUE}};' 
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * STYLE: PRICE
	 */
	protected function register_style_price_controls() : void {

		$this->start_controls_section(
			'section_style_price',
			[
				'label' => __( 'Product Price', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'price_color',
			[
				'label'     => __( 'Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .modep-price' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_typo',
				'selector' => '{{WRAPPER}} .modep-price',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * PRESET STYLES
	 * Specific styles that only activate when a certain "Preset" is chosen.
	 */
	protected function register_preset_style_sections() : void {

		// 1. Preset: Normal (Classic Grid)
		$this->start_controls_section(
			'section_style_preset_normal',
			[
				'label'     => __( 'Preset: Standard Settings', 'modefilter-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'normal' ],
			]
		);

		$this->add_control(
			'normal_hover_lift',
			[
				'label'      => __( 'Hover Lift Effect (px)', 'modefilter-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
				'default'    => [ 'size' => 3 ],
				'selectors'  => [
					'{{WRAPPER}}.modep--preset-normal .modep-product-inner' => 'transition: transform 0.3s ease;',
					'{{WRAPPER}}.modep--preset-normal .modep-product-inner:hover' => 'transform: translateY(-{{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->end_controls_section();

		// 2. Preset: Overlay (Text over Image)
		$this->start_controls_section(
			'section_style_preset_overlay',
			[
				'label'     => __( 'Preset: Overlay Settings', 'modefilter-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'overlay' ],
			]
		);

		$this->add_control(
			'overlay_bg',
			[
				'label'     => __( 'Overlay Tint', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.7)',
				'selectors' => [ '{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'background: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'overlay_title_color',
			[
				'label'     => __( 'Overlay Text Color', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'color: {{VALUE}};' ],
			]
		);

		$this->add_responsive_control(
			'overlay_radius',
			[
				'label'     => __( 'Overlay Rounded Corners', 'modefilter-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
				'selectors' => [ '{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'border-radius: {{SIZE}}{{UNIT}};' ],
			]
		);

		$this->end_controls_section();

		// 3. Preset: Minimal
		$this->start_controls_section(
			'section_style_preset_minimal',
			[
				'label'     => __( 'Preset: Minimal Settings', 'modefilter-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'minimal' ],
			]
		);

		$this->add_control(
			'minimal_button_bg',
			[
				'label'     => __( 'Action Button BG', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => [ '{{WRAPPER}}.modep--preset-minimal .modep-add-to-cart .button' => 'background-color: {{VALUE}};' ],
			]
		);

		$this->add_control(
			'minimal_button_color',
			[
				'label'     => __( 'Action Button Text', 'modefilter-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [ '{{WRAPPER}}.modep--preset-minimal .modep-add-to-cart .button' => 'color: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();

		// 4. Preset: Custom Info
		$this->start_controls_section(
			'section_style_preset_custom',
			[
				'label'     => __( 'Preset: Custom Builder', 'modefilter-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'preset' => 'custom' ],
			]
		);

		$this->add_control(
			'custom_hint',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<strong>' . __( 'Custom Mode Active', 'modefilter-pro' ) . '</strong><br>' . 
						  __( 'The order and visibility of elements are managed via the "Builder: Order Components" repeater in the Layout tab.', 'modefilter-pro' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * RENDER: SELLABLE WIDGET
	 * Processes all Elementor settings and passes them to the core [modep_filters] shortcode.
	 */
	protected function render() : void {
		$s = $this->get_settings_for_display();

		// 1. Process Taxonomies (Inclusions)
		$cat   = ! empty( $s['cat_in'] )   ? implode( ',', array_map( 'intval', (array) $s['cat_in'] ) )   : '';
		$tag   = ! empty( $s['tag_in'] )   ? implode( ',', array_map( 'intval', (array) $s['tag_in'] ) )   : '';
		$brand = ! empty( $s['brand_in'] ) ? implode( ',', array_map( 'intval', (array) $s['brand_in'] ) ) : '';

		// 2. Process Taxonomies (Exclusions)
		$ex_cat   = ! empty( $s['exclude_cat'] )   ? implode( ',', array_map( 'intval', (array) $s['exclude_cat'] ) )   : '';
		$ex_tag   = ! empty( $s['exclude_tag'] )   ? implode( ',', array_map( 'intval', (array) $s['exclude_tag'] ) )   : '';
		$ex_brand = ! empty( $s['exclude_brand'] ) ? implode( ',', array_map( 'intval', (array) $s['exclude_brand'] ) ) : '';

		// 3. Enabled Filters CSV
		$filters_csv = '';
		if ( ! empty( $s['enabled_filters'] ) && is_array( $s['enabled_filters'] ) ) {
			$filters_csv = implode( ',', array_map( 'sanitize_key', $s['enabled_filters'] ) );
		}

		// 4. Custom Card Layout String
		// Format: part1|part2|!part3 (where ! denotes hidden)
		$custom_layout = '';
		if ( ( $s['preset'] ?? '' ) === 'custom' && ! empty( $s['custom_layout'] ) ) {
			$layout_parts = [];
			foreach ( (array) $s['custom_layout'] as $row ) {
				$part_key = sanitize_key( (string) ( $row['part'] ?? '' ) );
				if ( ! $part_key ) continue;

				$is_hidden = ( empty( $row['visible'] ) || $row['visible'] !== 'yes' );
				$layout_parts[] = ( $is_hidden ? '!' : '' ) . $part_key;
			}
			$custom_layout = implode( '|', $layout_parts );
		}

		// 5. Build Shortcode Attributes
		$attributes = [
			'columns'           => (int) ( $s['columns'] ?? 3 ),
			'per_page'          => (int) ( $s['per_page'] ?? 9 ),
			'sort'              => esc_attr( $s['sort'] ?? '' ),
			'cat_in'            => $cat,
			'tag_in'            => $tag,
			'brand_in'          => $brand,
			'pagination'        => esc_attr( $s['pagination'] ?? 'load_more' ),
			'load_more_text'    => esc_attr( $s['load_more_text'] ?? __( 'Load more', 'modefilter-pro' ) ),
			'preset'            => esc_attr( $s['preset'] ?? 'normal' ),
			'link_whole_card'   => ! empty( $s['link_whole_card'] ) ? 'yes' : 'no',
			'custom_layout'     => esc_attr( $custom_layout ),
			'filter_position'   => esc_attr( $s['filter_position'] ?? 'left' ),
			'filters_mode'      => esc_attr( $s['filters_mode'] ?? 'manual' ),
			'filters'           => $filters_csv,
			'terms_limit'       => (int) ( $s['terms_limit'] ?? 12 ),
			'terms_orderby'     => esc_attr( $s['terms_orderby'] ?? 'count' ),
			'terms_order'       => esc_attr( $s['terms_order'] ?? 'DESC' ),
			'terms_show_more'   => ! empty( $s['terms_show_more'] ) ? 'yes' : 'no',
			'exclude_cat'       => $ex_cat,
			'exclude_tag'       => $ex_tag,
			'exclude_brand'     => $ex_brand,
			'grid_layout'       => esc_attr( $s['grid_layout'] ?? 'grid' ),
			'masonry_gap'       => isset( $s['masonry_gap']['size'] ) ? (int) $s['masonry_gap']['size'] : '',
			'justified_height'  => (int) ( $s['justified_row_height'] ?? 250 ),
		];

		// Flatten attributes for shortcode string
		$attr_string = '';
		foreach ( $attributes as $key => $val ) {
			$attr_string .= sprintf( ' %s="%s"', $key, $val );
		}

		echo do_shortcode( '[modep_filters' . $attr_string . ']' );
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

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 200, // Safety limit for Pro stability
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = sprintf( '%s (ID: %d)', $term->name, $term->term_id );
		}

		return $options;
	}
}