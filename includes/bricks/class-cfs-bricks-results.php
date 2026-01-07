<?php
/**
 * Bricks Builder Results Element
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Bricks_Results extends \Bricks\Element {

    /**
     * Element category
     */
    public $category = 'cfs';

    /**
     * Element name (unique identifier)
     */
    public $name = 'cfs-results';

    /**
     * Element icon (Themify icons)
     */
    public $icon = 'ti-layout-grid3';

    /**
     * Element CSS selector for styling
     */
    public $css_selector = '.cfs-results-wrapper';

    /**
     * Element scripts
     */
    public $scripts = [];

    /**
     * Get element label
     */
    public function get_label() {
        return esc_html__('Facet Results', 'custom-facet-search');
    }

    /**
     * Get element keywords for search
     */
    public function get_keywords() {
        return ['results', 'grid', 'posts', 'loop', 'query'];
    }

    /**
     * Set control groups
     */
    public function set_control_groups() {
        $this->control_groups['query'] = [
            'title' => esc_html__('Query Settings', 'custom-facet-search'),
            'tab'   => 'content',
        ];

        $this->control_groups['layout'] = [
            'title' => esc_html__('Layout', 'custom-facet-search'),
            'tab'   => 'content',
        ];

        $this->control_groups['display'] = [
            'title' => esc_html__('Display Options', 'custom-facet-search'),
            'tab'   => 'content',
        ];

        $this->control_groups['gridStyle'] = [
            'title' => esc_html__('Grid', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['cardStyle'] = [
            'title' => esc_html__('Card', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['titleStyle'] = [
            'title' => esc_html__('Title', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['excerptStyle'] = [
            'title' => esc_html__('Excerpt', 'custom-facet-search'),
            'tab'   => 'style',
        ];

        $this->control_groups['metaStyle'] = [
            'title' => esc_html__('Meta', 'custom-facet-search'),
            'tab'   => 'style',
        ];
    }

    /**
     * Set element controls
     */
    public function set_controls() {
        // Get available post types
        $post_types = get_post_types(['public' => true], 'objects');
        $post_type_options = [];
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'attachment') {
                $post_type_options[$post_type->name] = $post_type->label;
            }
        }

        // === QUERY SETTINGS ===

        // Grid ID
        $this->controls['grid_id'] = [
            'tab'         => 'content',
            'group'       => 'query',
            'label'       => esc_html__('Grid ID', 'custom-facet-search'),
            'type'        => 'text',
            'placeholder' => esc_html__('Auto-generated', 'custom-facet-search'),
            'description' => esc_html__('Unique ID for this grid. Use this in facet widgets to target this specific results grid.', 'custom-facet-search'),
        ];

        // Post Type
        $this->controls['post_type'] = [
            'tab'     => 'content',
            'group'   => 'query',
            'label'   => esc_html__('Post Type', 'custom-facet-search'),
            'type'    => 'select',
            'options' => $post_type_options,
            'default' => 'post',
        ];

        // Posts Per Page
        $this->controls['posts_per_page'] = [
            'tab'     => 'content',
            'group'   => 'query',
            'label'   => esc_html__('Posts Per Page', 'custom-facet-search'),
            'type'    => 'number',
            'min'     => 1,
            'max'     => 100,
            'default' => 12,
        ];

        // Order By
        $this->controls['orderby'] = [
            'tab'     => 'content',
            'group'   => 'query',
            'label'   => esc_html__('Order By', 'custom-facet-search'),
            'type'    => 'select',
            'options' => [
                'date'           => esc_html__('Date', 'custom-facet-search'),
                'title'          => esc_html__('Title', 'custom-facet-search'),
                'menu_order'     => esc_html__('Menu Order', 'custom-facet-search'),
                'rand'           => esc_html__('Random', 'custom-facet-search'),
                'meta_value'     => esc_html__('Meta Value', 'custom-facet-search'),
                'meta_value_num' => esc_html__('Meta Value (Numeric)', 'custom-facet-search'),
            ],
            'default' => 'date',
        ];

        // Order
        $this->controls['order'] = [
            'tab'     => 'content',
            'group'   => 'query',
            'label'   => esc_html__('Order', 'custom-facet-search'),
            'type'    => 'select',
            'options' => [
                'DESC' => esc_html__('Descending', 'custom-facet-search'),
                'ASC'  => esc_html__('Ascending', 'custom-facet-search'),
            ],
            'default' => 'DESC',
        ];

        // === LAYOUT SETTINGS ===

        // Columns
        $this->controls['columns'] = [
            'tab'     => 'content',
            'group'   => 'layout',
            'label'   => esc_html__('Columns', 'custom-facet-search'),
            'type'    => 'select',
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
            'default' => '3',
        ];

        // === DISPLAY OPTIONS ===

        // Show Sort Dropdown
        $this->controls['show_sort'] = [
            'tab'     => 'content',
            'group'   => 'display',
            'label'   => esc_html__('Show Sort Dropdown', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // Show Results Count
        $this->controls['show_count'] = [
            'tab'     => 'content',
            'group'   => 'display',
            'label'   => esc_html__('Show Results Count', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // Show Thumbnail
        $this->controls['show_thumbnail'] = [
            'tab'     => 'content',
            'group'   => 'display',
            'label'   => esc_html__('Show Thumbnail', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // Thumbnail Size
        $this->controls['thumbnail_size'] = [
            'tab'      => 'content',
            'group'    => 'display',
            'label'    => esc_html__('Thumbnail Size', 'custom-facet-search'),
            'type'     => 'select',
            'options'  => [
                'thumbnail'    => esc_html__('Thumbnail', 'custom-facet-search'),
                'medium'       => esc_html__('Medium', 'custom-facet-search'),
                'medium_large' => esc_html__('Medium Large', 'custom-facet-search'),
                'large'        => esc_html__('Large', 'custom-facet-search'),
                'full'         => esc_html__('Full', 'custom-facet-search'),
            ],
            'default'  => 'medium',
            'required' => [['show_thumbnail', '=', true]],
        ];

        // Show Excerpt
        $this->controls['show_excerpt'] = [
            'tab'     => 'content',
            'group'   => 'display',
            'label'   => esc_html__('Show Excerpt', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // Excerpt Length
        $this->controls['excerpt_length'] = [
            'tab'      => 'content',
            'group'    => 'display',
            'label'    => esc_html__('Excerpt Length (words)', 'custom-facet-search'),
            'type'     => 'number',
            'min'      => 5,
            'max'      => 100,
            'default'  => 20,
            'required' => [['show_excerpt', '=', true]],
        ];

        // Show Date
        $this->controls['show_date'] = [
            'tab'     => 'content',
            'group'   => 'display',
            'label'   => esc_html__('Show Date', 'custom-facet-search'),
            'type'    => 'checkbox',
            'default' => true,
        ];

        // === STYLE CONTROLS ===

        // Grid Gap
        $this->controls['grid_gap'] = [
            'tab'   => 'style',
            'group' => 'gridStyle',
            'label' => esc_html__('Gap', 'custom-facet-search'),
            'type'  => 'number',
            'units' => true,
            'css'   => [
                [
                    'property' => 'gap',
                    'selector' => '.cfs-results',
                ],
            ],
            'default' => '20px',
        ];

        // Card Background
        $this->controls['card_background'] = [
            'tab'   => 'style',
            'group' => 'cardStyle',
            'label' => esc_html__('Background', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'background-color',
                    'selector' => '.cfs-result-item',
                ],
            ],
        ];

        // Card Border
        $this->controls['card_border'] = [
            'tab'   => 'style',
            'group' => 'cardStyle',
            'label' => esc_html__('Border', 'custom-facet-search'),
            'type'  => 'border',
            'css'   => [
                [
                    'property' => 'border',
                    'selector' => '.cfs-result-item',
                ],
            ],
        ];

        // Card Box Shadow
        $this->controls['card_box_shadow'] = [
            'tab'   => 'style',
            'group' => 'cardStyle',
            'label' => esc_html__('Box Shadow', 'custom-facet-search'),
            'type'  => 'box-shadow',
            'css'   => [
                [
                    'property' => 'box-shadow',
                    'selector' => '.cfs-result-item',
                ],
            ],
        ];

        // Card Padding
        $this->controls['card_padding'] = [
            'tab'   => 'style',
            'group' => 'cardStyle',
            'label' => esc_html__('Content Padding', 'custom-facet-search'),
            'type'  => 'spacing',
            'css'   => [
                [
                    'property' => 'padding',
                    'selector' => '.cfs-result-content',
                ],
            ],
        ];

        // Title Typography
        $this->controls['title_typography'] = [
            'tab'   => 'style',
            'group' => 'titleStyle',
            'label' => esc_html__('Typography', 'custom-facet-search'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.cfs-result-title',
                ],
            ],
        ];

        // Title Color
        $this->controls['title_color'] = [
            'tab'   => 'style',
            'group' => 'titleStyle',
            'label' => esc_html__('Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-result-title a',
                ],
            ],
        ];

        // Title Hover Color
        $this->controls['title_hover_color'] = [
            'tab'   => 'style',
            'group' => 'titleStyle',
            'label' => esc_html__('Hover Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-result-title a:hover',
                ],
            ],
        ];

        // Excerpt Typography
        $this->controls['excerpt_typography'] = [
            'tab'   => 'style',
            'group' => 'excerptStyle',
            'label' => esc_html__('Typography', 'custom-facet-search'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.cfs-result-excerpt',
                ],
            ],
        ];

        // Excerpt Color
        $this->controls['excerpt_color'] = [
            'tab'   => 'style',
            'group' => 'excerptStyle',
            'label' => esc_html__('Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-result-excerpt',
                ],
            ],
        ];

        // Meta Typography
        $this->controls['meta_typography'] = [
            'tab'   => 'style',
            'group' => 'metaStyle',
            'label' => esc_html__('Typography', 'custom-facet-search'),
            'type'  => 'typography',
            'css'   => [
                [
                    'property' => 'font',
                    'selector' => '.cfs-result-meta',
                ],
            ],
        ];

        // Meta Color
        $this->controls['meta_color'] = [
            'tab'   => 'style',
            'group' => 'metaStyle',
            'label' => esc_html__('Color', 'custom-facet-search'),
            'type'  => 'color',
            'css'   => [
                [
                    'property' => 'color',
                    'selector' => '.cfs-result-meta',
                ],
            ],
        ];
    }

    /**
     * Render element HTML
     */
    public function render() {
        $settings = $this->settings;

        // Generate grid ID
        $grid_id = !empty($settings['grid_id']) ? $settings['grid_id'] : 'cfs-bricks-' . $this->id;

        // Build shortcode attributes
        $atts = [
            'post_type'      => $settings['post_type'] ?? 'post',
            'posts_per_page' => $settings['posts_per_page'] ?? 12,
            'columns'        => $settings['columns'] ?? 3,
            'orderby'        => $settings['orderby'] ?? 'date',
            'order'          => $settings['order'] ?? 'DESC',
            'grid_id'        => $grid_id,
        ];

        // Set root element attributes
        $this->set_attribute('_root', 'class', 'cfs-bricks-results-wrapper');
        $this->set_attribute('_root', 'data-bricks-id', $this->id);

        // Render the wrapper
        echo "<div {$this->render_attributes('_root')}>";

        // Render shortcode
        echo do_shortcode(sprintf(
            '[cfs_results post_type="%s" posts_per_page="%d" columns="%s" orderby="%s" order="%s" grid_id="%s"]',
            esc_attr($atts['post_type']),
            intval($atts['posts_per_page']),
            esc_attr($atts['columns']),
            esc_attr($atts['orderby']),
            esc_attr($atts['order']),
            esc_attr($atts['grid_id'])
        ));

        echo '</div>';
    }
}
