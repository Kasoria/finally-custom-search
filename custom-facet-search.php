<?php
/**
 * Plugin Name: Custom Facet Search
 * Plugin URI: https://webgrowth.studio
 * Description: Powerful faceted search for WordPress with support for custom post types and custom fields. Create range sliders, checkboxes, dropdowns, and more.
 * Version: 1.0.0
 * Author: Christian Wenterodt
 * Author URI: https://chrispump.me
 * License: GPL v2 or later
 * Text Domain: custom-facet-search
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CFS_VERSION', '1.0.0');
define('CFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class Custom_Facet_Search {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-admin.php';
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-facets.php';
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-query.php';
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-ajax.php';
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-shortcodes.php';
        require_once CFS_PLUGIN_DIR . 'includes/class-cfs-widgets.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Initialize components
        add_action('init', [$this, 'init_components']);
    }
    
    public function init_components() {
        CFS_Admin::instance();
        CFS_Facets::instance();
        CFS_Query::instance();
        CFS_Ajax::instance();
        CFS_Shortcodes::instance();
        CFS_Widgets::instance();

        // Hook up debug mode setting
        add_filter('cfs_debug_mode', [$this, 'is_debug_mode']);
    }

    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        $settings = get_option('cfs_settings', []);
        return !empty($settings['debug_mode']);
    }
    
    public function activate() {
        // Create default options
        $default_options = [
            'enable_ajax' => true,
            'ajax_url_update' => true,
            'scroll_to_results' => true,
            'loading_animation' => true,
            'results_container' => '.cfs-results',
            'pagination_type' => 'standard', // standard, load_more, infinite
        ];
        
        if (!get_option('cfs_settings')) {
            add_option('cfs_settings', $default_options);
        }
        
        // Create facets table
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'cfs_facets';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            source varchar(50) NOT NULL,
            source_key varchar(255) NOT NULL,
            settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('custom-facet-search', false, dirname(CFS_PLUGIN_BASENAME) . '/languages');
    }
    
    public function enqueue_frontend_assets() {
        // noUiSlider for range sliders
        wp_enqueue_style(
            'nouislider',
            'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css',
            [],
            '15.7.1'
        );
        
        wp_enqueue_script(
            'nouislider',
            'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js',
            [],
            '15.7.1',
            true
        );
        
        // Plugin styles and scripts
        wp_enqueue_style(
            'cfs-frontend',
            CFS_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            CFS_VERSION
        );
        
        wp_enqueue_script(
            'cfs-frontend',
            CFS_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'nouislider'],
            CFS_VERSION,
            true
        );
        
        $settings = get_option('cfs_settings', []);
        wp_localize_script('cfs-frontend', 'cfsConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfs_nonce'),
            'settings' => $settings,
            'debug' => !empty($settings['debug_mode']),
            'i18n' => [
                'loading' => __('Loading...', 'custom-facet-search'),
                'loadMore' => __('Load More', 'custom-facet-search'),
                'noResults' => __('No results found.', 'custom-facet-search'),
                'reset' => __('Reset', 'custom-facet-search'),
                'result' => __('result', 'custom-facet-search'),
                'results' => __('results', 'custom-facet-search'),
                'activeFilters' => __('Active filters:', 'custom-facet-search'),
                'clearAll' => __('Clear all', 'custom-facet-search'),
            ]
        ]);
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cfs-') === false && $hook !== 'toplevel_page_custom-facet-search') {
            return;
        }
        
        wp_enqueue_style(
            'cfs-admin',
            CFS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CFS_VERSION
        );
        
        wp_enqueue_script(
            'cfs-admin',
            CFS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            CFS_VERSION,
            true
        );
        
        wp_localize_script('cfs-admin', 'cfsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cfs_admin_nonce'),
        ]);
    }
}

// Initialize plugin
function CFS() {
    return Custom_Facet_Search::instance();
}

// Start the plugin
CFS();
