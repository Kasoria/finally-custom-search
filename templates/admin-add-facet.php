<?php
/**
 * Admin template - Add new facet
 */

if (!defined('ABSPATH')) {
    exit;
}

$edit_facet = null;
?>

<div class="wrap cfs-admin-wrap">
    <h1><?php esc_html_e('Add New Facet', 'custom-facet-search'); ?></h1>
    
    <?php include CFS_PLUGIN_DIR . 'templates/admin-facet-form.php'; ?>
</div>
