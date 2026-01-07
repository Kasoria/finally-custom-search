<?php
/**
 * Admin functionality for Custom Facet Search
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_cfs_save_facet', [$this, 'ajax_save_facet']);
        add_action('wp_ajax_cfs_delete_facet', [$this, 'ajax_delete_facet']);
        add_action('wp_ajax_cfs_get_meta_keys', [$this, 'ajax_get_meta_keys']);
        add_action('wp_ajax_cfs_get_taxonomy_terms', [$this, 'ajax_get_taxonomy_terms']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Facet Search', 'custom-facet-search'),
            __('Facet Search', 'custom-facet-search'),
            'manage_options',
            'custom-facet-search',
            [$this, 'render_facets_page'],
            'dashicons-filter',
            30
        );
        
        add_submenu_page(
            'custom-facet-search',
            __('All Facets', 'custom-facet-search'),
            __('All Facets', 'custom-facet-search'),
            'manage_options',
            'custom-facet-search',
            [$this, 'render_facets_page']
        );
        
        add_submenu_page(
            'custom-facet-search',
            __('Add New Facet', 'custom-facet-search'),
            __('Add New', 'custom-facet-search'),
            'manage_options',
            'cfs-add-facet',
            [$this, 'render_add_facet_page']
        );
        
        add_submenu_page(
            'custom-facet-search',
            __('Settings', 'custom-facet-search'),
            __('Settings', 'custom-facet-search'),
            'manage_options',
            'cfs-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('cfs_settings_group', 'cfs_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];

        $sanitized['enable_ajax'] = isset($input['enable_ajax']);
        $sanitized['ajax_url_update'] = isset($input['ajax_url_update']);
        $sanitized['scroll_to_results'] = isset($input['scroll_to_results']);
        $sanitized['loading_animation'] = isset($input['loading_animation']);
        $sanitized['results_container'] = sanitize_text_field($input['results_container'] ?? '.cfs-results');
        $sanitized['pagination_type'] = sanitize_text_field($input['pagination_type'] ?? 'standard');
        $sanitized['debug_mode'] = isset($input['debug_mode']);

        return $sanitized;
    }
    
    public function render_facets_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        // Handle edit mode
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_facet = null;
        
        if ($edit_id) {
            $edit_facet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
            if ($edit_facet) {
                $edit_facet->settings = json_decode($edit_facet->settings, true);
            }
        }
        
        $facets = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        include CFS_PLUGIN_DIR . 'templates/admin-facets.php';
    }
    
    public function render_add_facet_page() {
        include CFS_PLUGIN_DIR . 'templates/admin-add-facet.php';
    }
    
    public function render_settings_page() {
        $settings = get_option('cfs_settings', []);
        include CFS_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function ajax_save_facet() {
        check_ajax_referer('cfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'custom-facet-search')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($_POST['slug'] ?? $name);
        $type = sanitize_text_field($_POST['type'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? '');
        $source_key = sanitize_text_field($_POST['source_key'] ?? '');
        
        // Build settings array
        $settings = [
            'post_types' => array_map('sanitize_text_field', $_POST['post_types'] ?? []),
            'label' => sanitize_text_field($_POST['label'] ?? $name),
            'placeholder' => sanitize_text_field($_POST['placeholder'] ?? ''),
            'show_count' => isset($_POST['show_count']),
            'hide_empty' => isset($_POST['hide_empty']),
            'multiple' => isset($_POST['multiple']),
            'hierarchical' => isset($_POST['hierarchical']),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'name'),
            'order' => sanitize_text_field($_POST['order'] ?? 'ASC'),
            'data_type' => sanitize_text_field($_POST['data_type'] ?? 'auto'),
        ];
        
        // Range-specific settings
        if ($type === 'range') {
            $settings['min'] = floatval($_POST['min'] ?? 0);
            $settings['max'] = floatval($_POST['max'] ?? 100);
            $settings['step'] = floatval($_POST['step'] ?? 1);
            $settings['prefix'] = sanitize_text_field($_POST['prefix'] ?? '');
            $settings['suffix'] = sanitize_text_field($_POST['suffix'] ?? '');
            $settings['format'] = sanitize_text_field($_POST['format'] ?? 'number');
            $settings['inputs_enabled'] = isset($_POST['inputs_enabled']);
        }
        
        // Date-specific settings
        if ($type === 'date') {
            $settings['date_format'] = sanitize_text_field($_POST['date_format'] ?? 'Y-m-d');
            $settings['date_type'] = sanitize_text_field($_POST['date_type'] ?? 'single');
        }
        
        $data = [
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'source' => $source,
            'source_key' => $source_key,
            'settings' => wp_json_encode($settings),
        ];
        
        if ($id > 0) {
            // Update existing facet
            $result = $wpdb->update($table_name, $data, ['id' => $id]);
        } else {
            // Insert new facet
            $result = $wpdb->insert($table_name, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to save facet.', 'custom-facet-search')]);
        }
        
        wp_send_json_success([
            'message' => __('Facet saved successfully.', 'custom-facet-search'),
            'id' => $id,
            'shortcode' => "[cfs_facet slug=\"{$slug}\"]"
        ]);
    }
    
    public function ajax_delete_facet() {
        check_ajax_referer('cfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'custom-facet-search')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $wpdb->delete($table_name, ['id' => $id]);
            wp_send_json_success(['message' => __('Facet deleted.', 'custom-facet-search')]);
        }
        
        wp_send_json_error(['message' => __('Invalid facet ID.', 'custom-facet-search')]);
    }
    
    public function ajax_get_meta_keys() {
        check_ajax_referer('cfs_admin_nonce', 'nonce');
        
        global $wpdb;
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        
        // Get all unique meta keys for the post type
        $meta_keys = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_key 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND pm.meta_key NOT LIKE '\_%'
            ORDER BY pm.meta_key ASC
            LIMIT 200
        ", $post_type));
        
        wp_send_json_success(['meta_keys' => $meta_keys]);
    }
    
    public function ajax_get_taxonomy_terms() {
        check_ajax_referer('cfs_admin_nonce', 'nonce');
        
        $taxonomy = sanitize_text_field($_POST['taxonomy'] ?? '');
        
        if (empty($taxonomy)) {
            wp_send_json_error(['message' => __('No taxonomy specified.', 'custom-facet-search')]);
        }
        
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 100,
        ]);
        
        if (is_wp_error($terms)) {
            wp_send_json_error(['message' => $terms->get_error_message()]);
        }
        
        wp_send_json_success(['terms' => $terms]);
    }
    
    /**
     * Get all registered post types for admin
     */
    public static function get_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $excluded = ['attachment'];
        
        $result = [];
        foreach ($post_types as $post_type) {
            if (!in_array($post_type->name, $excluded)) {
                $result[$post_type->name] = $post_type->label;
            }
        }
        
        return $result;
    }
    
    /**
     * Get all registered taxonomies for admin
     */
    public static function get_taxonomies() {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        
        $result = [];
        foreach ($taxonomies as $taxonomy) {
            $result[$taxonomy->name] = $taxonomy->label;
        }
        
        return $result;
    }
    
    /**
     * Get facet types
     */
    public static function get_facet_types() {
        return [
            'checkbox' => __('Checkboxes', 'custom-facet-search'),
            'radio' => __('Radio Buttons', 'custom-facet-search'),
            'dropdown' => __('Dropdown', 'custom-facet-search'),
            'range' => __('Range Slider', 'custom-facet-search'),
            'search' => __('Search Box', 'custom-facet-search'),
            'date' => __('Date Picker', 'custom-facet-search'),
            'rating' => __('Rating', 'custom-facet-search'),
        ];
    }
    
    /**
     * Get data sources
     */
    public static function get_sources() {
        return [
            'taxonomy' => __('Taxonomy', 'custom-facet-search'),
            'custom_field' => __('Custom Field (Post Meta)', 'custom-facet-search'),
            'post_attribute' => __('Post Attribute', 'custom-facet-search'),
        ];
    }
}
