<?php
/**
 * Admin template - Facets listing
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cfs-admin-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Facets', 'custom-facet-search'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=cfs-add-facet'); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'custom-facet-search'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php if ($edit_facet): ?>
        <!-- Edit Facet Form -->
        <div class="cfs-edit-facet-wrap">
            <h2><?php esc_html_e('Edit Facet', 'custom-facet-search'); ?></h2>
            <?php include CFS_PLUGIN_DIR . 'templates/admin-facet-form.php'; ?>
        </div>
    <?php endif; ?>
    
    <table class="wp-list-table widefat fixed striped cfs-facets-table">
        <thead>
            <tr>
                <th scope="col" class="column-name"><?php esc_html_e('Name', 'custom-facet-search'); ?></th>
                <th scope="col" class="column-type"><?php esc_html_e('Type', 'custom-facet-search'); ?></th>
                <th scope="col" class="column-source"><?php esc_html_e('Source', 'custom-facet-search'); ?></th>
                <th scope="col" class="column-shortcode"><?php esc_html_e('Shortcode', 'custom-facet-search'); ?></th>
                <th scope="col" class="column-actions"><?php esc_html_e('Actions', 'custom-facet-search'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($facets)): ?>
                <tr>
                    <td colspan="5" class="cfs-no-items">
                        <?php esc_html_e('No facets found.', 'custom-facet-search'); ?>
                        <a href="<?php echo admin_url('admin.php?page=cfs-add-facet'); ?>">
                            <?php esc_html_e('Create your first facet', 'custom-facet-search'); ?>
                        </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($facets as $facet): 
                    $settings = json_decode($facet->settings, true);
                    $types = CFS_Admin::get_facet_types();
                    $sources = CFS_Admin::get_sources();
                ?>
                    <tr data-id="<?php echo esc_attr($facet->id); ?>">
                        <td class="column-name">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=custom-facet-search&edit=' . $facet->id); ?>">
                                    <?php echo esc_html($facet->name); ?>
                                </a>
                            </strong>
                            <br>
                            <span class="cfs-facet-slug"><?php echo esc_html($facet->slug); ?></span>
                        </td>
                        <td class="column-type">
                            <?php echo esc_html($types[$facet->type] ?? $facet->type); ?>
                        </td>
                        <td class="column-source">
                            <?php echo esc_html($sources[$facet->source] ?? $facet->source); ?>:
                            <code><?php echo esc_html($facet->source_key); ?></code>
                        </td>
                        <td class="column-shortcode">
                            <code class="cfs-shortcode-display">[cfs_facet slug="<?php echo esc_attr($facet->slug); ?>"]</code>
                            <button type="button" class="button-link cfs-copy-shortcode" title="<?php esc_attr_e('Copy shortcode', 'custom-facet-search'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo admin_url('admin.php?page=custom-facet-search&edit=' . $facet->id); ?>" class="button button-small">
                                <?php esc_html_e('Edit', 'custom-facet-search'); ?>
                            </a>
                            <button type="button" class="button button-small button-link-delete cfs-delete-facet" data-id="<?php echo esc_attr($facet->id); ?>">
                                <?php esc_html_e('Delete', 'custom-facet-search'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="cfs-shortcodes-reference">
        <h3><?php esc_html_e('Available Shortcodes', 'custom-facet-search'); ?></h3>
        <table class="widefat">
            <tr>
                <td><code>[cfs_facet slug="..."]</code></td>
                <td><?php esc_html_e('Display a single facet', 'custom-facet-search'); ?></td>
            </tr>
            <tr>
                <td><code>[cfs_results post_type="..." posts_per_page="12" columns="3"]</code></td>
                <td><?php esc_html_e('Display filtered results grid', 'custom-facet-search'); ?></td>
            </tr>
            <tr>
                <td><code>[cfs_active_filters]</code></td>
                <td><?php esc_html_e('Display currently active filters with remove buttons', 'custom-facet-search'); ?></td>
            </tr>
            <tr>
                <td><code>[cfs_reset label="Reset"]</code></td>
                <td><?php esc_html_e('Display reset all filters button', 'custom-facet-search'); ?></td>
            </tr>
            <tr>
                <td><code>[cfs_count post_type="..."]</code></td>
                <td><?php esc_html_e('Display filtered results count', 'custom-facet-search'); ?></td>
            </tr>
        </table>
    </div>
</div>
