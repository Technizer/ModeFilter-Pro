<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Safe early exit if Elementor isn't available yet.
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

class MODEP_Elementor_Widget extends \Elementor\Widget_Base {

	/* ---------------------------------
	   Identity
	---------------------------------- */
	public function get_name() { return 'modep_filters'; }

	public function get_title() { return __( 'ModeFilter Products', 'modefilter-pro' ); }

	public function get_icon() { return 'eicon-filter'; }

	public function get_categories() { return [ 'modefilter-pro' ]; }

	public function get_script_depends() { return [ 'modep-js' ]; }

	public function get_style_depends() { return [ 'modep-style' ]; }

	/* ---------------------------------
	   Controls
	---------------------------------- */
	protected function register_controls() : void {

		$this->register_section_query_controls();

		// New: grid layout mode (Grid / Masonry / Justified)
		$this->register_section_grid_layout_controls();

		// Layout & UX (preset + pagination + card link + parts control)
		$this->register_section_layout_controls();

		// New: Filters architecture (manual/auto + opt-in)
		$this->register_section_filters_controls();

		// Style sections
		$this->register_style_filters_controls();
		$this->register_style_sort_controls();
		$this->register_style_grid_controls();
		$this->register_style_pagination_controls();
		$this->register_style_messages_controls();

		$this->register_style_card_controls();
		$this->register_style_title_controls();
		$this->register_style_price_controls();

		// Presets (Normal / Overlay / Minimal + custom hint)
		$this->register_preset_style_sections();
	}

