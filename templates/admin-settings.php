<?php
/**
 * Admin template - Settings
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cfs-admin-wrap">
    <h1><?php esc_html_e('Facet Search Settings', 'custom-facet-search'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('cfs_settings_group'); ?>
        
        <h2><?php esc_html_e('General Settings', 'custom-facet-search'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('AJAX Filtering', 'custom-facet-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cfs_settings[enable_ajax]" <?php checked($settings['enable_ajax'] ?? true); ?>>
                        <?php esc_html_e('Enable AJAX filtering (no page reload)', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('URL Updates', 'custom-facet-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cfs_settings[ajax_url_update]" <?php checked($settings['ajax_url_update'] ?? true); ?>>
                        <?php esc_html_e('Update browser URL when filters change (enables shareable links)', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Scroll Behavior', 'custom-facet-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cfs_settings[scroll_to_results]" <?php checked($settings['scroll_to_results'] ?? true); ?>>
                        <?php esc_html_e('Scroll to results after filtering', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Loading Animation', 'custom-facet-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cfs_settings[loading_animation]" <?php checked($settings['loading_animation'] ?? true); ?>>
                        <?php esc_html_e('Show loading spinner during AJAX requests', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="cfs-results-container"><?php esc_html_e('Results Container', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="text" id="cfs-results-container" name="cfs_settings[results_container]" class="regular-text" value="<?php echo esc_attr($settings['results_container'] ?? '.cfs-results'); ?>">
                    <p class="description"><?php esc_html_e('CSS selector for the results container to update via AJAX.', 'custom-facet-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="cfs-pagination-type"><?php esc_html_e('Pagination Type', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <select id="cfs-pagination-type" name="cfs_settings[pagination_type]">
                        <option value="standard" <?php selected($settings['pagination_type'] ?? 'standard', 'standard'); ?>>
                            <?php esc_html_e('Standard Pagination', 'custom-facet-search'); ?>
                        </option>
                        <option value="load_more" <?php selected($settings['pagination_type'] ?? '', 'load_more'); ?>>
                            <?php esc_html_e('Load More Button', 'custom-facet-search'); ?>
                        </option>
                        <option value="infinite" <?php selected($settings['pagination_type'] ?? '', 'infinite'); ?>>
                            <?php esc_html_e('Infinite Scroll', 'custom-facet-search'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('Advanced Settings', 'custom-facet-search'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Indexing', 'custom-facet-search'); ?></th>
                <td>
                    <button type="button" id="cfs-reindex" class="button">
                        <?php esc_html_e('Rebuild Index', 'custom-facet-search'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Rebuild the facet index if you notice inconsistent counts or missing options.', 'custom-facet-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Debug Mode', 'custom-facet-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="cfs_settings[debug_mode]" <?php checked($settings['debug_mode'] ?? false); ?>>
                        <?php esc_html_e('Enable debug mode (logs queries to browser console)', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php esc_html_e('Usage Guide', 'custom-facet-search'); ?></h2>
    
    <div class="cfs-usage-guide">
        <h3><?php esc_html_e('Basic Setup', 'custom-facet-search'); ?></h3>
        <ol>
            <li><?php esc_html_e('Create facets in the "All Facets" page', 'custom-facet-search'); ?></li>
            <li><?php esc_html_e('Add facets to your page using shortcodes or Elementor widgets', 'custom-facet-search'); ?></li>
            <li><?php esc_html_e('Add the results shortcode to display filtered posts', 'custom-facet-search'); ?></li>
        </ol>
        
        <h3><?php esc_html_e('Example Page Layout', 'custom-facet-search'); ?></h3>
        <pre><code>&lt;!-- Sidebar with facets --&gt;
&lt;div class="filters-sidebar"&gt;
    [cfs_facet slug="category-filter"]
    [cfs_facet slug="price-range"]
    [cfs_facet slug="color"]
    [cfs_reset]
&lt;/div&gt;

&lt;!-- Main content area --&gt;
&lt;div class="results-area"&gt;
    [cfs_active_filters]
    [cfs_results post_type="product" posts_per_page="12" columns="3"]
&lt;/div&gt;</code></pre>
        
        <h3><?php esc_html_e('Custom Templates', 'custom-facet-search'); ?></h3>
        <p><?php esc_html_e('To customize the result item display, create a template file in your theme and reference it:', 'custom-facet-search'); ?></p>
        <pre><code>[cfs_results template="template-parts/product-card"]</code></pre>
        
        <h3><?php esc_html_e('Elementor Integration', 'custom-facet-search'); ?></h3>
        <p><?php esc_html_e('If using Elementor, you\'ll find "Facet Filter" and "Facet Results" widgets in the "Facet Search" category.', 'custom-facet-search'); ?></p>
    </div>
</div>
