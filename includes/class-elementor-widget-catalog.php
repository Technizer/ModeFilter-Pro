<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

class MODEP_Elementor_Widget_Catalog extends \Elementor\Widget_Base {

	/* ---------------------------------
	   Identity
	---------------------------------- */
	public function get_name() {
		return 'modep_catalog';
	}

	public function get_title() {
		return __( 'ModeFilter Catalog Products', 'modefilter-pro' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_categories() {
		return [ 'modefilter-pro' ];
	}

	public function get_script_depends() {
		// Adjust to your handles.
		return [ 'modefilter-pro-frontend' ];
	}

	public function get_style_depends() {
		// Adjust to your handles.
		return [ 'modefilter-pro' ];
	}

	/* ---------------------------------
	   Controls
	---------------------------------- */
	protected function register_controls() {

		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_filters_controls();

		$this->register_style_container_controls();
		$this->register_style_card_controls();
		$this->register_style_parts_controls();

		// Messages controls existed in the Sellable widget: keep parity.
		$this->register_style_messages_controls();
	}

	private function register_content_controls() : void {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'preset',
			[
				'label'   => __( 'Preset', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'normal',
				'options' => [
					'normal'  => __( 'Normal', 'modefilter-pro' ),
					'overlay' => __( 'Overlay', 'modefilter-pro' ),
					'minimal' => __( 'Minimal', 'modefilter-pro' ),
					'custom'  => __( 'Custom (use Custom Layout)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'modefilter-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'step'    => 1,
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
				'step'    => 1,
				'default' => 12,
			]
		);

		$this->add_control(
			'sort',
			[
				'label'   => __( 'Sort', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu_order',
				'options' => [
					'menu_order' => __( 'Default', 'modefilter-pro' ),
					'date'       => __( 'Newest', 'modefilter-pro' ),
					'popularity' => __( 'Popularity', 'modefilter-pro' ),
					'rating'     => __( 'Rating', 'modefilter-pro' ),
					// Price sorts can still exist even in catalog mode,
					// since sorting does not necessarily "show price" in UI.
					'price'      => __( 'Price (Low to High)', 'modefilter-pro' ),
					'price-desc' => __( 'Price (High to Low)', 'modefilter-pro' ),
					'rand'       => __( 'Random', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'pagination',
			[
				'label'        => __( 'Pagination', 'modefilter-pro' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'numbers',
				'options'      => [
					'none'     => __( 'None', 'modefilter-pro' ),
					'numbers'  => __( 'Numbers', 'modefilter-pro' ),
					'loadmore' => __( 'Load More Button', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'load_more_text',
			[
				'label'     => __( 'Load More Text', 'modefilter-pro' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Load More', 'modefilter-pro' ),
				'condition' => [ 'pagination' => 'loadmore' ],
			]
		);

		$this->add_control(
			'catalog_button_text',
			[
				'label'       => __( 'Catalog Button Text', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Enquire Now', 'modefilter-pro' ),
				'label_block' => true,
				'description' => __( 'Shown instead of price/add to cart when the product is in Catalog Mode.', 'modefilter-pro' ),
			]
		);

		$this->add_control(
			'link_whole_card',
			[
				'label'        => __( 'Link Whole Card', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'modefilter-pro' ),
				'label_off'    => __( 'No', 'modefilter-pro' ),
				'return_value' => '1',
				'default'      => '1',
			]
		);

		$this->end_controls_section();
	}

	private function register_layout_controls() : void {
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
					'grid'      => __( 'Grid', 'modefilter-pro' ),
					'masonry'   => __( 'Masonry', 'modefilter-pro' ),
					'justified' => __( 'Justified', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'masonry_gap',
			[
				'label'     => __( 'Masonry Gap (px)', 'modefilter-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 80,
				'step'      => 1,
				'default'   => 20,
				'condition' => [ 'grid_layout' => 'masonry' ],
			]
		);

		$this->add_control(
			'justified_row_height',
			[
				'label'     => __( 'Justified Row Height (px)', 'modefilter-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 50,
				'max'       => 600,
				'step'      => 1,
				'default'   => 220,
				'condition' => [ 'grid_layout' => 'justified' ],
			]
		);

		$this->end_controls_section();

		// Custom Layout (reorder parts)
		$this->start_controls_section(
			'section_custom_layout',
			[
				'label' => __( 'Custom Layout', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'part',
			[
				'label'   => __( 'Part', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'title',
				'options' => $this->parts_options_catalog(),
			]
		);

		$repeater->add_control(
			'enabled',
			[
				'label'        => __( 'Show', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'modefilter-pro' ),
				'label_off'    => __( 'No', 'modefilter-pro' ),
				'return_value' => '1',
				'default'      => '1',
			]
		);

		$this->add_control(
			'custom_layout',
			[
				'label'       => __( 'Parts Order', 'modefilter-pro' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ part }}}',
				'default'     => $this->parts_default_catalog(),
				'condition'   => [ 'preset' => 'custom' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_filters_controls() : void {
		$this->start_controls_section(
			'section_filters',
			[
				'label' => __( 'Filters', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'filter_ui',
			[
				'label'   => __( 'Filter UI', 'modefilter-pro' ),
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'chips',
			]
		);

		$this->add_control(
			'filter_position',
			[
				'label'   => __( 'Filter Position', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left'  => __( 'Left', 'modefilter-pro' ),
					'top'   => __( 'Top', 'modefilter-pro' ),
					'right' => __( 'Right', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'filters_mode',
			[
				'label'   => __( 'Filters Mode', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => [
					'manual' => __( 'Manual (opt-in)', 'modefilter-pro' ),
					'auto'   => __( 'Auto-detect (only existing)', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'filters',
			[
				'label'       => __( 'Enable Filters', 'modefilter-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [ 'categories', 'tags', 'brands' ],
				'options'     => [
					'categories' => __( 'Categories', 'modefilter-pro' ),
					'tags'       => __( 'Tags', 'modefilter-pro' ),
					'brands'     => __( 'Brands', 'modefilter-pro' ),
					'price'      => __( 'Price', 'modefilter-pro' ),
					'rating'     => __( 'Rating', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_limit',
			[
				'label'   => __( 'Terms Limit', 'modefilter-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 200,
				'step'    => 1,
				'default' => 12,
			]
		);

		$this->add_control(
			'terms_orderby',
			[
				'label'   => __( 'Order By', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'count',
				'options' => [
					'count' => __( 'Count', 'modefilter-pro' ),
					'name'  => __( 'Name', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_order',
			[
				'label'   => __( 'Order', 'modefilter-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC'  => __( 'ASC', 'modefilter-pro' ),
					'DESC' => __( 'DESC', 'modefilter-pro' ),
				],
			]
		);

		$this->add_control(
			'terms_show_more',
			[
				'label'        => __( 'Show “More” Toggle', 'modefilter-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'modefilter-pro' ),
				'label_off'    => __( 'No', 'modefilter-pro' ),
				'return_value' => '1',
				'default'      => '',
			]
		);

		$this->add_control(
			'exclude_cat',
			[
				'label'       => __( 'Exclude Categories (IDs or slugs)', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'uncategorized,12,15',
			]
		);

		$this->add_control(
			'exclude_tag',
			[
				'label'       => __( 'Exclude Tags (IDs or slugs)', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'featured,10',
			]
		);

		$this->add_control(
			'exclude_brand',
			[
				'label'       => __( 'Exclude Brands (IDs or slugs)', 'modefilter-pro' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'acme,7',
			]
		);

		$this->end_controls_section();
	}

	/* ---------------------------------
	   Style controls (trimmed for Catalog)
	---------------------------------- */
	private function register_style_container_controls() : void {
		$this->start_controls_section(
			'section_style_container',
			[
				'label' => __( 'Container', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_bg',
				'selector' => '{{WRAPPER}} .modep',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .modep',
			]
		);

		$this->end_controls_section();
	}

	private function register_style_card_controls() : void {
		$this->start_controls_section(
			'section_style_card',
			[
				'label' => __( 'Card', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_bg',
				'selector' => '{{WRAPPER}} .modep-card',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .modep-card',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .modep-card',
			]
		);

		$this->end_controls_section();
	}

	private function register_style_parts_controls() : void {
		$this->start_controls_section(
			'section_style_parts',
			[
				'label' => __( 'Parts', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typo',
				'selector' => '{{WRAPPER}} .modep-card__title',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typo',
				'selector' => '{{WRAPPER}} .modep-card__meta',
			]
		);

		// NOTE: Intentionally no price typography/style controls here.

		$this->end_controls_section();
	}

	private function register_style_messages_controls() : void {
		$this->start_controls_section(
			'section_style_messages',
			[
				'label' => __( 'Messages', 'modefilter-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'msg_typo',
				'selector' => '{{WRAPPER}} .modep-message',
			]
		);

		$this->end_controls_section();
	}

	/* ---------------------------------
	   Render
	---------------------------------- */
	protected function render() {
		$s = $this->get_settings_for_display();

		// Build shortcode attributes (keep parity with Sellable widget contract).
		// IMPORTANT: catalog listing must force only_catalog="yes" so the shortcode filters to catalog products
		// and uses the Enquire button instead of price/cart.
		$atts = [
			'only_catalog'         => 'yes',
			'catalog_button_text'  => (string) ( $s['catalog_button_text'] ?? __( 'Enquire Now', 'modefilter-pro' ) ),
			// Query / grid
			'columns'             => (int) ( $s['columns'] ?? 3 ),
			'per_page'            => (int) ( $s['per_page'] ?? 12 ),
			'sort'                => (string) ( $s['sort'] ?? 'menu_order' ),

			// Layout
			'preset'              => (string) ( $s['preset'] ?? 'normal' ),
			// Keep the shortcode contract used in the sellable widget: none | numbers | load_more | infinite
			'pagination'          => ( 'loadmore' === (string) ( $s['pagination'] ?? '' ) ) ? 'load_more' : (string) ( $s['pagination'] ?? 'numbers' ),
			'load_more_text'      => (string) ( $s['load_more_text'] ?? __( 'Load More', 'modefilter-pro' ) ),
			'link_whole_card'     => ! empty( $s['link_whole_card'] ) ? 'yes' : 'no',

			// Filters
			'filter_ui'           => 'chips',
			'filter_position'     => (string) ( $s['filter_position'] ?? 'left' ),
			'filters_mode'        => (string) ( $s['filters_mode'] ?? 'manual' ),
			'filters'             => is_array( $s['filters'] ?? null ) ? implode( ',', $s['filters'] ) : (string) ( $s['filters'] ?? '' ),
			'terms_limit'         => (int) ( $s['terms_limit'] ?? 12 ),
			'terms_orderby'       => (string) ( $s['terms_orderby'] ?? 'count' ),
			'terms_order'         => (string) ( $s['terms_order'] ?? 'DESC' ),
			'terms_show_more'     => ! empty( $s['terms_show_more'] ) ? 'yes' : 'no',
			'exclude_cat'         => (string) ( $s['exclude_cat'] ?? '' ),
			'exclude_tag'         => (string) ( $s['exclude_tag'] ?? '' ),
			'exclude_brand'       => (string) ( $s['exclude_brand'] ?? '' ),

			// Grid layout mode
			'grid_layout'         => (string) ( $s['grid_layout'] ?? 'grid' ),
			'masonry_gap'         => (int) ( $s['masonry_gap'] ?? 20 ),
			'justified_row_height'=> (int) ( $s['justified_row_height'] ?? 220 ),
		];

		// Custom layout (only when preset=custom).
		if ( ! empty( $s['preset'] ) && 'custom' === $s['preset'] && ! empty( $s['custom_layout'] ) && is_array( $s['custom_layout'] ) ) {
			// Convert repeater rows into the pipe format expected by the shortcode (same as Sellable widget).
			$parts = [];
			foreach ( (array) $s['custom_layout'] as $row ) {
				$flag    = ( ! empty( $row['enabled'] ) && '1' === (string) $row['enabled'] ) ? '' : '!';
				$partkey = sanitize_key( (string) ( $row['part'] ?? '' ) );
				if ( $partkey ) {
					$parts[] = $flag . $partkey;
				}
			}
			$atts['custom_layout'] = implode( '|', array_filter( $parts ) );
		}

		// Build shortcode string. Use [modep_filters] for rendering to ensure catalog contract works everywhere.
		$shortcode = '[modep_filters';
		foreach ( $atts as $k => $v ) {
			if ( '' === $v || null === $v ) {
				continue;
			}
			$shortcode .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( (string) $v ) );
		}
		$shortcode .= ']';

		echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/* ---------------------------------
	   Helpers: parts options & defaults
	---------------------------------- */
	private function parts_options_catalog() : array {
		// IMPORTANT: no price part in Catalog widget.
		return [
			'image'      => __( 'Image', 'modefilter-pro' ),
			'title'      => __( 'Title', 'modefilter-pro' ),
			'rating'     => __( 'Rating', 'modefilter-pro' ),
			'categories' => __( 'Categories', 'modefilter-pro' ),
			'tags'       => __( 'Tags', 'modefilter-pro' ),
			'brand'      => __( 'Brand', 'modefilter-pro' ),
			'excerpt'    => __( 'Excerpt', 'modefilter-pro' ),
			'button'     => __( 'Button', 'modefilter-pro' ),
			'badge'      => __( 'Badge', 'modefilter-pro' ),
		];
	}

	private function parts_default_catalog() : array {
		return [
			[ 'part' => 'image',      'enabled' => '1' ],
			[ 'part' => 'title',      'enabled' => '1' ],
			[ 'part' => 'rating',     'enabled' => '1' ],
			[ 'part' => 'brand',      'enabled' => '1' ],
			[ 'part' => 'categories', 'enabled' => '1' ],
			[ 'part' => 'button',     'enabled' => '1' ],
		];
	}
}
