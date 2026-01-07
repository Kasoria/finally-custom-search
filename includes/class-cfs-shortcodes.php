<?php
/**
 * Shortcodes for Custom Facet Search
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Shortcodes {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('cfs_facet', [$this, 'render_facet']);
        add_shortcode('cfs_results', [$this, 'render_results']);
        add_shortcode('cfs_active_filters', [$this, 'render_active_filters']);
        add_shortcode('cfs_reset', [$this, 'render_reset_button']);
        add_shortcode('cfs_count', [$this, 'render_results_count']);
        add_shortcode('cfs_form', [$this, 'render_form_wrapper']);
    }
    
    /**
     * Render a single facet
     * [cfs_facet slug="my-facet" show_label="true" class="custom-class" target_grid="grid-id"]
     */
    public function render_facet($atts) {
        $atts = shortcode_atts([
            'slug' => '',
            'show_label' => 'true',
            'class' => '',
            'target_grid' => '',
        ], $atts);
        
        if (empty($atts['slug'])) {
            return '<!-- CFS Error: No facet slug specified -->';
        }
        
        return CFS_Facets::instance()->render($atts['slug'], [
            'show_label' => $atts['show_label'] === 'true',
            'class' => $atts['class'],
            'target_grid' => $atts['target_grid'],
        ]);
    }
    
    /**
     * Render results container
     * [cfs_results post_type="post" posts_per_page="12" template="" columns="3" grid_id="my-grid"]
     */
    public function render_results($atts) {
        $atts = shortcode_atts([
            'post_type' => 'post',
            'posts_per_page' => 12,
            'template' => '',
            'columns' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'class' => '',
            'grid_id' => '',
        ], $atts);
        
        // Generate grid ID if not provided
        $grid_id = !empty($atts['grid_id']) ? $atts['grid_id'] : 'cfs-grid-' . uniqid();
        
        // Initial query
        $args = [
            'post_type' => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
            'cfs_filter' => true,
        ];
        
        $query = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="cfs-results-wrapper <?php echo esc_attr($atts['class']); ?>"
             id="<?php echo esc_attr($grid_id); ?>"
             data-grid-id="<?php echo esc_attr($grid_id); ?>"
             data-post-type="<?php echo esc_attr($atts['post_type']); ?>"
             data-posts-per-page="<?php echo esc_attr($atts['posts_per_page']); ?>"
             data-template="<?php echo esc_attr($atts['template']); ?>"
             data-orderby="<?php echo esc_attr($atts['orderby']); ?>"
             data-order="<?php echo esc_attr($atts['order']); ?>">
            
            <div class="cfs-results-header">
                <span class="cfs-results-count">
                    <?php printf(
                        esc_html(_n('%d result', '%d results', $query->found_posts, 'custom-facet-search')),
                        $query->found_posts
                    ); ?>
                </span>
                
                <div class="cfs-sort-wrapper">
                    <label for="cfs-sort"><?php esc_html_e('Sort by:', 'custom-facet-search'); ?></label>
                    <select id="cfs-sort" class="cfs-sort-select">
                        <option value="date-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'date-DESC'); ?>>
                            <?php esc_html_e('Newest first', 'custom-facet-search'); ?>
                        </option>
                        <option value="date-ASC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'date-ASC'); ?>>
                            <?php esc_html_e('Oldest first', 'custom-facet-search'); ?>
                        </option>
                        <option value="title-ASC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'title-ASC'); ?>>
                            <?php esc_html_e('Title A-Z', 'custom-facet-search'); ?>
                        </option>
                        <option value="title-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'title-DESC'); ?>>
                            <?php esc_html_e('Title Z-A', 'custom-facet-search'); ?>
                        </option>
                        <option value="menu_order-ASC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'menu_order-ASC'); ?>>
                            <?php esc_html_e('Menu order', 'custom-facet-search'); ?>
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="cfs-results cfs-columns-<?php echo intval($atts['columns']); ?>">
                <?php
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        
                        if (!empty($atts['template']) && locate_template($atts['template'])) {
                            get_template_part($atts['template']);
                        } else {
                            $this->render_default_item();
                        }
                    }
                } else {
                    $this->render_no_results();
                }
                ?>
            </div>
            
            <?php $this->render_pagination($query); ?>
            
            <div class="cfs-loading">
                <div class="cfs-spinner"></div>
            </div>
        </div>
        <?php
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render active filters
     * [cfs_active_filters show_clear_all="true"]
     */
    public function render_active_filters($atts) {
        $atts = shortcode_atts([
            'show_clear_all' => 'true',
        ], $atts);
        
        $active_filters = CFS_Query::get_active_filter_summary();
        
        if (empty($active_filters)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="cfs-active-filters">
            <span class="cfs-active-filters-label"><?php esc_html_e('Active filters:', 'custom-facet-search'); ?></span>
            
            <?php foreach ($active_filters as $filter): ?>
                <span class="cfs-active-filter">
                    <span class="cfs-filter-label"><?php echo esc_html($filter['label']); ?>:</span>
                    <span class="cfs-filter-value"><?php echo esc_html($filter['value']); ?></span>
                    <a href="<?php echo esc_url($filter['remove_url']); ?>" class="cfs-remove-filter" data-slug="<?php echo esc_attr($filter['slug']); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </a>
                </span>
            <?php endforeach; ?>
            
            <?php if ($atts['show_clear_all'] === 'true'): ?>
                <a href="<?php echo esc_url(CFS_Query::get_reset_url()); ?>" class="cfs-clear-all">
                    <?php esc_html_e('Clear all', 'custom-facet-search'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render reset button
     * [cfs_reset label="Reset Filters"]
     */
    public function render_reset_button($atts) {
        $atts = shortcode_atts([
            'label' => __('Reset Filters', 'custom-facet-search'),
            'class' => '',
        ], $atts);
        
        return sprintf(
            '<a href="%s" class="cfs-reset-btn %s">%s</a>',
            esc_url(CFS_Query::get_reset_url()),
            esc_attr($atts['class']),
            esc_html($atts['label'])
        );
    }
    
    /**
     * Render results count
     * [cfs_count]
     */
    public function render_results_count($atts) {
        $atts = shortcode_atts([
            'post_type' => 'post',
        ], $atts);
        
        $args = [
            'post_type' => $atts['post_type'],
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'cfs_filter' => true,
            'fields' => 'ids',
        ];
        
        $query = new WP_Query($args);
        
        return sprintf(
            '<span class="cfs-count-display">%s</span>',
            sprintf(
                esc_html(_n('%d result', '%d results', $query->found_posts, 'custom-facet-search')),
                $query->found_posts
            )
        );
    }
    
    /**
     * Form wrapper for facets (AJAX submission)
     * [cfs_form target=".cfs-results"] ... facets ... [/cfs_form]
     */
    public function render_form_wrapper($atts, $content = null) {
        $atts = shortcode_atts([
            'target' => '.cfs-results-wrapper',
            'auto_submit' => 'true',
            'submit_label' => __('Apply Filters', 'custom-facet-search'),
        ], $atts);
        
        ob_start();
        ?>
        <div class="cfs-form-wrapper" 
             data-target="<?php echo esc_attr($atts['target']); ?>"
             data-auto-submit="<?php echo esc_attr($atts['auto_submit']); ?>">
            
            <?php echo do_shortcode($content); ?>
            
            <?php if ($atts['auto_submit'] !== 'true'): ?>
                <button type="button" class="cfs-submit-btn">
                    <?php echo esc_html($atts['submit_label']); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render default item template
     */
    private function render_default_item() {
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('cfs-result-item'); ?>>
            <?php if (has_post_thumbnail()): ?>
                <div class="cfs-result-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="cfs-result-content">
                <h3 class="cfs-result-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <div class="cfs-result-excerpt">
                    <?php the_excerpt(); ?>
                </div>
                
                <div class="cfs-result-meta">
                    <span class="cfs-result-date"><?php echo get_the_date(); ?></span>
                </div>
            </div>
        </article>
        <?php
    }
    
    /**
     * Render no results
     */
    private function render_no_results() {
        ?>
        <div class="cfs-no-results">
            <p><?php esc_html_e('No results found matching your criteria.', 'custom-facet-search'); ?></p>
            <a href="<?php echo esc_url(CFS_Query::get_reset_url()); ?>" class="cfs-reset-all">
                <?php esc_html_e('Reset all filters', 'custom-facet-search'); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * Render pagination
     */
    private function render_pagination($query) {
        if ($query->max_num_pages <= 1) {
            return;
        }
        
        $current_page = max(1, $query->get('paged'));
        $total_pages = $query->max_num_pages;
        ?>
        <div class="cfs-pagination">
            <?php if ($current_page > 1): ?>
                <button type="button" class="cfs-page-btn cfs-prev" data-page="<?php echo $current_page - 1; ?>">
                    &laquo; <?php esc_html_e('Previous', 'custom-facet-search'); ?>
                </button>
            <?php endif; ?>
            
            <div class="cfs-page-numbers">
                <?php
                $range = 2;
                $start = max(1, $current_page - $range);
                $end = min($total_pages, $current_page + $range);
                
                if ($start > 1) {
                    echo '<button type="button" class="cfs-page-btn" data-page="1">1</button>';
                    if ($start > 2) {
                        echo '<span class="cfs-page-dots">...</span>';
                    }
                }
                
                for ($i = $start; $i <= $end; $i++) {
                    $active = $i === $current_page ? ' active' : '';
                    echo '<button type="button" class="cfs-page-btn' . $active . '" data-page="' . $i . '">' . $i . '</button>';
                }
                
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<span class="cfs-page-dots">...</span>';
                    }
                    echo '<button type="button" class="cfs-page-btn" data-page="' . $total_pages . '">' . $total_pages . '</button>';
                }
                ?>
            </div>
            
            <?php if ($current_page < $total_pages): ?>
                <button type="button" class="cfs-page-btn cfs-next" data-page="<?php echo $current_page + 1; ?>">
                    <?php esc_html_e('Next', 'custom-facet-search'); ?> &raquo;
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
}
