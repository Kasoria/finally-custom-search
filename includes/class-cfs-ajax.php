<?php
/**
 * AJAX handler for faceted search
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Ajax {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_cfs_filter', [$this, 'handle_filter']);
        add_action('wp_ajax_nopriv_cfs_filter', [$this, 'handle_filter']);
        
        add_action('wp_ajax_cfs_get_counts', [$this, 'get_facet_counts']);
        add_action('wp_ajax_nopriv_cfs_get_counts', [$this, 'get_facet_counts']);
        
        add_action('wp_ajax_cfs_load_more', [$this, 'load_more']);
        add_action('wp_ajax_nopriv_cfs_load_more', [$this, 'load_more']);
    }
    
    /**
     * Handle main filter AJAX request
     */
    public function handle_filter() {
        check_ajax_referer('cfs_nonce', 'nonce');

        // Get parameters
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $posts_per_page = intval($_POST['posts_per_page'] ?? 12);
        $paged = intval($_POST['paged'] ?? 1);
        $template = sanitize_text_field($_POST['template'] ?? '');
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');

        // Parse filters from POST data
        $filters = [];
        if (!empty($_POST['filters'])) {
            parse_str($_POST['filters'], $filters);
        }

        // Debug logging (can be enabled via filter)
        if (apply_filters('cfs_debug_mode', false)) {
            error_log('CFS Filter Request: ' . print_r([
                'post_type' => $post_type,
                'filters' => $filters,
                'orderby' => $orderby,
                'order' => $order
            ], true));
        }

        // Temporarily set GET parameters for query modification
        $_GET = array_merge($_GET, $filters);
        
        // Build query args
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
            'cfs_filter' => true,
        ];
        
        $query = new WP_Query($args);
        
        // Render results
        ob_start();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                if (!empty($template) && file_exists($template)) {
                    include $template;
                } else {
                    $this->render_default_item();
                }
            }
        } else {
            $this->render_no_results();
        }
        
        $html = ob_get_clean();
        
        // Render pagination
        ob_start();
        $this->render_pagination($query);
        $pagination = ob_get_clean();
        
        wp_reset_postdata();
        
        // Get active filter summary
        $active_filters = CFS_Query::get_active_filter_summary();
        
        wp_send_json_success([
            'html' => $html,
            'pagination' => $pagination,
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $paged,
            'active_filters' => $active_filters,
        ]);
    }
    
    /**
     * Get facet counts for current filter state
     */
    public function get_facet_counts() {
        check_ajax_referer('cfs_nonce', 'nonce');
        
        $facet_slug = sanitize_text_field($_POST['facet'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        
        $facet = CFS_Facets::instance()->get_facet($facet_slug);
        
        if (!$facet) {
            wp_send_json_error(['message' => 'Facet not found']);
        }
        
        $counts = [];
        
        if ($facet->source === 'taxonomy') {
            $terms = get_terms([
                'taxonomy' => $facet->source_key,
                'hide_empty' => true,
            ]);
            
            foreach ($terms as $term) {
                $counts[$term->slug] = $term->count;
            }
        } elseif ($facet->source === 'custom_field') {
            global $wpdb;
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT pm.meta_value, COUNT(*) as count
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type = %s
                AND p.post_status = 'publish'
                AND pm.meta_key = %s
                AND pm.meta_value != ''
                GROUP BY pm.meta_value
            ", $post_type, $facet->source_key));
            
            foreach ($results as $row) {
                $counts[$row->meta_value] = intval($row->count);
            }
        }
        
        wp_send_json_success(['counts' => $counts]);
    }
    
    /**
     * Load more posts
     */
    public function load_more() {
        check_ajax_referer('cfs_nonce', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $posts_per_page = intval($_POST['posts_per_page'] ?? 12);
        $paged = intval($_POST['paged'] ?? 1);
        $template = sanitize_text_field($_POST['template'] ?? '');
        
        // Parse filters
        $filters = [];
        if (!empty($_POST['filters'])) {
            parse_str($_POST['filters'], $filters);
        }
        
        $_GET = array_merge($_GET, $filters);
        
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => 'publish',
            'cfs_filter' => true,
        ];
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                if (!empty($template) && file_exists($template)) {
                    include $template;
                } else {
                    $this->render_default_item();
                }
            }
        }
        
        $html = ob_get_clean();
        wp_reset_postdata();
        
        wp_send_json_success([
            'html' => $html,
            'has_more' => $paged < $query->max_num_pages,
        ]);
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
     * Render no results message
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
        $settings = get_option('cfs_settings', []);
        $pagination_type = $settings['pagination_type'] ?? 'standard';
        
        if ($query->max_num_pages <= 1) {
            return;
        }
        
        $current_page = max(1, $query->get('paged'));
        $total_pages = $query->max_num_pages;
        
        if ($pagination_type === 'load_more') {
            ?>
            <div class="cfs-pagination cfs-load-more-wrapper">
                <?php if ($current_page < $total_pages): ?>
                    <button type="button" class="cfs-load-more" data-page="<?php echo $current_page + 1; ?>">
                        <?php esc_html_e('Load More', 'custom-facet-search'); ?>
                    </button>
                <?php endif; ?>
                <span class="cfs-pagination-info">
                    <?php printf(
                        esc_html__('Page %1$d of %2$d', 'custom-facet-search'),
                        $current_page,
                        $total_pages
                    ); ?>
                </span>
            </div>
            <?php
        } else {
            ?>
            <div class="cfs-pagination cfs-standard-pagination">
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
}
