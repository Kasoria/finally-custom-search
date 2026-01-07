<?php
/**
 * Bricks Builder Facet Element
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Bricks_Facet extends \Bricks\Element {

    /**
     * Element category
     */
    public $category = 'cfs';

    /**
     * Element name (unique identifier)
     */
    public $name = 'cfs-facet';

    /**
     * Element icon (Themify icons)
     */
    public $icon = 'ti-filter';

    /**
     * Element CSS selector for styling
     */
    public $css_selector = '.cfs-facet';

    /**
     * Element scripts
     */
    public $scripts = [];

    /**
     * Get element label
     */
    public function get_label() {
        return esc_html__('Facet Filter', 'custom-facet-search');
    }

    /**
     * Get element keywords for search
     */
    public function get_keywords() {
        return ['filter', 'facet', 'search', 'checkbox', 'dropdown', 'range'];
    }

    /**
     * Set control groups
     */
    public function set_control_groups() {
        $this->control_groups['facet'] = [
            'title' => esc_html__('Facet Settings', 'custom-facet-search'),
            'tab'   => 'content',
        ];

        $this->control_groups['labelStyle'] = [
            'title' => esc_html__('Label', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['optionsStyle'] = [
            'title' => esc_html__('Options', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['inputsStyle'] = [
            'title' => esc_html__('Input Fields', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['rangeStyle'] = [
            'title' => esc_html__('Range Slider', 'custom-facet-search'),
            'tab'   => 'style',
        ];
    }

    /**
     * Set element controls
     */
    public function set_controls() {
        // Get available facets from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        $facets = $wpdb->get_results("SELECT slug, name FROM $table_name ORDER BY name ASC");

        $facet_options = ['' => esc_html__('Select a facet', 'custom-facet-search')];
        foreach ($facets as $facet) {
            $facet_options[$facet->slug] = $facet->name;
        }

        // Facet Selection
        $this->controls['facet_slug'] = [
            'tab'         => 'content',
            'group'       => 'facet',
            'label'       => esc_html__('Select Facet', 'custom-facet-search'),
            'type'        => 'select',
            'options'     => $facet_options,
            'default'     => '',
            'placeholder' => esc_html__('Select a facet', 'custom-facet-search'),
        ];

        // Target Grid
        $this->controls['target_grid'] = [
            'tab'         => 'content',
            'group'       => 'facet',
            'label'       => esc_html__('Target Grid/Loop', 'custom-facet-search'),
            'type'        => 'text',
            'placeholder' => 'e.g., my-results or brxe-abc123',
            'description' => esc_html__('Enter the CSS ID or Bricks element ID of the results grid to filter. Leave empty to auto-detect.', 'custom-facet-search'),
        ];

        // Show Label
        $this->controls['show_label'] = [
            'tab'     => 'content',
            'group'   => 'facet',
            'label'   => esc_html__('Show Label', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // === STYLE CONTROLS ===

        // Label Styles
        $this->controls['label_typography'] = [
            'tab'   => 'style',
            'group' => 'labelStyle',
            'label' => esc_html__('Typography', 'custom-facet-search'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.cfs-facet-label',
                ],
            ],
        ];

        $this->controls['label_color'] = [
            'tab'   => 'style',
            'group' => 'labelStyle',
            'label' => esc_html__('Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-facet-label',
                ],
            ],
        ];

        $this->controls['label_margin'] = [
            'tab'   => 'style',
            'group' => 'labelStyle',
            'label' => esc_html__('Margin', 'custom-facet-search'),
            'type'  => 'spacing',
            'css'   => [
                [
                    'property' => 'margin',
                    'selector' => '.cfs-facet-label',
                ],
            ],
        ];

        // Options Styles
        $this->controls['option_typography'] = [
            'tab'   => 'style',
            'group' => 'optionsStyle',
            'label' => esc_html__('Typography', 'custom-facet-search'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.cfs-checkbox-label, .cfs-radio-label',
                ],
            ],
        ];

        $this->controls['option_color'] = [
            'tab'   => 'style',
            'group' => 'optionsStyle',
            'label' => esc_html__('Text Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-checkbox-label, .cfs-radio-label',
                ],
            ],
        ];

        $this->controls['option_spacing'] = [
            'tab'   => 'style',
            'group' => 'optionsStyle',
            'label' => esc_html__('Item Spacing', 'custom-facet-search'),
            'type'  => 'number',
            'units' => true,
            'css'   => [
                [
                    'property' => 'margin-bottom',
                    'selector' => '.cfs-checkbox-item, .cfs-radio-item',
                ],
            ],
        ];

        // Input Fields Styles
        $this->controls['input_background'] = [
            'tab'   => 'style',
            'group' => 'inputsStyle',
            'label' => esc_html__('Background', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background-color',
                    'selector' => '.cfs-range-input, .cfs-dropdown, .cfs-search-input, .cfs-date-input',
                ],
            ],
        ];

        $this->controls['input_border'] = [
            'tab'   => 'style',
            'group' => 'inputsStyle',
            'label' => esc_html__('Border', 'custom-facet-search'),
            'type'  => 'border',
            'css'   => [
                [
                    'property' => 'border',
                    'selector' => '.cfs-range-input, .cfs-dropdown, .cfs-search-input, .cfs-date-input',
                ],
            ],
        ];

        $this->controls['input_padding'] = [
            'tab'   => 'style',
            'group' => 'inputsStyle',
            'label' => esc_html__('Padding', 'custom-facet-search'),
            'type'  => 'spacing',
            'css'   => [
                [
                    'property' => 'padding',
                    'selector' => '.cfs-range-input, .cfs-dropdown, .cfs-search-input, .cfs-date-input',
                ],
            ],
        ];

        // Range Slider Styles
        $this->controls['range_track_color'] = [
            'tab'   => 'style',
            'group' => 'rangeStyle',
            'label' => esc_html__('Track Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background',
                    'selector' => '.noUi-target',
                ],
            ],
        ];

        $this->controls['range_progress_color'] = [
            'tab'   => 'style',
            'group' => 'rangeStyle',
            'label' => esc_html__('Progress Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background',
                    'selector' => '.noUi-connect',
                ],
            ],
        ];

        $this->controls['range_handle_color'] = [
            'tab'   => 'style',
            'group' => 'rangeStyle',
            'label' => esc_html__('Handle Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background',
                    'selector' => '.noUi-handle',
                ],
            ],
        ];
    }

    /**
     * Render element HTML
     */
    public function render() {
        $settings = $this->settings;

        // Check if facet is selected
        if (empty($settings['facet_slug'])) {
            // Show placeholder in builder
            if (bricks_is_builder()) {
                echo '<div class="cfs-bricks-placeholder">';
                echo esc_html__('Please select a facet from the settings panel.', 'custom-facet-search');
                echo '</div>';
            }
            return;
        }

        $facet_slug = $settings['facet_slug'];
        $target_grid = $settings['target_grid'] ?? '';
        $show_label = isset($settings['show_label']) ? (bool) $settings['show_label'] : true;

        // Set root element attributes
        $this->set_attribute('_root', 'class', 'cfs-bricks-facet-wrapper');

        // Render the wrapper and facet
        echo "<div {$this->render_attributes('_root')}>";

        echo CFS_Facets::instance()->render($facet_slug, [
            'show_label'  => $show_label,
            'target_grid' => $target_grid,
        ]);

        echo '</div>';
    }
}
