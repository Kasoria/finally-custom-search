<?php
/**
 * Admin template - Facet form
 */

if (!defined('ABSPATH')) {
    exit;
}

$types = CFS_Admin::get_facet_types();
$sources = CFS_Admin::get_sources();
$post_types = CFS_Admin::get_post_types();
$taxonomies = CFS_Admin::get_taxonomies();

// Get values (either from edit or defaults)
$id = $edit_facet->id ?? 0;
$name = $edit_facet->name ?? '';
$slug = $edit_facet->slug ?? '';
$type = $edit_facet->type ?? 'checkbox';
$source = $edit_facet->source ?? 'taxonomy';
$source_key = $edit_facet->source_key ?? '';
$settings = $edit_facet->settings ?? [];
?>

<form id="cfs-facet-form" class="cfs-facet-form">
    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="cfs-name"><?php esc_html_e('Name', 'custom-facet-search'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <input type="text" id="cfs-name" name="name" class="regular-text" value="<?php echo esc_attr($name); ?>" required>
                <p class="description"><?php esc_html_e('The internal name for this facet.', 'custom-facet-search'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfs-slug"><?php esc_html_e('Slug', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <input type="text" id="cfs-slug" name="slug" class="regular-text" value="<?php echo esc_attr($slug); ?>">
                <p class="description"><?php esc_html_e('URL-friendly identifier. Leave empty to auto-generate from name.', 'custom-facet-search'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfs-label"><?php esc_html_e('Display Label', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <input type="text" id="cfs-label" name="label" class="regular-text" value="<?php echo esc_attr($settings['label'] ?? ''); ?>">
                <p class="description"><?php esc_html_e('Label shown above the facet on the frontend.', 'custom-facet-search'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfs-type"><?php esc_html_e('Facet Type', 'custom-facet-search'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <select id="cfs-type" name="type" required>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($type, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cfs-source"><?php esc_html_e('Data Source', 'custom-facet-search'); ?> <span class="required">*</span></label>
            </th>
            <td>
                <select id="cfs-source" name="source" required>
                    <?php foreach ($sources as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($source, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        
        <tr class="cfs-source-taxonomy" <?php echo $source !== 'taxonomy' ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="cfs-taxonomy"><?php esc_html_e('Taxonomy', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <select id="cfs-taxonomy" name="source_key_taxonomy">
                    <option value=""><?php esc_html_e('Select taxonomy...', 'custom-facet-search'); ?></option>
                    <?php foreach ($taxonomies as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($source === 'taxonomy' ? $source_key : '', $key); ?>>
                            <?php echo esc_html($label); ?> (<?php echo esc_html($key); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        
        <tr class="cfs-source-custom-field" <?php echo $source !== 'custom_field' ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="cfs-meta-key"><?php esc_html_e('Meta Key', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <input type="text" id="cfs-meta-key" name="source_key_meta" class="regular-text" value="<?php echo esc_attr($source === 'custom_field' ? $source_key : ''); ?>">
                <p class="description"><?php esc_html_e('The custom field key (meta_key) to filter by.', 'custom-facet-search'); ?></p>
                <div id="cfs-meta-key-suggestions" class="cfs-suggestions"></div>
            </td>
        </tr>

        <tr class="cfs-source-custom-field" <?php echo $source !== 'custom_field' ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="cfs-data-type"><?php esc_html_e('Data Type', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <select id="cfs-data-type" name="data_type">
                    <option value="auto" <?php selected($settings['data_type'] ?? 'auto', 'auto'); ?>>
                        <?php esc_html_e('Auto-detect', 'custom-facet-search'); ?>
                    </option>
                    <option value="text" <?php selected($settings['data_type'] ?? '', 'text'); ?>>
                        <?php esc_html_e('Text', 'custom-facet-search'); ?>
                    </option>
                    <option value="numeric" <?php selected($settings['data_type'] ?? '', 'numeric'); ?>>
                        <?php esc_html_e('Number (integer)', 'custom-facet-search'); ?>
                    </option>
                    <option value="decimal" <?php selected($settings['data_type'] ?? '', 'decimal'); ?>>
                        <?php esc_html_e('Number (decimal)', 'custom-facet-search'); ?>
                    </option>
                </select>
                <p class="description"><?php esc_html_e('Helps with proper sorting and filtering of numeric values. Use "Auto-detect" if unsure.', 'custom-facet-search'); ?></p>
            </td>
        </tr>
        
        <tr class="cfs-source-post-attribute" <?php echo $source !== 'post_attribute' ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="cfs-post-attribute"><?php esc_html_e('Post Attribute', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <select id="cfs-post-attribute" name="source_key_attribute">
                    <option value="post_author" <?php selected($source === 'post_attribute' ? $source_key : '', 'post_author'); ?>>
                        <?php esc_html_e('Author', 'custom-facet-search'); ?>
                    </option>
                    <option value="post_date" <?php selected($source === 'post_attribute' ? $source_key : '', 'post_date'); ?>>
                        <?php esc_html_e('Post Date', 'custom-facet-search'); ?>
                    </option>
                    <option value="post_content" <?php selected($source === 'post_attribute' ? $source_key : '', 'post_content'); ?>>
                        <?php esc_html_e('Content Search', 'custom-facet-search'); ?>
                    </option>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Post Types', 'custom-facet-search'); ?></label>
            </th>
            <td>
                <?php foreach ($post_types as $key => $label): 
                    $checked = isset($settings['post_types']) ? in_array($key, $settings['post_types']) : ($key === 'post');
                ?>
                    <label class="cfs-checkbox-inline">
                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($key); ?>" <?php checked($checked); ?>>
                        <?php echo esc_html($label); ?>
                    </label>
                <?php endforeach; ?>
                <p class="description"><?php esc_html_e('Which post types should this facet filter.', 'custom-facet-search'); ?></p>
            </td>
        </tr>
    </table>
    
    <!-- Range-specific settings -->
    <div id="cfs-range-settings" class="cfs-type-settings" <?php echo $type !== 'range' ? 'style="display:none;"' : ''; ?>>
        <h3><?php esc_html_e('Range Slider Settings', 'custom-facet-search'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cfs-min"><?php esc_html_e('Minimum Value', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="number" id="cfs-min" name="min" step="any" value="<?php echo esc_attr($settings['min'] ?? 0); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-max"><?php esc_html_e('Maximum Value', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="number" id="cfs-max" name="max" step="any" value="<?php echo esc_attr($settings['max'] ?? 100); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-step"><?php esc_html_e('Step', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="number" id="cfs-step" name="step" step="any" value="<?php echo esc_attr($settings['step'] ?? 1); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-prefix"><?php esc_html_e('Prefix', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="text" id="cfs-prefix" name="prefix" class="small-text" value="<?php echo esc_attr($settings['prefix'] ?? ''); ?>">
                    <span class="description"><?php esc_html_e('e.g., € or $', 'custom-facet-search'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-suffix"><?php esc_html_e('Suffix', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="text" id="cfs-suffix" name="suffix" class="small-text" value="<?php echo esc_attr($settings['suffix'] ?? ''); ?>">
                    <span class="description"><?php esc_html_e('e.g., cm, kg, m²', 'custom-facet-search'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-inputs-enabled"><?php esc_html_e('Allow Number Input', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="cfs-inputs-enabled" name="inputs_enabled" <?php checked($settings['inputs_enabled'] ?? true); ?>>
                        <?php esc_html_e('Allow users to type exact values in input fields', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Checkbox/Radio/Dropdown settings -->
    <div id="cfs-list-settings" class="cfs-type-settings" <?php echo !in_array($type, ['checkbox', 'radio', 'dropdown']) ? 'style="display:none;"' : ''; ?>>
        <h3><?php esc_html_e('List Settings', 'custom-facet-search'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Options', 'custom-facet-search'); ?></th>
                <td>
                    <label class="cfs-checkbox-block">
                        <input type="checkbox" name="show_count" <?php checked($settings['show_count'] ?? false); ?>>
                        <?php esc_html_e('Show post count', 'custom-facet-search'); ?>
                    </label>
                    <label class="cfs-checkbox-block">
                        <input type="checkbox" name="hide_empty" <?php checked($settings['hide_empty'] ?? true); ?>>
                        <?php esc_html_e('Hide empty options', 'custom-facet-search'); ?>
                    </label>
                    <label class="cfs-checkbox-block cfs-dropdown-only" <?php echo $type !== 'dropdown' ? 'style="display:none;"' : ''; ?>>
                        <input type="checkbox" name="multiple" <?php checked($settings['multiple'] ?? false); ?>>
                        <?php esc_html_e('Allow multiple selections (dropdown only)', 'custom-facet-search'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-placeholder"><?php esc_html_e('Placeholder', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <input type="text" id="cfs-placeholder" name="placeholder" class="regular-text" value="<?php echo esc_attr($settings['placeholder'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cfs-orderby"><?php esc_html_e('Order By', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <select id="cfs-orderby" name="orderby">
                        <option value="name" <?php selected($settings['orderby'] ?? 'name', 'name'); ?>>
                            <?php esc_html_e('Name', 'custom-facet-search'); ?>
                        </option>
                        <option value="count" <?php selected($settings['orderby'] ?? '', 'count'); ?>>
                            <?php esc_html_e('Count', 'custom-facet-search'); ?>
                        </option>
                        <option value="term_id" <?php selected($settings['orderby'] ?? '', 'term_id'); ?>>
                            <?php esc_html_e('Term ID', 'custom-facet-search'); ?>
                        </option>
                    </select>
                    <select id="cfs-order" name="order">
                        <option value="ASC" <?php selected($settings['order'] ?? 'ASC', 'ASC'); ?>>
                            <?php esc_html_e('Ascending', 'custom-facet-search'); ?>
                        </option>
                        <option value="DESC" <?php selected($settings['order'] ?? '', 'DESC'); ?>>
                            <?php esc_html_e('Descending', 'custom-facet-search'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Date settings -->
    <div id="cfs-date-settings" class="cfs-type-settings" <?php echo $type !== 'date' ? 'style="display:none;"' : ''; ?>>
        <h3><?php esc_html_e('Date Settings', 'custom-facet-search'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cfs-date-type"><?php esc_html_e('Date Type', 'custom-facet-search'); ?></label>
                </th>
                <td>
                    <select id="cfs-date-type" name="date_type">
                        <option value="single" <?php selected($settings['date_type'] ?? 'single', 'single'); ?>>
                            <?php esc_html_e('Single Date', 'custom-facet-search'); ?>
                        </option>
                        <option value="range" <?php selected($settings['date_type'] ?? '', 'range'); ?>>
                            <?php esc_html_e('Date Range', 'custom-facet-search'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <p class="submit">
        <button type="submit" class="button button-primary">
            <?php echo $id ? esc_html__('Update Facet', 'custom-facet-search') : esc_html__('Create Facet', 'custom-facet-search'); ?>
        </button>
        <?php if ($id): ?>
            <a href="<?php echo admin_url('admin.php?page=custom-facet-search'); ?>" class="button">
                <?php esc_html_e('Cancel', 'custom-facet-search'); ?>
            </a>
        <?php endif; ?>
    </p>
    
    <div id="cfs-save-result"></div>
</form>
