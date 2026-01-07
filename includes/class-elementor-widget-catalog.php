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
        $this->register_style_filter_chips(); // NEW: Added detailed Chip styling
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

        $this->add_control( 'columns', [ 'label' => __( 'Columns', 'modefilter-pro' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 6, 'default' => 3 ] );
        $this->add_control( 'per_page', [ 'label' => __( 'Products Per Page', 'modefilter-pro' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 100, 'default' => 12 ] );

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

        $this->add_control( 'link_whole_card', [ 'label' => __( 'Link Entire Card', 'modefilter-pro' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

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

        $repeater->add_control( 'visible', [ 'label' => __( 'Show/Hide', 'modefilter-pro' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

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

        $this->add_control( 'filters_mode', [ 'label' => __( 'Selection Mode', 'modefilter-pro' ), 'type' => Controls_Manager::SELECT, 'default' => 'manual', 'options' => [ 'manual' => __( 'Manual (Choose below)', 'modefilter-pro' ), 'auto' => __( 'Auto (Smart detection)', 'modefilter-pro' ) ] ] );

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
                    'rating'     => __( 'Average Rating', 'modefilter-pro' ),
                ],
                'condition'   => [ 'filters_mode' => 'manual' ],
            ]
        );

        $this->add_control( 'filter_position', [ 'label' => __( 'Display Position', 'modefilter-pro' ), 'type' => Controls_Manager::SELECT, 'default' => 'left', 'options' => [ 'left' => 'Left', 'right' => 'Right', 'top' => 'Top Bar' ] ] );
        $this->add_control( 'terms_limit', [ 'label' => __( 'Max Terms per Filter', 'modefilter-pro' ), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 100, 'default' => 12 ] );
        $this->add_control( 'terms_orderby', [ 'label' => __( 'Sort Terms By', 'modefilter-pro' ), 'type' => Controls_Manager::SELECT, 'default' => 'count', 'options' => [ 'count' => 'Frequency', 'name' => 'Alphabetical' ] ] );
        $this->add_control( 'terms_show_more', [ 'label' => __( 'Enable "Show More"', 'modefilter-pro' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ] );

        $this->end_controls_section();
    }

    /* -------------------------------------------------------------------------- */
    /* STYLE SECTIONS                                                             */
    /* -------------------------------------------------------------------------- */

    protected function register_style_filters_panel() : void {
        $this->start_controls_section( 's_filters', [ 'label' => __( 'Filter Sidebar', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_group_control( Group_Control_Background::get_type(), [ 'name' => 'f_bg', 'selector' => '{{WRAPPER}} .modep-sidebar' ] );
        $this->end_controls_section();
    }

    protected function register_style_filter_chips() : void {
        $this->start_controls_section( 'section_style_chips', [ 'label' => __( 'Filter Chips / Tags', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'chip_typo', 'selector' => '{{WRAPPER}} .modep-chip' ] );

        $this->start_controls_tabs( 'tabs_chip_style' );

        $this->start_controls_tab( 'tab_chip_n', [ 'label' => 'Normal' ] );
        $this->add_control( 'chip_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-chip' => 'color: {{VALUE}};' ] ] );
        $this->add_control( 'chip_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-chip' => 'background-color: {{VALUE}};' ] ] );
        $this->end_controls_tab();

        $this->start_controls_tab( 'tab_chip_active', [ 'label' => 'Active' ] );
        $this->add_control( 'chip_color_a', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-chip.is-active' => 'color: {{VALUE}};' ] ] );
        $this->add_control( 'chip_bg_a', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-chip.is-active' => 'background-color: {{VALUE}};' ] ] );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control( 'chip_pad', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'separator' => 'before', 'selectors' => [ '{{WRAPPER}} .modep-chip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ] ] );
        $this->add_control( 'chip_spacing', [ 'label' => 'Gap', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .modep-filter-list' => 'gap: {{SIZE}}{{UNIT}}; display: flex; flex-wrap: wrap;' ] ] );
        $this->end_controls_section();
    }

    protected function register_style_grid_controls() : void {
        $this->start_controls_section( 's_grid', [ 'label' => __( 'Grid Spacing', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_responsive_control( 'g_gap', [ 'label' => __( 'Column Gap', 'modefilter-pro' ), 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .modep-grid' => 'gap: {{SIZE}}{{UNIT}};' ] ] );
        $this->end_controls_section();
    }

    protected function register_style_card_controls() : void {
        $this->start_controls_section( 's_card', [ 'label' => __( 'Product Card', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_group_control( Group_Control_Background::get_type(), [ 'name' => 'card_bg', 'selector' => '{{WRAPPER}} .modep-product-inner' ] );
        $this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'c_border', 'selector' => '{{WRAPPER}} .modep-product-inner' ] );
        $this->add_group_control( Group_Control_Box_Shadow::get_type(), [ 'name' => 'c_shadow', 'selector' => '{{WRAPPER}} .modep-product-inner' ] );
        $this->end_controls_section();
    }

    protected function register_style_title_controls() : void {
        $this->start_controls_section( 's_title', [ 'label' => __( 'Product Title', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_control( 't_color', [ 'label' => __( 'Color', 'modefilter-pro' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-title' => 'color: {{VALUE}};' ] ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 't_typo', 'selector' => '{{WRAPPER}} .modep-title' ] );
        $this->end_controls_section();
    }

    protected function register_style_button_controls() : void {
        $this->start_controls_section( 'section_style_button', [ 'label' => __( 'Enquiry Button Style', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'btn_typo', 'selector' => '{{WRAPPER}} .modep-btn-catalog' ] );
        
        $this->start_controls_tabs( 'tabs_btn_style' );
        $this->start_controls_tab( 'tab_btn_normal', [ 'label' => 'Normal' ] );
        $this->add_control( 'btn_color', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog' => 'color: {{VALUE}} !important;' ] ] );
        $this->add_control( 'btn_bg', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog' => 'background-color: {{VALUE}} !important;' ] ] );
        $this->end_controls_tab();

        $this->start_controls_tab( 'tab_btn_hover', [ 'label' => 'Hover' ] );
        $this->add_control( 'btn_color_hover', [ 'label' => 'Text Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog:hover' => 'color: {{VALUE}} !important;' ] ] );
        $this->add_control( 'btn_bg_hover', [ 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog:hover' => 'background-color: {{VALUE}} !important;' ] ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control( 'btn_padding', [ 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'separator' => 'before', 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ] ] );
        $this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'btn_border', 'selector' => '{{WRAPPER}} .modep-btn-catalog' ] );
        $this->add_control( 'btn_radius', [ 'label' => 'Border Radius', 'type' => Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .modep-btn-catalog' => 'border-radius: {{SIZE}}{{UNIT}} !important;' ] ] );
        $this->end_controls_section();
    }

    protected function register_style_messages_controls() : void {
        $this->start_controls_section( 's_msgs', [ 'label' => __( 'Status Messages', 'modefilter-pro' ), 'tab' => Controls_Manager::TAB_STYLE ] );
        $this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'm_typo', 'selector' => '{{WRAPPER}} .modep-no-products' ] );
        $this->end_controls_section();
    }

    /* -------------------------------------------------------------------------- */
    /* RENDER                                                                     */
    /* -------------------------------------------------------------------------- */

    protected function render() : void {
        $s = $this->get_settings_for_display();

        // Process Custom Layout String
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

        $attributes = [
            'only_catalog'        => 'yes',
            'catalog_button_text' => esc_attr( $s['catalog_button_text'] ?? __( 'Enquire Now', 'modefilter-pro' ) ),
            'columns'             => (int) ( $s['columns'] ?? 3 ),
            'per_page'            => (int) ( $s['per_page'] ?? 12 ),
            'sort'                => esc_attr( $s['sort'] ?? 'menu_order' ),
            'preset'              => esc_attr( $s['preset'] ?? 'normal' ),
            'custom_layout'       => esc_attr( $custom_layout ),
            'pagination'          => esc_attr( $s['pagination'] ?? 'numbers' ),
            'filter_position'     => esc_attr( $s['filter_position'] ?? 'left' ),
            'filters'             => ! empty( $s['enabled_filters'] ) ? implode( ',', array_map( 'sanitize_key', $s['enabled_filters'] ) ) : '',
            'terms_limit'         => (int) ( $s['terms_limit'] ?? 12 ),
            'terms_orderby'       => esc_attr( $s['terms_orderby'] ?? 'count' ),
            'terms_show_more'     => ( ! empty( $s['terms_show_more'] ) && $s['terms_show_more'] === 'yes' ) ? 'yes' : 'no',
            'grid_layout'         => esc_attr( $s['grid_layout'] ?? 'grid' ),
            'masonry_gap'         => isset( $s['masonry_gap']['size'] ) ? (int) $s['masonry_gap']['size'] : 20,
            'justified_height'    => (int) ( $s['justified_row_height'] ?? 250 ),
        ];

        $attr_string = '';
        foreach ( $attributes as $key => $val ) {
            $attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( (string) $val ) );
        }

        echo '<div class="modep-elementor-catalog-wrapper">';
        echo do_shortcode( '[modep_filters' . $attr_string . ']' );
        echo '</div>';
    }

    protected function get_terms_safe( string $taxonomy ) : array {
        $options = [];
        if ( ! taxonomy_exists( $taxonomy ) ) return $options;
        $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 200 ] );
        if ( is_wp_error( $terms ) || empty( $terms ) ) return $options;
        foreach ( $terms as $term ) { $options[ $term->term_id ] = $term->name; }
        return $options;
    }
}