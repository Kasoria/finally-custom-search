# Custom Facet Search

A powerful WordPress plugin for faceted/filtered search with support for custom post types, custom fields (meta), and taxonomies.

## Features

- **Multiple Facet Types**: Checkboxes, Radio buttons, Dropdowns, Range sliders, Search box, Date picker, Rating
- **Flexible Data Sources**: Filter by taxonomy, custom field (post meta), or post attributes
- **AJAX Filtering**: Instant results without page reload
- **Grid Targeting**: Link specific facets to specific result grids (like WP Grid Builder)
- **URL Parameters**: Shareable filtered URLs
- **Elementor Integration**: Dedicated widgets for Elementor page builder
- **WordPress Widgets**: Sidebar widget support
- **Fully Customizable**: CSS classes and templates for easy styling

## Installation

1. Upload the `custom-facet-search` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Facet Search** in the admin menu to create facets

## Creating Facets

1. Navigate to **Facet Search → Add New**
2. Enter a name and configure:
   - **Facet Type**: Choose how the filter appears (checkbox, dropdown, range slider, etc.)
   - **Data Source**: Select taxonomy, custom field, or post attribute
   - **Post Types**: Choose which post types this facet filters
3. Save the facet

## Usage

### Elementor (Recommended)

1. Add a **"Facet Results"** widget where you want results to appear
   - Set a **Grid ID** (e.g., `my-products-grid`) or note the auto-generated ID
   
2. Add **"Facet Filter"** widgets for each filter
   - Select your facet
   - In **"Select a grid or widget to filter"**, enter the Grid ID from step 1

This links the facets to that specific results grid, allowing multiple independent filter sets on one page.

### Shortcodes

#### Display a Facet
```
[cfs_facet slug="your-facet-slug" target_grid="my-grid-id"]
```

#### Display Results Grid
```
[cfs_results post_type="post" posts_per_page="12" columns="3" grid_id="my-grid-id"]
```

Parameters:
- `post_type` - Post type to display (default: "post")
- `posts_per_page` - Number of posts per page (default: 12)
- `columns` - Grid columns 1-6 (default: 3)
- `grid_id` - Unique ID for targeting this grid
- `orderby` - Order by field (default: "date")
- `order` - ASC or DESC (default: "DESC")
- `template` - Custom template path

#### Display Active Filters
```
[cfs_active_filters show_clear_all="true"]
```

#### Reset Button
```
[cfs_reset label="Reset Filters"]
```

#### Results Count
```
[cfs_count post_type="post"]
```

### Example Page Layout

```html
<div class="search-page">
    <!-- Sidebar -->
    <aside class="filters">
        [cfs_facet slug="category" target_grid="products"]
        [cfs_facet slug="price-range" target_grid="products"]
        [cfs_facet slug="color" target_grid="products"]
        [cfs_reset]
    </aside>
    
    <!-- Main Content -->
    <main class="results">
        [cfs_active_filters]
        [cfs_results post_type="product" posts_per_page="12" columns="3" grid_id="products"]
    </main>
</div>
```

### Multiple Filter Groups on One Page

You can have separate filter sets for different grids:

```html
<!-- Products Section -->
<div class="products-section">
    [cfs_facet slug="product-category" target_grid="products-grid"]
    [cfs_results post_type="product" grid_id="products-grid"]
</div>

<!-- Blog Section -->
<div class="blog-section">
    [cfs_facet slug="blog-category" target_grid="blog-grid"]
    [cfs_results post_type="post" grid_id="blog-grid"]
</div>
```

## Range Slider Configuration

For range sliders (e.g., price, dimensions):

1. Set **Facet Type** to "Range Slider"
2. Set **Data Source** to "Custom Field"
3. Enter the meta key (e.g., `_price`, `teppich_laenge`)
4. Configure:
   - **Minimum/Maximum Value**: The slider range
   - **Step**: Increment value
   - **Prefix/Suffix**: e.g., "€" or "cm"
   - **Allow Number Input**: Let users type exact values

## Elementor Integration

Find the "Facet Search" widgets category in Elementor:
- **Facet Filter**: Add individual facets
- **Facet Results**: Display filtered results grid

## Custom Templates

Create a template file in your theme and reference it:

```
[cfs_results template="template-parts/product-card"]
```

Your template will receive standard WordPress loop context.

## Hooks & Filters

### Modify Query
```php
add_filter('cfs_query_args', function($args, $filters) {
    // Modify query arguments
    return $args;
}, 10, 2);
```

### Custom Facet Output
```php
add_filter('cfs_facet_html', function($html, $facet, $values) {
    // Modify facet HTML
    return $html;
}, 10, 3);
```

## CSS Customization

Main CSS classes:
- `.cfs-facet` - Facet wrapper
- `.cfs-facet-label` - Facet label
- `.cfs-results` - Results grid
- `.cfs-result-item` - Individual result card
- `.cfs-pagination` - Pagination wrapper
- `.cfs-active-filters` - Active filters display

## Settings

Navigate to **Facet Search → Settings** to configure:
- AJAX filtering on/off
- URL parameter updates
- Scroll behavior
- Loading animations
- Pagination type (standard, load more, infinite scroll)

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Changelog

### 1.0.0
- Initial release
- Core facet types: checkbox, radio, dropdown, range, search, date, rating
- AJAX filtering
- Elementor widgets
- WordPress widget

## Support

For issues and feature requests, contact [WebGrowth Studio](https://webgrowth.studio)

## License

GPL v2 or later
