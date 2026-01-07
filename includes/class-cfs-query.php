<?php
/**
 * Query modification class for faceted search
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Query {
    
    private static $instance = null;
    private $active_filters = [];
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('pre_get_posts', [$this, 'modify_query'], 20);
        add_filter('posts_where', [$this, 'custom_where'], 10, 2);
        add_filter('posts_join', [$this, 'custom_join'], 10, 2);
    }
    
    /**
     * Modify the main query based on facet parameters
     */
    public function modify_query($query) {
        global $wpdb;

        // Only modify frontend queries
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }

        // Check if we have any CFS filter parameters in the URL
        $has_cfs_params = false;
        foreach ($_GET as $key => $value) {
            if (strpos($key, 'cfs_') === 0) {
                $has_cfs_params = true;
                break;
            }
        }

        // Determine if we should filter this query
        $should_filter = false;

        // Always filter queries explicitly flagged with cfs_filter (our AJAX queries)
        if ($query->get('cfs_filter')) {
            $should_filter = true;
        }

        // Filter main query if we have CFS params
        if ($query->is_main_query() && $has_cfs_params) {
            $should_filter = true;
        }

        // Also filter post type queries on frontend if we have CFS params
        // This handles page builder loops (Bricks, Elementor, etc.)
        if ($has_cfs_params && !is_admin() && !$query->is_main_query()) {
            $post_type = $query->get('post_type');
            // Filter if it's querying a specific post type (not just 'any' or pages)
            if (!empty($post_type) && $post_type !== 'page' && $post_type !== 'revision' && $post_type !== 'nav_menu_item') {
                $should_filter = true;
            }
        }

        // Allow filtering via hook
        $should_filter = apply_filters('cfs_should_filter_query', $should_filter, $query, $has_cfs_params);

        if (!$should_filter) {
            return;
        }

        // Get all facet parameters
        $this->active_filters = $this->get_active_filters();

        if (empty($this->active_filters)) {
            return;
        }

        // Debug logging
        if (apply_filters('cfs_debug_mode', false)) {
            error_log('CFS Active Filters: ' . print_r($this->active_filters, true));
        }
        
        // Build meta query
        $meta_query = $query->get('meta_query') ?: [];
        $tax_query = $query->get('tax_query') ?: [];
        
        foreach ($this->active_filters as $filter) {
            $facet = CFS_Facets::instance()->get_facet($filter['slug']);
            
            if (!$facet) {
                continue;
            }
            
            // Handle taxonomy filters
            if ($facet->source === 'taxonomy') {
                $tax_query[] = [
                    'taxonomy' => $facet->source_key,
                    'field' => 'slug',
                    'terms' => $filter['values'],
                    'operator' => 'IN',
                ];
            }
            
            // Handle custom field filters
            if ($facet->source === 'custom_field') {
                $settings = $facet->settings ?? [];
                $data_type = $settings['data_type'] ?? 'auto';

                // Determine the meta type for proper comparison
                $meta_type = $this->get_meta_type($data_type, $filter, $facet);

                if ($facet->type === 'range') {
                    // Range query
                    $meta_query[] = [
                        'key' => $facet->source_key,
                        'value' => [$filter['min'], $filter['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN',
                    ];
                } elseif ($facet->type === 'search') {
                    // Search query (LIKE)
                    $meta_query[] = [
                        'key' => $facet->source_key,
                        'value' => '%' . $wpdb->esc_like($filter['values'][0]) . '%',
                        'compare' => 'LIKE',
                    ];
                } elseif ($facet->type === 'date') {
                    if (isset($filter['from']) && isset($filter['to'])) {
                        $meta_query[] = [
                            'key' => $facet->source_key,
                            'value' => [$filter['from'], $filter['to']],
                            'type' => 'DATE',
                            'compare' => 'BETWEEN',
                        ];
                    } else {
                        $meta_query[] = [
                            'key' => $facet->source_key,
                            'value' => $filter['values'][0],
                            'type' => 'DATE',
                            'compare' => '=',
                        ];
                    }
                } elseif ($facet->type === 'rating') {
                    // Rating (greater than or equal)
                    $meta_query[] = [
                        'key' => $facet->source_key,
                        'value' => $filter['values'][0],
                        'type' => 'NUMERIC',
                        'compare' => '>=',
                    ];
                } else {
                    // Standard value comparison with proper type handling
                    $query_args = [
                        'key' => $facet->source_key,
                        'compare' => count($filter['values']) === 1 ? '=' : 'IN',
                        'value' => count($filter['values']) === 1 ? $filter['values'][0] : $filter['values'],
                    ];

                    // Add type for numeric comparisons
                    if ($meta_type === 'NUMERIC') {
                        $query_args['type'] = 'NUMERIC';
                    } elseif ($meta_type === 'DECIMAL') {
                        $query_args['type'] = 'DECIMAL(10,2)';
                    }

                    $meta_query[] = $query_args;
                }
            }
            
            // Handle post attribute filters
            if ($facet->source === 'post_attribute') {
                switch ($facet->source_key) {
                    case 'post_author':
                        $query->set('author__in', $filter['values']);
                        break;
                    case 'post_date':
                        // Handle date range
                        if (isset($filter['from'])) {
                            $query->set('date_query', [
                                [
                                    'after' => $filter['from'],
                                    'before' => $filter['to'] ?? '',
                                    'inclusive' => true,
                                ]
                            ]);
                        }
                        break;
                }
            }
        }
        
        // Apply meta query
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $existing_meta = $query->get('meta_query') ?: [];
            $merged_meta = array_merge($existing_meta, $meta_query);
            $query->set('meta_query', $merged_meta);

            // Debug logging
            if (apply_filters('cfs_debug_mode', false)) {
                error_log('CFS Meta Query: ' . print_r($merged_meta, true));
            }
        }

        // Apply tax query
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $existing_tax = $query->get('tax_query') ?: [];
            $merged_tax = array_merge($existing_tax, $tax_query);
            $query->set('tax_query', $merged_tax);

            // Debug logging
            if (apply_filters('cfs_debug_mode', false)) {
                error_log('CFS Tax Query: ' . print_r($merged_tax, true));
            }
        }
        
        // Handle search facet for post content/title
        foreach ($this->active_filters as $filter) {
            $facet = CFS_Facets::instance()->get_facet($filter['slug']);
            
            if ($facet && $facet->type === 'search' && $facet->source === 'post_attribute') {
                $query->set('s', $filter['values'][0]);
            }
        }
    }
    
    /**
     * Get active filters from URL parameters
     */
    public function get_active_filters() {
        $filters = [];
        
        foreach ($_GET as $key => $value) {
            // Check for CFS parameters
            if (strpos($key, 'cfs_') !== 0) {
                continue;
            }
            
            // Skip empty values
            if ($value === '' || (is_array($value) && empty(array_filter($value)))) {
                continue;
            }
            
            // Handle range parameters
            if (preg_match('/^cfs_(.+)_(min|max)$/', $key, $matches)) {
                $slug = $matches[1];
                $type = $matches[2];
                
                if (!isset($filters[$slug])) {
                    $filters[$slug] = [
                        'slug' => $slug,
                        'type' => 'range',
                        'min' => null,
                        'max' => null,
                    ];
                }
                
                $filters[$slug][$type] = floatval($value);
                continue;
            }
            
            // Handle date range parameters
            if (preg_match('/^cfs_(.+)_(from|to)$/', $key, $matches)) {
                $slug = $matches[1];
                $type = $matches[2];
                
                if (!isset($filters[$slug])) {
                    $filters[$slug] = [
                        'slug' => $slug,
                        'type' => 'date_range',
                        'from' => null,
                        'to' => null,
                    ];
                }
                
                $filters[$slug][$type] = sanitize_text_field($value);
                continue;
            }
            
            // Standard parameters
            $slug = str_replace('cfs_', '', $key);
            $values = is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)];
            
            $filters[$slug] = [
                'slug' => $slug,
                'values' => $values,
            ];
        }
        
        return array_values($filters);
    }
    
    /**
     * Determine the meta type for proper comparison
     */
    private function get_meta_type($data_type, $filter, $facet) {
        // If explicitly set, use that
        if ($data_type === 'numeric') {
            return 'NUMERIC';
        } elseif ($data_type === 'decimal') {
            return 'DECIMAL';
        } elseif ($data_type === 'text') {
            return 'CHAR';
        }

        // Auto-detect: check if all filter values are numeric
        if (isset($filter['values']) && !empty($filter['values'])) {
            $all_numeric = true;
            foreach ($filter['values'] as $value) {
                if (!is_numeric($value)) {
                    $all_numeric = false;
                    break;
                }
            }
            if ($all_numeric) {
                // Check if any value contains a decimal point
                foreach ($filter['values'] as $value) {
                    if (strpos((string)$value, '.') !== false) {
                        return 'DECIMAL';
                    }
                }
                return 'NUMERIC';
            }
        }

        return 'CHAR';
    }

    /**
     * Custom WHERE clause for advanced queries
     */
    public function custom_where($where, $query) {
        if (!$query->get('cfs_custom_where')) {
            return $where;
        }
        
        // Add custom WHERE conditions here if needed
        return $where;
    }
    
    /**
     * Custom JOIN clause for advanced queries
     */
    public function custom_join($join, $query) {
        if (!$query->get('cfs_custom_join')) {
            return $join;
        }
        
        // Add custom JOIN conditions here if needed
        return $join;
    }
    
    /**
     * Get filtered results for AJAX
     */
    public function get_filtered_results($args = []) {
        $defaults = [
            'post_type' => 'post',
            'posts_per_page' => 12,
            'paged' => 1,
            'cfs_filter' => true,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $query = new WP_Query($args);
        
        return [
            'posts' => $query->posts,
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $query->get('paged'),
        ];
    }
    
    /**
     * Build URL with current filters
     */
    public static function build_filter_url($base_url = '', $filters = []) {
        if (empty($base_url)) {
            $base_url = get_permalink();
        }
        
        $params = [];
        
        foreach ($filters as $slug => $value) {
            if (is_array($value)) {
                if (isset($value['min']) && isset($value['max'])) {
                    $params['cfs_' . $slug . '_min'] = $value['min'];
                    $params['cfs_' . $slug . '_max'] = $value['max'];
                } else {
                    $params['cfs_' . $slug] = $value;
                }
            } else {
                $params['cfs_' . $slug] = $value;
            }
        }
        
        return add_query_arg($params, $base_url);
    }
    
    /**
     * Get active filter summary
     */
    public static function get_active_filter_summary() {
        $instance = self::instance();
        $filters = $instance->get_active_filters();
        $summary = [];
        
        foreach ($filters as $filter) {
            $facet = CFS_Facets::instance()->get_facet($filter['slug']);
            
            if (!$facet) {
                continue;
            }
            
            $settings = $facet->settings;
            $label = $settings['label'] ?? $facet->name;
            
            if ($facet->type === 'range') {
                $prefix = $settings['prefix'] ?? '';
                $suffix = $settings['suffix'] ?? '';
                $value_text = $prefix . $filter['min'] . $suffix . ' – ' . $prefix . $filter['max'] . $suffix;
            } else {
                $value_text = implode(', ', $filter['values']);
            }
            
            $summary[] = [
                'slug' => $filter['slug'],
                'label' => $label,
                'value' => $value_text,
                'remove_url' => self::get_remove_filter_url($filter['slug']),
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get URL with filter removed
     */
    public static function get_remove_filter_url($slug) {
        $params_to_remove = [
            'cfs_' . $slug,
            'cfs_' . $slug . '_min',
            'cfs_' . $slug . '_max',
            'cfs_' . $slug . '_from',
            'cfs_' . $slug . '_to',
        ];
        
        $url = remove_query_arg($params_to_remove);
        
        return $url;
    }
    
    /**
     * Get reset all filters URL
     */
    public static function get_reset_url() {
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        return home_url($url);
    }
}
