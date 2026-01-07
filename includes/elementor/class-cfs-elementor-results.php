<?php
/**
 * Elementor Results Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Elementor_Results_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'cfs_results';
    }
    
    public function get_title() {
        return __('Facet Results', 'custom-facet-search');
    }
    
    public function get_icon() {
        return 'eicon-posts-grid';
    }
    
    public function get_categories() {
        return ['custom-facet-search'];
    }
    
    public function get_keywords() {
        return ['results', 'grid', 'posts', 'search'];
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Query Settings', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Grid ID for targeting
        $this->add_control(
            'grid_id',
            [
                'label' => __('Grid ID', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Auto-generated', 'custom-facet-search'),
                'description' => __('Unique ID for this grid. Use this ID in facet widgets to target this specific grid.', 'custom-facet-search'),
                'label_block' => true,
            ]
        );
        
        // Get post types
        $post_types = get_post_types(['public' => true], 'objects');
        $post_type_options = [];
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'attachment') {
                $post_type_options[$post_type->name] = $post_type->label;
            }
        }
        
        $this->add_control(
            'post_type',
            [
                'label' => __('Post Type', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $post_type_options,
                'default' => 'post',
            ]
        );
        
        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Posts Per Page', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'default' => 12,
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'default' => '3',
            ]
        );
        
        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'date' => __('Date', 'custom-facet-search'),
                    'title' => __('Title', 'custom-facet-search'),
                    'menu_order' => __('Menu Order', 'custom-facet-search'),
                    'rand' => __('Random', 'custom-facet-search'),
                    'meta_value' => __('Meta Value', 'custom-facet-search'),
                    'meta_value_num' => __('Meta Value (Numeric)', 'custom-facet-search'),
                ],
                'default' => 'date',
            ]
        );
        
        $this->add_control(
            'order',
            [
                'label' => __('Order', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'DESC' => __('Descending', 'custom-facet-search'),
                    'ASC' => __('Ascending', 'custom-facet-search'),
                ],
                'default' => 'DESC',
            ]
        );
        
        $this->add_control(
            'show_sort',
            [
                'label' => __('Show Sort Dropdown', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_count',
            [
                'label' => __('Show Results Count', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Layout Section
        $this->start_controls_section(
            'section_layout',
            [
                'label' => __('Layout', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'show_thumbnail',
            [
                'label' => __('Show Thumbnail', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'thumbnail_size',
            [
                'label' => __('Thumbnail Size', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'thumbnail' => __('Thumbnail', 'custom-facet-search'),
                    'medium' => __('Medium', 'custom-facet-search'),
                    'medium_large' => __('Medium Large', 'custom-facet-search'),
                    'large' => __('Large', 'custom-facet-search'),
                    'full' => __('Full', 'custom-facet-search'),
                ],
                'default' => 'medium',
                'condition' => [
                    'show_thumbnail' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'show_excerpt',
            [
                'label' => __('Show Excerpt', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'excerpt_length',
            [
                'label' => __('Excerpt Length', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 10,
                'max' => 500,
                'default' => 100,
                'condition' => [
                    'show_excerpt' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'show_date',
            [
                'label' => __('Show Date', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Grid
        $this->start_controls_section(
            'section_style_grid',
            [
                'label' => __('Grid', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'column_gap',
            [
                'label' => __('Column Gap', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .cfs-results' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'row_gap',
            [
                'label' => __('Row Gap', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .cfs-results' => 'row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Card
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => __('Card', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => __('Background', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-result-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .cfs-result-item',
            ]
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .cfs-result-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .cfs-result-item',
            ]
        );
        
        $this->add_responsive_control(
            'card_padding',
            [
                'label' => __('Padding', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .cfs-result-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Title
        $this->start_controls_section(
            'section_style_title',
            [
                'label' => __('Title', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-result-title a' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'title_hover_color',
            [
                'label' => __('Hover Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-result-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .cfs-result-title',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Generate or use custom grid ID
        $grid_id = !empty($settings['grid_id']) ? $settings['grid_id'] : 'cfs-grid-' . $this->get_id();
        
        echo do_shortcode(sprintf(
            '[cfs_results post_type="%s" posts_per_page="%d" columns="%s" orderby="%s" order="%s" grid_id="%s"]',
            esc_attr($settings['post_type']),
            intval($settings['posts_per_page']),
            esc_attr($settings['columns']),
            esc_attr($settings['orderby']),
            esc_attr($settings['order']),
            esc_attr($grid_id)
        ));
    }
}
