<?php
/**
 * Widgets and Elementor integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class CFS_Widgets {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register WordPress widgets
        add_action('widgets_init', [$this, 'register_widgets']);
        
        // Elementor integration
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_category']);
    }
    
    /**
     * Register WordPress widgets
     */
    public function register_widgets() {
        register_widget('CFS_Facet_Widget');
    }
    
    /**
     * Add Elementor widget category
     */
    public function add_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'custom-facet-search',
            [
                'title' => __('Facet Search', 'custom-facet-search'),
                'icon' => 'fa fa-filter',
            ]
        );
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        // Only load if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        require_once CFS_PLUGIN_DIR . 'includes/elementor/class-cfs-elementor-facet.php';
        require_once CFS_PLUGIN_DIR . 'includes/elementor/class-cfs-elementor-results.php';
        
        $widgets_manager->register(new CFS_Elementor_Facet_Widget());
        $widgets_manager->register(new CFS_Elementor_Results_Widget());
    }
}

/**
 * WordPress Sidebar Widget
 */
class CFS_Facet_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'cfs_facet_widget',
            __('Facet Search', 'custom-facet-search'),
            [
                'description' => __('Display a search facet', 'custom-facet-search'),
            ]
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        if (!empty($instance['facet_slug'])) {
            echo CFS_Facets::instance()->render($instance['facet_slug'], [
                'show_label' => !empty($instance['show_label']),
            ]);
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $facet_slug = !empty($instance['facet_slug']) ? $instance['facet_slug'] : '';
        $show_label = !empty($instance['show_label']);
        
        // Get all facets
        global $wpdb;
        $table_name = $wpdb->prefix . 'cfs_facets';
        $facets = $wpdb->get_results("SELECT slug, name FROM $table_name ORDER BY name ASC");
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'custom-facet-search'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('facet_slug')); ?>">
                <?php esc_html_e('Facet:', 'custom-facet-search'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('facet_slug')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('facet_slug')); ?>">
                <option value=""><?php esc_html_e('Select a facet', 'custom-facet-search'); ?></option>
                <?php foreach ($facets as $facet): ?>
                    <option value="<?php echo esc_attr($facet->slug); ?>" <?php selected($facet_slug, $facet->slug); ?>>
                        <?php echo esc_html($facet->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_label')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_label')); ?>"
                   <?php checked($show_label); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_label')); ?>">
                <?php esc_html_e('Show facet label', 'custom-facet-search'); ?>
            </label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['facet_slug'] = sanitize_text_field($new_instance['facet_slug'] ?? '');
        $instance['show_label'] = !empty($new_instance['show_label']);
        
        return $instance;
    }
}
