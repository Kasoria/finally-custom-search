<?php
/**
 * Elementor Facet Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Elementor_Facet_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'cfs_facet';
    }
    
    public function get_title() {
        return __('Facet Filter', 'custom-facet-search');
    }
    
    public function get_icon() {
        return 'eicon-filter';
    }
    
    public function get_categories() {
        return ['custom-facet-search'];
    }
    
    public function get_keywords() {
        return ['filter', 'facet', 'search', 'custom'];
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Facet Settings', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Get available facets
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        $facets = $wpdb->get_results("SELECT slug, name FROM $table_name ORDER BY name ASC");
        
        $facet_options = ['' => __('Select a facet', 'custom-facet-search')];
        foreach ($facets as $facet) {
            $facet_options[$facet->slug] = $facet->name;
        }
        
        $this->add_control(
            'facet_slug',
            [
                'label' => __('Select a facet', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $facet_options,
                'default' => '',
            ]
        );
        
        // Target grid/widget selector
        $this->add_control(
            'target_grid',
            [
                'label' => __('Select a grid or widget to filter', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'e.g., e3e3d8a or .my-results',
                'description' => __('Enter the Elementor widget ID (found in Advanced → CSS ID) or a CSS selector. Leave empty to auto-detect.', 'custom-facet-search'),
                'label_block' => true,
            ]
        );
        
        $this->add_control(
            'show_label',
            [
                'label' => __('Show Label', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-facet-search'),
                'label_off' => __('No', 'custom-facet-search'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Label
        $this->start_controls_section(
            'section_style_label',
            [
                'label' => __('Label', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_color',
            [
                'label' => __('Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-facet-label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .cfs-facet-label',
            ]
        );
        
        $this->add_responsive_control(
            'label_margin',
            [
                'label' => __('Margin', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .cfs-facet-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Options
        $this->start_controls_section(
            'section_style_options',
            [
                'label' => __('Options', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'option_color',
            [
                'label' => __('Text Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-checkbox-label, {{WRAPPER}} .cfs-radio-label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'option_typography',
                'selector' => '{{WRAPPER}} .cfs-checkbox-label, {{WRAPPER}} .cfs-radio-label',
            ]
        );
        
        $this->add_responsive_control(
            'option_spacing',
            [
                'label' => __('Spacing', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .cfs-checkbox-item, {{WRAPPER}} .cfs-radio-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Range Slider
        $this->start_controls_section(
            'section_style_range',
            [
                'label' => __('Range Slider', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'range_track_color',
            [
                'label' => __('Track Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .noUi-target' => 'background: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'range_progress_color',
            [
                'label' => __('Progress Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .noUi-connect' => 'background: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'range_handle_color',
            [
                'label' => __('Handle Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .noUi-handle' => 'background: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Inputs
        $this->start_controls_section(
            'section_style_inputs',
            [
                'label' => __('Input Fields', 'custom-facet-search'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'input_background',
            [
                'label' => __('Background', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-range-input, {{WRAPPER}} .cfs-dropdown, {{WRAPPER}} .cfs-search-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_border_color',
            [
                'label' => __('Border Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-range-input, {{WRAPPER}} .cfs-dropdown, {{WRAPPER}} .cfs-search-input' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'input_focus_border_color',
            [
                'label' => __('Focus Border Color', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cfs-range-input:focus, {{WRAPPER}} .cfs-dropdown:focus, {{WRAPPER}} .cfs-search-input:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'input_border_radius',
            [
                'label' => __('Border Radius', 'custom-facet-search'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .cfs-range-input, {{WRAPPER}} .cfs-dropdown, {{WRAPPER}} .cfs-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['facet_slug'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="cfs-elementor-notice">' . esc_html__('Please select a facet.', 'custom-facet-search') . '</p>';
            }
            return;
        }
        
        $target_grid = $settings['target_grid'] ?? '';
        
        echo CFS_Facets::instance()->render($settings['facet_slug'], [
            'show_label' => $settings['show_label'] === 'yes',
            'target_grid' => $target_grid,
        ]);
    }
}
