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
}
