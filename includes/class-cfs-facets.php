<?php
/**
 * Facets rendering class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Facets {
    
    private static $instance = null;
    private $facets_cache = [];
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Cache facets on init
        add_action('wp', [$this, 'cache_facets']);
    }
    
    public function cache_facets() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        $facets = $wpdb->get_results("SELECT * FROM $table_name");
        
        foreach ($facets as $facet) {
            $facet->settings = json_decode($facet->settings, true);
            $this->facets_cache[$facet->slug] = $facet;
        }
    }
    
    /**
     * Get facet by slug
     */
    public function get_facet($slug) {
        if (isset($this->facets_cache[$slug])) {
            return $this->facets_cache[$slug];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        $facet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE slug = %s",
            $slug
        ));
        
        if ($facet) {
            $facet->settings = json_decode($facet->settings, true);
            $this->facets_cache[$slug] = $facet;
        }
        
        return $facet;
    }
    
    /**
     * Render a facet
     */
    public function render($slug, $args = []) {
        $facet = $this->get_facet($slug);
        
        if (!$facet) {
            return '';
        }
        
        $defaults = [
            'class' => '',
            'show_label' => true,
            'target_grid' => '',
            'post_type' => '',
            'posts_per_page' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $settings = $facet->settings;

        // Get current filter values from URL
        $current_values = $this->get_current_values($facet);

        // Build target selector
        $target_grid = $args['target_grid'];
        $post_type = $args['post_type'];
        $posts_per_page = $args['posts_per_page'];

        // Build output
        ob_start();

        $wrapper_class = 'cfs-facet cfs-facet-' . esc_attr($facet->type) . ' ' . esc_attr($args['class']);

        // Build data attributes
        $data_attrs = [
            'data-facet="' . esc_attr($slug) . '"',
            'data-type="' . esc_attr($facet->type) . '"',
            'data-source="' . esc_attr($facet->source) . '"',
        ];
        if ($target_grid) {
            $data_attrs[] = 'data-target-grid="' . esc_attr($target_grid) . '"';
        }
        if ($post_type) {
            $data_attrs[] = 'data-post-type="' . esc_attr($post_type) . '"';
        }
        if ($posts_per_page) {
            $data_attrs[] = 'data-posts-per-page="' . esc_attr($posts_per_page) . '"';
        }
        ?>
        <div class="<?php echo esc_attr(trim($wrapper_class)); ?>" <?php echo implode(' ', $data_attrs); ?>>
            
            <?php if ($args['show_label'] && !empty($settings['label'])): ?>
                <label class="cfs-facet-label"><?php echo esc_html($settings['label']); ?></label>
            <?php endif; ?>
            
            <div class="cfs-facet-inner">
                <?php
                switch ($facet->type) {
                    case 'checkbox':
                        $this->render_checkbox($facet, $current_values);
                        break;
                    case 'radio':
                        $this->render_radio($facet, $current_values);
                        break;
                    case 'dropdown':
                        $this->render_dropdown($facet, $current_values);
                        break;
                    case 'range':
                        $this->render_range($facet, $current_values);
                        break;
                    case 'search':
                        $this->render_search($facet, $current_values);
                        break;
                    case 'date':
                        $this->render_date($facet, $current_values);
                        break;
                    case 'rating':
                        $this->render_rating($facet, $current_values);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get current filter values from URL
     */
    private function get_current_values($facet) {
        $param_name = 'cfs_' . $facet->slug;
        
        if ($facet->type === 'range') {
            return [
                'min' => isset($_GET[$param_name . '_min']) ? floatval($_GET[$param_name . '_min']) : null,
                'max' => isset($_GET[$param_name . '_max']) ? floatval($_GET[$param_name . '_max']) : null,
            ];
        }
        
        if (isset($_GET[$param_name])) {
            $value = $_GET[$param_name];
            return is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)];
        }
        
        return [];
    }
    
    /**
     * Get facet choices (for taxonomy or custom field)
     */
    private function get_choices($facet) {
        $settings = $facet->settings;
        $choices = [];
        
        if ($facet->source === 'taxonomy') {
            $args = [
                'taxonomy' => $facet->source_key,
                'hide_empty' => $settings['hide_empty'] ?? true,
                'orderby' => $settings['orderby'] ?? 'name',
                'order' => $settings['order'] ?? 'ASC',
            ];
            
            if (!empty($settings['hierarchical'])) {
                $args['parent'] = 0;
            }
            
            $terms = get_terms($args);
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $choices[] = [
                        'value' => $term->slug,
                        'label' => $term->name,
                        'count' => $term->count,
                    ];
                }
            }
        } elseif ($facet->source === 'custom_field') {
            global $wpdb;

            // Get post types - use from settings, or fall back to 'post'
            $post_types = $settings['post_types'] ?? ['post'];
            if (empty($post_types)) {
                $post_types = ['post'];
            }
            $post_types_placeholder = implode(',', array_fill(0, count($post_types), '%s'));

            // Determine the data type to optimize sorting
            $data_type = $settings['data_type'] ?? 'auto';

            // Build the ORDER BY clause based on data type
            $order_by = 'pm.meta_value ASC';
            if ($data_type === 'numeric' || $data_type === 'decimal') {
                $order_by = 'CAST(pm.meta_value AS DECIMAL(20,4)) ASC';
            }

            $values = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT TRIM(pm.meta_value) as meta_value
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type IN ($post_types_placeholder)
                AND p.post_status = 'publish'
                AND pm.meta_key = %s
                AND pm.meta_value != ''
                AND TRIM(pm.meta_value) != ''
                ORDER BY $order_by
            ", array_merge($post_types, [$facet->source_key])));

            // Get counts for each value if show_count is enabled
            $show_count = $settings['show_count'] ?? false;
            $counts = [];

            if ($show_count && !empty($values)) {
                $count_results = $wpdb->get_results($wpdb->prepare("
                    SELECT TRIM(pm.meta_value) as meta_value, COUNT(DISTINCT p.ID) as count
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                    WHERE p.post_type IN ($post_types_placeholder)
                    AND p.post_status = 'publish'
                    AND pm.meta_key = %s
                    AND pm.meta_value != ''
                    GROUP BY TRIM(pm.meta_value)
                ", array_merge($post_types, [$facet->source_key])));

                foreach ($count_results as $row) {
                    $counts[$row->meta_value] = intval($row->count);
                }
            }

            foreach ($values as $value) {
                // Skip empty values after trim
                if (empty($value)) {
                    continue;
                }

                $choices[] = [
                    'value' => $value,
                    'label' => $value,
                    'count' => $counts[$value] ?? null,
                ];
            }
        } elseif ($facet->source === 'post_attribute') {
            // Handle post attributes like post_author, post_status, etc.
            if ($facet->source_key === 'post_author') {
                $authors = get_users(['who' => 'authors']);
                foreach ($authors as $author) {
                    $choices[] = [
                        'value' => $author->ID,
                        'label' => $author->display_name,
                        'count' => count_user_posts($author->ID),
                    ];
                }
            }
        }
        
        return $choices;
    }
    
    /**
     * Render checkbox facet
     */
    private function render_checkbox($facet, $current_values) {
        $choices = $this->get_choices($facet);
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        
        if (empty($choices)) {
            echo '<p class="cfs-no-choices">' . esc_html__('No options available.', 'custom-facet-search') . '</p>';
            return;
        }
        ?>
        <div class="cfs-checkbox-list">
            <?php foreach ($choices as $choice): 
                $checked = in_array($choice['value'], $current_values);
                $id = $name . '_' . sanitize_title($choice['value']);
            ?>
                <label class="cfs-checkbox-item" for="<?php echo esc_attr($id); ?>">
                    <input type="checkbox" 
                           id="<?php echo esc_attr($id); ?>"
                           name="<?php echo esc_attr($name); ?>[]" 
                           value="<?php echo esc_attr($choice['value']); ?>"
                           <?php checked($checked); ?>>
                    <span class="cfs-checkbox-label"><?php echo esc_html($choice['label']); ?></span>
                    <?php if ($settings['show_count'] && $choice['count'] !== null): ?>
                        <span class="cfs-count">(<?php echo intval($choice['count']); ?>)</span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render radio facet
     */
    private function render_radio($facet, $current_values) {
        $choices = $this->get_choices($facet);
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        $current = !empty($current_values) ? $current_values[0] : '';
        ?>
        <div class="cfs-radio-list">
            <label class="cfs-radio-item">
                <input type="radio" 
                       name="<?php echo esc_attr($name); ?>" 
                       value=""
                       <?php checked(empty($current)); ?>>
                <span class="cfs-radio-label"><?php esc_html_e('All', 'custom-facet-search'); ?></span>
            </label>
            <?php foreach ($choices as $choice): 
                $id = $name . '_' . sanitize_title($choice['value']);
            ?>
                <label class="cfs-radio-item" for="<?php echo esc_attr($id); ?>">
                    <input type="radio" 
                           id="<?php echo esc_attr($id); ?>"
                           name="<?php echo esc_attr($name); ?>" 
                           value="<?php echo esc_attr($choice['value']); ?>"
                           <?php checked($current, $choice['value']); ?>>
                    <span class="cfs-radio-label"><?php echo esc_html($choice['label']); ?></span>
                    <?php if ($settings['show_count'] && $choice['count'] !== null): ?>
                        <span class="cfs-count">(<?php echo intval($choice['count']); ?>)</span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render dropdown facet
     */
    private function render_dropdown($facet, $current_values) {
        $choices = $this->get_choices($facet);
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        $current = !empty($current_values) ? $current_values[0] : '';
        $multiple = !empty($settings['multiple']);
        ?>
        <select class="cfs-dropdown" 
                name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>"
                <?php echo $multiple ? 'multiple' : ''; ?>>
            <?php if (!$multiple): ?>
                <option value=""><?php echo esc_html($settings['placeholder'] ?? __('Select...', 'custom-facet-search')); ?></option>
            <?php endif; ?>
            <?php foreach ($choices as $choice): ?>
                <option value="<?php echo esc_attr($choice['value']); ?>"
                        <?php selected(in_array($choice['value'], $current_values)); ?>>
                    <?php echo esc_html($choice['label']); ?>
                    <?php if ($settings['show_count'] && $choice['count'] !== null): ?>
                        (<?php echo intval($choice['count']); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render range slider facet
     */
    private function render_range($facet, $current_values) {
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        
        $min = floatval($settings['min'] ?? 0);
        $max = floatval($settings['max'] ?? 100);
        $step = floatval($settings['step'] ?? 1);
        $prefix = $settings['prefix'] ?? '';
        $suffix = $settings['suffix'] ?? '';
        $inputs_enabled = $settings['inputs_enabled'] ?? true;
        
        $current_min = $current_values['min'] ?? $min;
        $current_max = $current_values['max'] ?? $max;
        ?>
        <div class="cfs-range-wrapper"
             data-min="<?php echo esc_attr($min); ?>"
             data-max="<?php echo esc_attr($max); ?>"
             data-step="<?php echo esc_attr($step); ?>"
             data-current-min="<?php echo esc_attr($current_min); ?>"
             data-current-max="<?php echo esc_attr($current_max); ?>"
             data-prefix="<?php echo esc_attr($prefix); ?>"
             data-suffix="<?php echo esc_attr($suffix); ?>">
            
            <div class="cfs-range-slider"></div>
            
            <div class="cfs-range-inputs">
                <div class="cfs-range-input-group">
                    <?php if ($prefix): ?>
                        <span class="cfs-range-prefix"><?php echo esc_html($prefix); ?></span>
                    <?php endif; ?>
                    <input type="number" 
                           class="cfs-range-input cfs-range-min" 
                           name="<?php echo esc_attr($name); ?>_min"
                           value="<?php echo esc_attr($current_min); ?>"
                           min="<?php echo esc_attr($min); ?>"
                           max="<?php echo esc_attr($max); ?>"
                           step="<?php echo esc_attr($step); ?>"
                           <?php echo $inputs_enabled ? '' : 'readonly'; ?>>
                    <?php if ($suffix): ?>
                        <span class="cfs-range-suffix"><?php echo esc_html($suffix); ?></span>
                    <?php endif; ?>
                </div>
                
                <span class="cfs-range-separator">–</span>
                
                <div class="cfs-range-input-group">
                    <?php if ($prefix): ?>
                        <span class="cfs-range-prefix"><?php echo esc_html($prefix); ?></span>
                    <?php endif; ?>
                    <input type="number" 
                           class="cfs-range-input cfs-range-max" 
                           name="<?php echo esc_attr($name); ?>_max"
                           value="<?php echo esc_attr($current_max); ?>"
                           min="<?php echo esc_attr($min); ?>"
                           max="<?php echo esc_attr($max); ?>"
                           step="<?php echo esc_attr($step); ?>"
                           <?php echo $inputs_enabled ? '' : 'readonly'; ?>>
                    <?php if ($suffix): ?>
                        <span class="cfs-range-suffix"><?php echo esc_html($suffix); ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="cfs-range-reset" <?php echo ($current_min == $min && $current_max == $max) ? 'disabled' : ''; ?>>
                    <?php esc_html_e('Reset', 'custom-facet-search'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render search facet
     */
    private function render_search($facet, $current_values) {
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        $current = !empty($current_values) ? $current_values[0] : '';
        ?>
        <div class="cfs-search-wrapper">
            <input type="text" 
                   class="cfs-search-input" 
                   name="<?php echo esc_attr($name); ?>"
                   value="<?php echo esc_attr($current); ?>"
                   placeholder="<?php echo esc_attr($settings['placeholder'] ?? __('Search...', 'custom-facet-search')); ?>">
            <button type="button" class="cfs-search-clear" <?php echo empty($current) ? 'style="display:none;"' : ''; ?>>
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
    }
    
    /**
     * Render date facet
     */
    private function render_date($facet, $current_values) {
        $settings = $facet->settings;
        $name = 'cfs_' . $facet->slug;
        $date_type = $settings['date_type'] ?? 'single';
        
        if ($date_type === 'range') {
            $current_from = !empty($current_values) && isset($current_values[0]) ? $current_values[0] : '';
            $current_to = !empty($current_values) && isset($current_values[1]) ? $current_values[1] : '';
            ?>
            <div class="cfs-date-range">
                <input type="date" 
                       class="cfs-date-input cfs-date-from" 
                       name="<?php echo esc_attr($name); ?>_from"
                       value="<?php echo esc_attr($current_from); ?>">
                <span class="cfs-date-separator"><?php esc_html_e('to', 'custom-facet-search'); ?></span>
                <input type="date" 
                       class="cfs-date-input cfs-date-to" 
                       name="<?php echo esc_attr($name); ?>_to"
                       value="<?php echo esc_attr($current_to); ?>">
            </div>
            <?php
        } else {
            $current = !empty($current_values) ? $current_values[0] : '';
            ?>
            <input type="date" 
                   class="cfs-date-input" 
                   name="<?php echo esc_attr($name); ?>"
                   value="<?php echo esc_attr($current); ?>">
            <?php
        }
    }
    
    /**
     * Render rating facet
     */
    private function render_rating($facet, $current_values) {
        $name = 'cfs_' . $facet->slug;
        $current = !empty($current_values) ? intval($current_values[0]) : 0;
        ?>
        <div class="cfs-rating-wrapper">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <label class="cfs-rating-item">
                    <input type="radio" 
                           name="<?php echo esc_attr($name); ?>" 
                           value="<?php echo $i; ?>"
                           <?php checked($current, $i); ?>>
                    <span class="cfs-rating-stars">
                        <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                    </span>
                    <span class="cfs-rating-text"><?php printf(__('%d & up', 'custom-facet-search'), $i); ?></span>
                </label>
            <?php endfor; ?>
            <label class="cfs-rating-item">
                <input type="radio" 
                       name="<?php echo esc_attr($name); ?>" 
                       value=""
                       <?php checked($current, 0); ?>>
                <span class="cfs-rating-text"><?php esc_html_e('All ratings', 'custom-facet-search'); ?></span>
            </label>
        </div>
        <?php
    }
}