	/* ============================================================
	   SECTION: QUERY
	============================================================ */
	protected function register_section_query_controls() : void {

		$this->start_controls_section(
			'section_query',
			[
				'label' => __( 'Query', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control( 'cat_in', [
			'label'       => __( 'Categories Include', 'modefilter-pro' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->terms_options( 'product_cat' ),
		] );

		$this->add_control( 'tag_in', [
			'label'       => __( 'Tags Include', 'modefilter-pro' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->terms_options( 'product_tag' ),
		] );

		$this->add_control( 'brand_in', [
			'label'       => __( 'Brands Include', 'modefilter-pro' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => taxonomy_exists( 'product_brand' ) ? $this->terms_options( 'product_brand' ) : [],
		] );

		$this->add_control( 'sellable_cat_slug', [
			'label'       => __( 'Sellable Base Category Slug', 'modefilter-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'bf-sale-2025',
			'description' => __( 'Optional: limits product pool to this category (slug).', 'modefilter-pro' ),
		] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns', 'modefilter-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 6,
			'step'    => 1,
			'default' => 3,
		] );

		$this->add_control( 'per_page', [
			'label'   => __( 'Products per Page', 'modefilter-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 60,
			'step'    => 1,
			'default' => 9,
		] );

		$this->add_control( 'sort', [
			'label'   => __( 'Default Sort', 'modefilter-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => [
				''             => __( 'Default', 'modefilter-pro' ),
				'price_asc'    => __( 'Price: Low to High', 'modefilter-pro' ),
				'price_desc'   => __( 'Price: High to Low', 'modefilter-pro' ),
				'in_stock'     => __( 'In Stock', 'modefilter-pro' ),
				'preorder'     => __( 'Pre-Order', 'modefilter-pro' ),
				'out_of_stock' => __( 'Out of Stock', 'modefilter-pro' ),
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   SECTION: GRID LAYOUT MODE (Content Tab)
	============================================================ */
	protected function register_section_grid_layout_controls() : void {

		$this->start_controls_section(
			'section_grid_layout',
			[
				'label' => __( 'Grid Layout', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control( 'grid_layout', [
			'label'   => __( 'Layout Mode', 'modefilter-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => [
				'grid'      => __( 'Grid', 'modefilter-pro' ),
				'masonry'   => __( 'Masonry', 'modefilter-pro' ),
				'justified' => __( 'Justified', 'modefilter-pro' ),
			],
		] );

		$this->add_control( 'masonry_gap', [
			'label'     => __( 'Masonry Gap', 'modefilter-pro' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'   => [ 'size' => 20 ],
			'condition' => [ 'grid_layout' => 'masonry' ],
		] );

		$this->add_control( 'justified_row_height', [
			'label'       => __( 'Justified Row Height', 'modefilter-pro' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 80,
			'max'         => 500,
			'step'        => 10,
			'default'     => 220,
			'condition'   => [ 'grid_layout' => 'justified' ],
			'description' => __( 'Used by JS to calculate rows (if enabled).', 'modefilter-pro' ),
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   SECTION: LAYOUT & UX (Preset + parts)
	============================================================ */
	protected function register_section_layout_controls() : void {

		$this->start_controls_section(
			'section_layout',
			[
				'label' => __( 'Layout & UX', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control( 'preset', [
			'label'   => __( 'Preset Style', 'modefilter-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'normal',
			'options' => [
				'normal'  => __( 'Normal', 'modefilter-pro' ),
				'overlay' => __( 'Overlay', 'modefilter-pro' ),
				'minimal' => __( 'Minimal', 'modefilter-pro' ),
				'custom'  => __( 'Custom (drag to order)', 'modefilter-pro' ),
			],
		] );

		$this->add_control( 'pagination', [
			'label'   => __( 'Pagination', 'modefilter-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'load_more',
			'options' => [
				'none'      => __( 'None', 'modefilter-pro' ),
				'load_more' => __( 'Load More', 'modefilter-pro' ),
				'numbers'   => __( 'Numbers', 'modefilter-pro' ),
				'infinite'  => __( 'Infinite Scroll', 'modefilter-pro' ),
			],
		] );

		$this->add_control( 'load_more_text', [
			'label'       => __( 'Load More Text', 'modefilter-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Load more', 'modefilter-pro' ),
			'condition'   => [ 'pagination' => 'load_more' ],
			'label_block' => true,
		] );

		$this->add_control( 'link_whole_card', [
			'label'        => __( 'Link Whole Card', 'modefilter-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'modefilter-pro' ),
			'label_off'    => __( 'No', 'modefilter-pro' ),
			'return_value' => 'yes',
			'default'      => '',
		] );

		// Parts (drag + show/hide)
		$repeater = new Repeater();

		$repeater->add_control('part', [
			'label'   => __('Part','modefilter-pro'),
			'type'    => Controls_Manager::SELECT,
			'default' => 'image',
			'options' => [
				'badge'       => __('Stock Badge','modefilter-pro'),
				'image'       => __('Featured Image','modefilter-pro'),
				'title'       => __('Title','modefilter-pro'),
				'price'       => __('Price','modefilter-pro'),
				'add_to_cart' => __('Add To Cart','modefilter-pro'),
				'paypal'      => __('PayPal Banner','modefilter-pro'),
				'excerpt'     => __('Short Description','modefilter-pro'),
			],
		]);

		$repeater->add_control('visible', [
			'label'        => __('Visible','modefilter-pro'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->add_control('custom_layout', [
			'label'       => __('Custom Card Layout (Drag to Order)','modefilter-pro'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[ 'part'=>'badge', 'visible'=>'yes' ],
				[ 'part'=>'image', 'visible'=>'yes' ],
				[ 'part'=>'title', 'visible'=>'yes' ],
				[ 'part'=>'price', 'visible'=>'yes' ],
				[ 'part'=>'add_to_cart', 'visible'=>'yes' ],
				[ 'part'=>'paypal', 'visible'=>'' ],
			],
			'title_field' => '{{{ part }}}',
			'condition'   => [ 'preset' => 'custom' ],
		]);

		$this->end_controls_section();
	}

	/* ============================================================
	   SECTION: FILTERS (Pro architecture)
	============================================================ */
	protected function register_section_filters_controls() : void {

		$this->start_controls_section('section_filters', [
			'label' => __('Filters','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_CONTENT,
		]);

		$this->add_control('filters_mode', [
			'label'   => __('Mode','modefilter-pro'),
			'type'    => Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => [
				'manual' => __('Manual (opt-in)','modefilter-pro'),
				'auto'   => __('Auto-detect (only what exists)','modefilter-pro'),
			],
		]);

		$this->add_control('filters', [
			'label'       => __('Enabled Filters','modefilter-pro'),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'default'     => [],
			'options'     => [
				'categories' => __('Categories','modefilter-pro'),
				'tags'       => __('Tags','modefilter-pro'),
				'brands'     => __('Brands','modefilter-pro'),
				'price'      => __('Price','modefilter-pro'),
				'rating'     => __('Rating','modefilter-pro'),
			],
			'description' => __('Leave empty to show no filters. In Auto mode, empty means “show whatever exists”.', 'modefilter-pro'),
		]);

		$this->add_control('terms_limit', [
			'label'   => __('Terms Limit','modefilter-pro'),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'max'     => 200,
			'step'    => 1,
			'default' => 12,
		]);

		$this->add_control('terms_orderby', [
			'label'   => __('Order By','modefilter-pro'),
			'type'    => Controls_Manager::SELECT,
			'default' => 'count',
			'options' => [
				'count' => __('Count','modefilter-pro'),
				'name'  => __('Name','modefilter-pro'),
			],
		]);

		$this->add_control('terms_order', [
			'label'   => __('Order','modefilter-pro'),
			'type'    => Controls_Manager::SELECT,
			'default' => 'DESC',
			'options' => [
				'DESC' => __('DESC','modefilter-pro'),
				'ASC'  => __('ASC','modefilter-pro'),
			],
		]);

		$this->add_control('terms_show_more', [
			'label'        => __('Show More Toggle','modefilter-pro'),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->add_control('filter_position', [
			'label'   => __('Position','modefilter-pro'),
			'type'    => Controls_Manager::SELECT,
			'default' => 'left',
			'options' => [
				'left'  => __('Left Sidebar','modefilter-pro'),
				'top'   => __('Top Row','modefilter-pro'),
				'right' => __('Right Sidebar','modefilter-pro'),
			],
		]);

		$this->add_control('exclude_cat', [
			'label'       => __('Exclude Categories','modefilter-pro'),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->terms_options('product_cat'),
		]);

		$this->add_control('exclude_tag', [
			'label'       => __('Exclude Tags','modefilter-pro'),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->terms_options('product_tag'),
		]);

		$this->add_control('exclude_brand', [
			'label'       => __('Exclude Brands','modefilter-pro'),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => taxonomy_exists('product_brand') ? $this->terms_options('product_brand') : [],
		]);

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: FILTERS PANEL + TITLES + CHIPS + MORE + TOGGLE
	============================================================ */
	protected function register_style_filters_controls() : void {

		// Filters panel
		$this->start_controls_section('section_style_filters_panel', [
			'label' => __('Filters Panel','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'filters_panel_bg',
			'selector' => '{{WRAPPER}} .modep-sidebar',
		] );

		$this->add_group_control( Group_Control_Border::get_type(), [
			'name'     => 'filters_panel_border',
			'selector' => '{{WRAPPER}} .modep-sidebar',
		] );

		$this->add_responsive_control( 'filters_panel_padding', [
			'label'      => __( 'Padding', 'modefilter-pro' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .modep-sidebar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control( 'filters_panel_gap', [
			'label' => __('Space Between Filter Blocks','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-sidebar' => 'gap: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// Filter titles
		$this->start_controls_section('section_style_filter_titles', [
			'label' => __('Filter Titles','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'filter_title_typo',
			'selector' => '{{WRAPPER}} .modep-filter-title',
		] );

		$this->add_control( 'filter_title_color', [
			'label' => __('Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-filter-title' => 'color: {{VALUE}};',
			],
		] );

		$this->add_responsive_control( 'filter_title_margin', [
			'label' => __('Title Bottom Spacing','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-filter-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// Chips
		$this->start_controls_section('section_style_filter_chips', [
			'label' => __('Filter Chips','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'chip_typo',
			'selector' => '{{WRAPPER}} .modep-chip',
		] );

		$this->add_responsive_control( 'chip_spacing', [
			'label' => __('Chip Gap','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-chips' => 'gap: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control( 'chip_padding', [
			'label'      => __('Chip Padding','modefilter-pro'),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .modep-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control( 'chip_radius', [
			'label' => __('Chip Border Radius','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-chip' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->start_controls_tabs('tabs_chip_states');

		$this->start_controls_tab('tab_chip_normal', [ 'label' => __('Normal','modefilter-pro') ] );

		$this->add_control('chip_text_color', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_bg', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_border_color', [
			'label' => __('Border Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip' => 'border-color: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab('tab_chip_hover', [ 'label' => __('Hover','modefilter-pro') ] );

		$this->add_control('chip_text_color_hover', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip:hover' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_bg_hover', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip:hover' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_border_color_hover', [
			'label' => __('Border Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip:hover' => 'border-color: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab('tab_chip_active', [ 'label' => __('Active','modefilter-pro') ] );

		$this->add_control('chip_text_color_active', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip.is-selected' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_bg_active', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip.is-selected' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control('chip_border_color_active', [
			'label' => __('Border Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-chip.is-selected' => 'border-color: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();

		// Show more / less
		$this->start_controls_section('section_style_filter_more', [
			'label' => __('Show More/Less Button','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'more_typo',
			'selector' => '{{WRAPPER}} .modep-terms-more',
		] );

		$this->add_control('more_color', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-terms-more' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('more_color_hover', [
			'label' => __('Hover Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-terms-more:hover' => 'color: {{VALUE}};',
			],
		] );

		$this->end_controls_section();

		// Filters toggle button (mobile)
		$this->start_controls_section('section_style_filter_toggle', [
			'label' => __('Filters Toggle Button','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'toggle_typo',
			'selector' => '{{WRAPPER}} .modep-toggle-btn',
		] );

		$this->add_control('toggle_color', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-toggle-btn' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('toggle_bg', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-toggle-btn' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_control('toggle_border_color', [
			'label' => __('Border Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-toggle-btn' => 'border-color: {{VALUE}};',
			],
		] );

		$this->add_responsive_control('toggle_radius', [
			'label' => __('Border Radius','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-toggle-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: SORT BAR
	============================================================ */
	protected function register_style_sort_controls() : void {

		$this->start_controls_section('section_style_sort', [
			'label' => __('Sort Bar','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'sort_bg',
			'selector' => '{{WRAPPER}} .modep-sort-bar',
		] );

		$this->add_responsive_control('sort_padding', [
			'label' => __('Padding','modefilter-pro'),
			'type'  => Controls_Manager::DIMENSIONS,
			'selectors' => [
				'{{WRAPPER}} .modep-sort-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_control('sort_label_color', [
			'label' => __('Label Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-sort-bar label' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'sort_label_typo',
			'selector' => '{{WRAPPER}} .modep-sort-bar label',
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'sort_select_typo',
			'selector' => '{{WRAPPER}} .modep-sort',
		] );

		$this->add_control('sort_select_color', [
			'label' => __('Select Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-sort' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control('sort_select_bg', [
			'label' => __('Select Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-sort' => 'background-color: {{VALUE}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: GRID
	============================================================ */
	protected function register_style_grid_controls() : void {

		$this->start_controls_section('section_style_grid', [
			'label' => __('Grid','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_responsive_control('grid_gap', [
			'label' => __('Grid Gap','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-grid' => 'gap: {{SIZE}}{{UNIT}};',
			],
		]);

		$this->add_responsive_control('grid_top_spacing', [
			'label' => __('Top Spacing','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-grid' => 'margin-top: {{SIZE}}{{UNIT}};',
			],
		]);

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: PAGINATION
	============================================================ */
	protected function register_style_pagination_controls() : void {

		$this->start_controls_section('section_style_pagination', [
			'label' => __('Pagination','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_responsive_control('pagination_spacing', [
			'label' => __('Top Spacing','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
			],
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'pagination_typo',
			'selector' => '{{WRAPPER}} .modep-pagination button',
		] );

		$this->start_controls_tabs('tabs_pagination_btn');

		$this->start_controls_tab('tab_pagi_normal', [ 'label' => __('Normal','modefilter-pro') ] );

		$this->add_control('pagi_color', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination button' => 'color: {{VALUE}};',
			],
		]);

		$this->add_control('pagi_bg', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination button' => 'background-color: {{VALUE}};',
			],
		]);

		$this->add_control('pagi_border', [
			'label' => __('Border Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination button' => 'border-color: {{VALUE}};',
			],
		]);

		$this->end_controls_tab();

		$this->start_controls_tab('tab_pagi_hover', [ 'label' => __('Hover','modefilter-pro') ] );

		$this->add_control('pagi_color_hover', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination button:hover' => 'color: {{VALUE}};',
			],
		]);

		$this->add_control('pagi_bg_hover', [
			'label' => __('Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination button:hover' => 'background-color: {{VALUE}};',
			],
		]);

		$this->end_controls_tab();

		$this->start_controls_tab('tab_pagi_active', [ 'label' => __('Active','modefilter-pro') ] );

		$this->add_control('pagi_active_bg', [
			'label' => __('Active Background','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination .modep-page-btn.is-active' => 'background-color: {{VALUE}};',
			],
		]);

		$this->add_control('pagi_active_color', [
			'label' => __('Active Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-pagination .modep-page-btn.is-active' => 'color: {{VALUE}};',
			],
		]);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: MESSAGES (no products / loading etc)
	============================================================ */
	protected function register_style_messages_controls() : void {

		$this->start_controls_section('section_style_messages', [
			'label' => __('Messages','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'msg_typo',
			'selector' => '{{WRAPPER}} .modep-no-products',
		] );

		$this->add_control('msg_color', [
			'label' => __('Text Color','modefilter-pro'),
			'type'  => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-no-products' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'msg_bg',
			'selector' => '{{WRAPPER}} .modep-no-products',
		] );

		$this->add_responsive_control('msg_padding', [
			'label' => __('Padding','modefilter-pro'),
			'type'  => Controls_Manager::DIMENSIONS,
			'selectors' => [
				'{{WRAPPER}} .modep-no-products' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control('msg_radius', [
			'label' => __('Border Radius','modefilter-pro'),
			'type'  => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors' => [
				'{{WRAPPER}} .modep-no-products' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: CARD
	============================================================ */
	protected function register_style_card_controls() : void {

		$this->start_controls_section(
			'section_style_card',
			[
				'label' => __( 'Card', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'card_bg',
			'selector' => '{{WRAPPER}} .modep-product-inner',
		] );

		$this->add_group_control( Group_Control_Border::get_type(), [
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .modep-product-inner',
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .modep-product-inner',
		] );

		$this->add_responsive_control( 'card_radius', [
			'label'      => __( 'Border Radius', 'modefilter-pro' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors'  => [
				'{{WRAPPER}} .modep-product-inner'  => 'border-radius: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .modep-thumb-link img' => 'border-radius: calc({{SIZE}}{{UNIT}} - 2px);',
			],
		] );

		$this->add_responsive_control( 'card_padding', [
			'label'      => __( 'Padding', 'modefilter-pro' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .modep-product-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: TITLE
	============================================================ */
	protected function register_style_title_controls() : void {

		$this->start_controls_section(
			'section_style_title',
			[
				'label' => __( 'Product Title', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'title_typo',
			'selector' => '{{WRAPPER}} .modep-title, {{WRAPPER}} .modep-title a',
		] );

		$this->add_control( 'title_color', [
			'label'     => __( 'Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-title, {{WRAPPER}} .modep-title a' => 'color: {{VALUE}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   STYLE: PRICE
	============================================================ */
	protected function register_style_price_controls() : void {

		$this->start_controls_section(
			'section_style_price',
			[
				'label' => __( 'Price', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'price_typo',
			'selector' => '{{WRAPPER}} .modep-price',
		] );

		$this->add_control( 'price_color', [
			'label'     => __( 'Color', 'modefilter-pro' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .modep-price' => 'color: {{VALUE}};',
			],
		] );

		$this->end_controls_section();
	}

	/* ============================================================
	   PRESET STYLES: Normal / Overlay / Minimal + Custom hint
	============================================================ */
	protected function register_preset_style_sections() : void {

		// Normal
		$this->start_controls_section('section_style_preset_normal', [
			'label' => __('Preset: Normal','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
			'condition' => [ 'preset' => 'normal' ],
		]);
		$this->add_control('normal_hover_lift', [
			'label'        => __('Hover Lift','modefilter-pro'),
			'type'         => Controls_Manager::SLIDER,
			'size_units'   => [ 'px' ],
			'range'        => [ 'px' => [ 'min' => 0, 'max' => 12 ] ],
			'default'      => [ 'size' => 2 ],
			'selectors'    => [
				'{{WRAPPER}}.modep--preset-normal .modep-product-inner:hover' => 'transform: translateY(-{{SIZE}}{{UNIT}});',
			],
		]);
		$this->end_controls_section();

		// Overlay
		$this->start_controls_section('section_style_preset_overlay', [
			'label' => __('Preset: Overlay','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
			'condition' => [ 'preset' => 'overlay' ],
		]);
		$this->add_control('overlay_title_color', [
			'label'     => __('Title Color','modefilter-pro'),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [
				'{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'color: {{VALUE}};',
			],
		]);
		$this->add_control('overlay_bg', [
			'label'     => __('Overlay Background','modefilter-pro'),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(17,17,17,0.7)',
			'selectors' => [
				'{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'background: {{VALUE}};',
			],
		]);
		$this->add_responsive_control( 'overlay_radius', [
			'label'      => __( 'Overlay Radius', 'modefilter-pro' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors'  => [
				'{{WRAPPER}}.modep--preset-overlay .modep-thumb-link:after' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		]);
		$this->end_controls_section();

		// Minimal
		$this->start_controls_section('section_style_preset_minimal', [
			'label' => __('Preset: Minimal','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
			'condition' => [ 'preset' => 'minimal' ],
		]);
		$this->add_control('minimal_button_bg', [
			'label'     => __('Button Background','modefilter-pro'),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#2b2b2b',
			'selectors' => [
				'{{WRAPPER}}.modep--preset-minimal .modep-add-to-cart .button' => 'background: {{VALUE}};',
			],
		]);
		$this->add_control('minimal_button_color', [
			'label'     => __('Button Text','modefilter-pro'),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}}.modep--preset-minimal .modep-add-to-cart .button' => 'color: {{VALUE}};',
			],
		]);
		$this->end_controls_section();

		// Custom hint
		$this->start_controls_section('section_style_preset_custom', [
			'label' => __('Preset: Custom','modefilter-pro'),
			'tab'   => Controls_Manager::TAB_STYLE,
			'condition' => [ 'preset' => 'custom' ],
		]);
		$this->add_control('custom_hint', [
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => __('Use the <b>Custom Card Layout</b> repeater in the Layout section to choose parts and order.', 'modefilter-pro'),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		]);
		$this->end_controls_section();
	}

	/* ============================================================
	   Render (Sellable widget)
	============================================================ */
	protected function render() : void {

		$s = $this->get_settings_for_display();

		$cat   = ! empty( $s['cat_in'] )   ? implode( ',', array_map( 'intval', (array) $s['cat_in'] ) )   : '';
		$tag   = ! empty( $s['tag_in'] )   ? implode( ',', array_map( 'intval', (array) $s['tag_in'] ) )   : '';
		$brand = ! empty( $s['brand_in'] ) ? implode( ',', array_map( 'intval', (array) $s['brand_in'] ) ) : '';

		$ex_cat   = ! empty( $s['exclude_cat'] )   ? implode( ',', array_map( 'intval', (array) $s['exclude_cat'] ) )   : '';
		$ex_tag   = ! empty( $s['exclude_tag'] )   ? implode( ',', array_map( 'intval', (array) $s['exclude_tag'] ) )   : '';
		$ex_brand = ! empty( $s['exclude_brand'] ) ? implode( ',', array_map( 'intval', (array) $s['exclude_brand'] ) ) : '';

		// filters[] (array) -> csv
		$filters_csv = '';
		if ( ! empty( $s['filters'] ) && is_array( $s['filters'] ) ) {
			$filters_csv = implode( ',', array_map( 'sanitize_key', (array) $s['filters'] ) );
		}

		// Custom layout string
		$custom_layout = '';
		if ( ( $s['preset'] ?? '' ) === 'custom' && ! empty( $s['custom_layout'] ) ) {
			$parts = [];
			foreach ( (array) $s['custom_layout'] as $row ) {
				$flag    = ( ! empty( $row['visible'] ) && $row['visible'] === 'yes' ) ? '' : '!';
				$partkey = sanitize_key( (string) ( $row['part'] ?? '' ) );
				if ( $partkey ) {
					$parts[] = $flag . $partkey;
				}
			}
			$custom_layout = implode( '|', array_filter( $parts ) );
		}

		// Grid layout mode
		$grid_layout = sanitize_key( (string) ( $s['grid_layout'] ?? 'grid' ) );
		$masonry_gap = isset( $s['masonry_gap']['size'] ) ? (string) $s['masonry_gap']['size'] : '';
		$justified_h = (string) ( $s['justified_row_height'] ?? '' );

		echo do_shortcode( sprintf(
			'[modep_filters columns="%d" per_page="%d" sort="%s" cat_in="%s" tag_in="%s" brand_in="%s" sellable_cat_slug="%s" pagination="%s" load_more_text="%s" preset="%s" link_whole_card="%s" custom_layout="%s" filter_ui="chips" filter_position="%s" filters_mode="%s" filters="%s" terms_limit="%d" terms_orderby="%s" terms_order="%s" terms_show_more="%s" exclude_cat="%s" exclude_tag="%s" exclude_brand="%s" grid_layout="%s" masonry_gap="%s" justified_row_height="%s"]',
			(int) ( $s['columns'] ?? 3 ),
			(int) ( $s['per_page'] ?? 9 ),
			esc_attr( (string) ( $s['sort'] ?? '' ) ),
			esc_attr( $cat ),
			esc_attr( $tag ),
			esc_attr( $brand ),
			esc_attr( (string) ( $s['sellable_cat_slug'] ?? '' ) ),
			esc_attr( (string) ( $s['pagination'] ?? 'load_more' ) ),
			esc_attr( (string) ( $s['load_more_text'] ?? __( 'Load more', 'modefilter-pro' ) ) ),
			esc_attr( (string) ( $s['preset'] ?? 'normal' ) ),
			! empty( $s['link_whole_card'] ) ? 'yes' : 'no',
			esc_attr( $custom_layout ),

			// Pro locked UI
			esc_attr( (string) ( $s['filter_position'] ?? 'left' ) ),

			// New architecture params
			esc_attr( (string) ( $s['filters_mode'] ?? 'manual' ) ),
			esc_attr( $filters_csv ),
			(int) ( $s['terms_limit'] ?? 12 ),
			esc_attr( (string) ( $s['terms_orderby'] ?? 'count' ) ),
			esc_attr( (string) ( $s['terms_order'] ?? 'DESC' ) ),
			! empty( $s['terms_show_more'] ) ? 'yes' : 'no',

			// Excludes
			esc_attr( $ex_cat ),
			esc_attr( $ex_tag ),
			esc_attr( $ex_brand ),

			// Grid layout
			esc_attr( $grid_layout ),
			esc_attr( $masonry_gap ),
			esc_attr( $justified_h )
		) );
	}

	/* ============================================================
	   Helpers
	============================================================ */
	protected function terms_options( $taxonomy ) : array {
		$out = [];
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $out;
		}

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $out;
		}

		foreach ( $terms as $t ) {
			$out[ (string) $t->term_id ] = sprintf( '%s (#%d)', $t->name, (int) $t->term_id );
		}

		return $out;
	}
}
