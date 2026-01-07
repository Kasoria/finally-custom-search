<?php
/**
 * Bricks Builder Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Bricks {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check if Bricks is active
        if (!$this->is_bricks_active()) {
            return;
        }

        // Register custom element category
        add_filter('bricks/builder/i18n', [$this, 'add_element_category']);

        // Register custom elements
        add_action('init', [$this, 'register_elements'], 11);

        // Add post IDs to Bricks loop items for client-side filtering
        add_filter('bricks/element/render_attributes', [$this, 'add_post_id_to_loop_items'], 10, 3);

        // Alternative: Add via the_post action for query loops
        add_action('the_post', [$this, 'track_loop_post_id'], 10, 2);

        // AJAX endpoint for getting matching post IDs
        add_action('wp_ajax_cfs_get_matching_ids', [$this, 'ajax_get_matching_ids']);
        add_action('wp_ajax_nopriv_cfs_get_matching_ids', [$this, 'ajax_get_matching_ids']);
    }

    /**
     * Check if Bricks Builder is active
     */
    private function is_bricks_active() {
        return defined('BRICKS_VERSION') || class_exists('\Bricks\Elements');
    }

    /**
     * Add custom element category to Bricks
     */
    public function add_element_category($i18n) {
        $i18n['cfs'] = esc_html__('Facet Search', 'custom-facet-search');
        return $i18n;
    }

    /**
     * Register custom elements with Bricks
     */
    public function register_elements() {
        // Make sure Bricks Elements class exists
        if (!class_exists('\Bricks\Elements')) {
            return;
        }

        // Element files to register
        $element_files = [
            CFS_PLUGIN_DIR . 'includes/bricks/class-cfs-bricks-facet.php',
            CFS_PLUGIN_DIR . 'includes/bricks/class-cfs-bricks-results.php',
        ];

        foreach ($element_files as $file) {
            if (file_exists($file)) {
                \Bricks\Elements::register_element($file);
            }
        }
    }

    /**
     * Add post ID as data attribute to Bricks loop items
     * This enables client-side filtering without replacing content
     */
    public function add_post_id_to_loop_items($attributes, $key, $element) {
        // Check if we're inside a query loop
        global $post;

        if ($post && isset($attributes['_root'])) {
            // Add data-cfs-post-id attribute
            if (!isset($attributes['_root']['data-cfs-post-id'])) {
                $attributes['_root']['data-cfs-post-id'] = $post->ID;
            }
        }

        return $attributes;
    }

    /**
     * Track post ID during loop iteration
     * Outputs a data attribute marker that JS can use
     */
    public function track_loop_post_id($post, $query) {
        // Only on frontend, not in builder or admin
        if (is_admin() || (function_exists('bricks_is_builder') && bricks_is_builder())) {
            return;
        }

        // Add inline script to mark the current element with post ID
        // This is a fallback method
        static $script_added = false;
        if (!$script_added) {
            add_action('wp_footer', function() {
                ?>
                <script>
                (function() {
                    // Mark Bricks loop items with post IDs
                    document.querySelectorAll('[class*="brxe-"]').forEach(function(el) {
                        if (!el.dataset.cfsPostId) {
                            // Try to find post ID from siblings or data
                            var postLink = el.querySelector('a[href*="/?p="], a[href*="/"]');
                            // This is a basic approach - the PHP filter is more reliable
                        }
                    });
                })();
                </script>
                <?php
            }, 999);
            $script_added = true;
        }
    }

    /**
     * AJAX handler to get matching post IDs for filters
     * Used for client-side filtering of Bricks loops
     */
    public function ajax_get_matching_ids() {
        check_ajax_referer('cfs_nonce', 'nonce');

        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $posts_per_page = intval($_POST['posts_per_page'] ?? -1); // -1 = all
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');

        // Parse filters from POST data
        $filters = [];
        if (!empty($_POST['filters'])) {
            parse_str($_POST['filters'], $filters);
        }

        // Set GET parameters for query modification
        $_GET = array_merge($_GET, $filters);

        // Build query args - get only IDs for performance
        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts_per_page,
            'orderby'        => $orderby,
            'order'          => $order,
            'post_status'    => 'publish',
            'fields'         => 'ids', // Only get IDs for performance
            'cfs_filter'     => true,
        ];

        $query = new WP_Query($args);

        wp_send_json_success([
            'post_ids'    => $query->posts,
            'found_posts' => $query->found_posts,
            'max_pages'   => $query->max_num_pages,
        ]);
    }
}
