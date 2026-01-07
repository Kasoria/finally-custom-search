/**
 * Custom Facet Search - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Main CFS object
    window.CFS = {
        config: cfsConfig || {},
        isLoading: false,
        debounceTimer: null,

        /**
         * Debug logging helper
         */
        log: function(...args) {
            if (this.config.debug) {
                console.log('[CFS Debug]', ...args);
            }
        },

        init: function() {
            this.log('Initializing Custom Facet Search');
            this.bindEvents();
            this.initRangeSliders();
            this.initFromURL();
            this.detectGrids();
            this.log('Initialization complete', {
                grids: Object.keys(this.grids),
                settings: this.config.settings
            });
        },
        
        /**
         * Detect all result grids on the page and store references
         */
        detectGrids: function() {
            this.grids = {};
            const self = this;

            // Find all CFS result wrappers (native shortcode/widget)
            $('.cfs-results-wrapper').each(function(i) {
                const $grid = $(this);
                const gridId = $grid.data('grid-id') || $grid.attr('id') || 'cfs-grid-' + i;
                self.grids[gridId] = $grid;
                self.log('Found CFS results wrapper:', gridId);
            });

            // Detect Elementor Loop Grids and other common grid widgets
            $('[data-id]').each(function() {
                const $el = $(this);
                const elementorId = $el.data('id');
                if (elementorId && !self.grids[elementorId]) {
                    const hasLoopContainer = $el.find('.elementor-loop-container, .elementor-posts-container, .elementor-grid, .e-loop-item').length;
                    const isLoopWidget = $el.hasClass('elementor-widget-loop-grid') || $el.hasClass('elementor-widget-posts');

                    if (hasLoopContainer || isLoopWidget) {
                        self.grids[elementorId] = $el;
                        self.log('Found Elementor grid:', elementorId);
                    }
                }
            });

            // Detect Bricks Builder elements - multiple detection methods

            // Method 1: Find Bricks loops by HTML comment markers
            // Bricks uses <!--brx-loop-start-XXXXX--> comments
            $('*').contents().filter(function() {
                return this.nodeType === 8; // Comment nodes
            }).each(function() {
                const comment = this.nodeValue || '';
                const match = comment.match(/brx-loop-start-([a-z0-9]+)/i);
                if (match) {
                    const loopId = match[1];
                    const $container = $(this).parent();
                    if (!self.grids[loopId]) {
                        self.grids[loopId] = $container;
                        self.log('Found Bricks loop via comment:', loopId);
                    }
                }
            });

            // Method 2: Find containers with repeating brxe-* children (loop items)
            $('.brxe-container, .brxe-div, .brxe-block').each(function(i) {
                const $el = $(this);
                const $children = $el.children('[class*="brxe-"]');

                // Check if children have repeating class patterns (indicates a loop)
                if ($children.length > 1) {
                    const firstChildClasses = $children.first().attr('class') || '';
                    const classMatch = firstChildClasses.match(/brxe-([a-z0-9]+)/i);

                    if (classMatch) {
                        const itemClass = 'brxe-' + classMatch[1];
                        const sameClassChildren = $children.filter('.' + itemClass);

                        // If multiple children have the same brxe-* class, it's likely a loop
                        if (sameClassChildren.length > 1) {
                            const loopId = classMatch[1];
                            if (!self.grids[loopId]) {
                                self.grids[loopId] = $el;
                                self.log('Found Bricks loop via repeating children:', loopId, 'items:', sameClassChildren.length);
                            }
                        }
                    }
                }
            });

            // Method 3: Query loops with data attribute
            $('[data-query-loop-id], [data-query-element-id]').each(function(i) {
                const $el = $(this);
                const loopId = $el.attr('data-query-loop-id') || $el.attr('data-query-element-id');
                if (loopId && !self.grids[loopId]) {
                    self.grids[loopId] = $el;
                    self.log('Found Bricks query loop via data attr:', loopId);
                }
            });

            // Method 4: Bricks sections/containers that might be grids
            $('.brxe-section, .brxe-container').each(function(i) {
                const $el = $(this);
                const id = $el.attr('id');
                // Only add if it has an explicit ID (user-defined)
                if (id && !self.grids[id]) {
                    self.grids[id] = $el;
                    self.log('Found Bricks section/container with ID:', id);
                }
            });

            // Also detect by common CSS selectors used by page builders
            const commonSelectors = [
                '.cfs-bricks-results-wrapper', // CFS Bricks Results
                '.jet-listing-grid',           // JetEngine
                '.wpgb-grid-wrapper',          // WP Grid Builder
                '[data-loop-grid]',            // Generic loop grid
                '.elementor-posts',            // Elementor Posts widget
                '.elementor-loop-container'    // Elementor Loop
            ];

            commonSelectors.forEach(function(selector) {
                $(selector).each(function(i) {
                    const $el = $(this);
                    const id = $el.attr('id') || $el.data('grid-id') || $el.data('id') || selector.replace(/[^a-z]/gi, '') + '-' + i;
                    if (!self.grids[id]) {
                        self.grids[id] = $el;
                        self.log('Found grid via selector:', selector, id);
                    }
                });
            });

            this.log('Total detected grids:', Object.keys(this.grids).length, Object.keys(this.grids));
        },
        
        /**
         * Get the target grid for a facet
         */
        getTargetGrid: function($facet) {
            const targetGrid = $facet ? $facet.data('target-grid') : null;

            if (targetGrid) {
                // Try to find by grid ID first
                if (this.grids[targetGrid]) {
                    return this.grids[targetGrid];
                }

                // Try as Elementor widget ID (with or without prefix)
                const elementorEl = $('[data-id="' + targetGrid + '"]');
                if (elementorEl.length) {
                    return elementorEl;
                }

                // Try with elementor-element prefix
                const withPrefix = $('#elementor-element-' + targetGrid + ', .elementor-element-' + targetGrid);
                if (withPrefix.length) {
                    return withPrefix;
                }

                // Try as Bricks element ID
                const bricksEl = $('[data-bricks-id="' + targetGrid + '"], #brxe-' + targetGrid + ', .brxe-' + targetGrid);
                if (bricksEl.length) {
                    return bricksEl;
                }

                // Try as Bricks query loop ID
                const bricksLoop = $('[data-query-loop-id="' + targetGrid + '"]');
                if (bricksLoop.length) {
                    return bricksLoop;
                }

                // Try as CSS selector (ID or class)
                try {
                    const cssSelector = $(targetGrid.startsWith('.') || targetGrid.startsWith('#') ? targetGrid : '#' + targetGrid);
                    if (cssSelector.length) {
                        return cssSelector;
                    }
                } catch (e) {
                    // Invalid selector, continue
                }
            }

            // Try to find CFS results wrapper first
            const $cfsWrapper = $('.cfs-results-wrapper, .cfs-bricks-results-wrapper').first();
            if ($cfsWrapper.length) {
                return $cfsWrapper;
            }

            // Fallback: use first detected grid (Bricks loop, Elementor loop, etc.)
            const gridKeys = Object.keys(this.grids);
            if (gridKeys.length > 0) {
                this.log('Using auto-detected grid as target:', gridKeys[0]);
                return this.grids[gridKeys[0]];
            }

            // No grid found
            return $();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Checkbox and radio changes
            $(document).on('change', '.cfs-facet input[type="checkbox"], .cfs-facet input[type="radio"]', function() {
                self.triggerFilter($(this).closest('.cfs-facet'));
            });
            
            // Dropdown changes
            $(document).on('change', '.cfs-facet select', function() {
                self.triggerFilter($(this).closest('.cfs-facet'));
            });
            
            // Search input with debounce
            $(document).on('input', '.cfs-search-input', function() {
                const $input = $(this);
                const $facet = $input.closest('.cfs-facet');
                const $clear = $input.siblings('.cfs-search-clear');
                
                $clear.toggle($input.val().length > 0);
                
                clearTimeout(self.debounceTimer);
                self.debounceTimer = setTimeout(function() {
                    self.triggerFilter($facet);
                }, 500);
            });
            
            // Search clear button
            $(document).on('click', '.cfs-search-clear', function() {
                const $facet = $(this).closest('.cfs-facet');
                $(this).siblings('.cfs-search-input').val('').focus();
                $(this).hide();
                self.triggerFilter($facet);
            });
            
            // Date inputs
            $(document).on('change', '.cfs-date-input', function() {
                self.triggerFilter($(this).closest('.cfs-facet'));
            });
            
            // Rating selection
            $(document).on('change', '.cfs-rating-wrapper input', function() {
                self.triggerFilter($(this).closest('.cfs-facet'));
            });
            
            // Pagination clicks
            $(document).on('click', '.cfs-pagination .cfs-page-btn', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const $grid = $(this).closest('.cfs-results-wrapper');
                self.goToPage(page, $grid);
            });
            
            // Load more button
            $(document).on('click', '.cfs-load-more', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const $grid = $(this).closest('.cfs-results-wrapper');
                self.loadMore(page, $grid);
            });
            
            // Sort dropdown
            $(document).on('change', '.cfs-sort-select', function() {
                self.triggerFilter();
            });
            
            // Remove active filter
            $(document).on('click', '.cfs-remove-filter', function(e) {
                e.preventDefault();
                const slug = $(this).data('slug');
                self.removeFilter(slug);
            });
            
            // Reset all filters
            $(document).on('click', '.cfs-reset-btn, .cfs-clear-all, .cfs-reset-all', function(e) {
                e.preventDefault();
                self.resetAllFilters();
            });
            
            // Range reset button
            $(document).on('click', '.cfs-range-reset', function() {
                const $wrapper = $(this).closest('.cfs-range-wrapper');
                const $facet = $(this).closest('.cfs-facet');
                self.resetRangeSlider($wrapper, $facet);
            });
        },
        
        initRangeSliders: function() {
            const self = this;
            
            $('.cfs-range-wrapper').each(function() {
                const $wrapper = $(this);
                const $slider = $wrapper.find('.cfs-range-slider');
                const $minInput = $wrapper.find('.cfs-range-min');
                const $maxInput = $wrapper.find('.cfs-range-max');
                const $resetBtn = $wrapper.find('.cfs-range-reset');
                
                const min = parseFloat($wrapper.data('min'));
                const max = parseFloat($wrapper.data('max'));
                const step = parseFloat($wrapper.data('step')) || 1;
                const currentMin = parseFloat($wrapper.data('current-min')) || min;
                const currentMax = parseFloat($wrapper.data('current-max')) || max;
                
                // Check if noUiSlider is available
                if (typeof noUiSlider === 'undefined') {
                    console.warn('CFS: noUiSlider not loaded');
                    return;
                }
                
                // Create slider
                noUiSlider.create($slider[0], {
                    start: [currentMin, currentMax],
                    connect: true,
                    step: step,
                    range: {
                        'min': min,
                        'max': max
                    },
                    format: {
                        to: function(value) {
                            return step >= 1 ? Math.round(value) : value.toFixed(2);
                        },
                        from: function(value) {
                            return parseFloat(value);
                        }
                    }
                });
                
                // Slider update event
                $slider[0].noUiSlider.on('update', function(values) {
                    $minInput.val(values[0]);
                    $maxInput.val(values[1]);
                    
                    // Update reset button state
                    const isDefault = parseFloat(values[0]) === min && parseFloat(values[1]) === max;
                    $resetBtn.prop('disabled', isDefault);
                });
                
                // Slider change event (fires on release)
                $slider[0].noUiSlider.on('change', function() {
                    const $facet = $wrapper.closest('.cfs-facet');
                    self.triggerFilter($facet);
                });
                
                // Input change events
                $minInput.on('change', function() {
                    let val = parseFloat($(this).val());
                    val = Math.max(min, Math.min(val, parseFloat($maxInput.val())));
                    $slider[0].noUiSlider.set([val, null]);
                    const $facet = $wrapper.closest('.cfs-facet');
                    self.triggerFilter($facet);
                });
                
                $maxInput.on('change', function() {
                    let val = parseFloat($(this).val());
                    val = Math.min(max, Math.max(val, parseFloat($minInput.val())));
                    $slider[0].noUiSlider.set([null, val]);
                    const $facet = $wrapper.closest('.cfs-facet');
                    self.triggerFilter($facet);
                });
                
                // Enter key on inputs
                $minInput.add($maxInput).on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $(this).trigger('change');
                    }
                });
            });
        },
        
        resetRangeSlider: function($wrapper, $facet) {
            const $slider = $wrapper.find('.cfs-range-slider');
            const min = parseFloat($wrapper.data('min'));
            const max = parseFloat($wrapper.data('max'));
            
            if ($slider[0] && $slider[0].noUiSlider) {
                $slider[0].noUiSlider.set([min, max]);
                this.triggerFilter($facet || $wrapper.closest('.cfs-facet'));
            }
        },
        
        triggerFilter: function($facet) {
            const self = this;
            const settings = this.config.settings || {};

            // Get target grid from the triggering facet
            const $targetGrid = $facet ? this.getTargetGrid($facet) : $('.cfs-results-wrapper').first();

            this.log('Trigger filter, target grid:', $targetGrid.length ? 'found' : 'not found', $targetGrid);

            // Check for CFS Results elements
            const isCFSResultsElement = $targetGrid.length && (
                $targetGrid.hasClass('cfs-results-wrapper') ||
                $targetGrid.hasClass('cfs-bricks-results-wrapper')
            );

            this.log('AJAX check:', {
                enabled: settings.enable_ajax,
                isCFSResults: isCFSResultsElement,
                targetFound: $targetGrid.length > 0
            });

            // Use AJAX if enabled - we'll determine post_type in doAjaxFilter
            if (settings.enable_ajax) {
                this.doAjaxFilter(null, $facet, $targetGrid);
            } else {
                // URL-based filtering only when AJAX is disabled
                this.log('Using URL-based filtering (AJAX disabled in settings)');
                this.doUrlFilter($facet);
            }
        },

        /**
         * Perform URL-based filtering (page reload with filter params)
         */
        doUrlFilter: function($facet) {
            const filters = this.gatherFilters();

            // Use the URL API for robust URL manipulation
            const url = new URL(window.location.href);

            // Remove existing cfs_ parameters
            const keysToDelete = [];
            url.searchParams.forEach((value, key) => {
                if (key.startsWith('cfs_')) {
                    keysToDelete.push(key);
                }
            });
            keysToDelete.forEach(key => url.searchParams.delete(key));

            // Add current filter values
            Object.keys(filters).forEach(key => {
                const value = filters[key];
                if (Array.isArray(value)) {
                    value.forEach(v => url.searchParams.append(key, v));
                } else if (value !== '' && value !== null && value !== undefined) {
                    url.searchParams.set(key, value);
                }
            });

            this.log('Redirecting to:', url.toString());
            window.location.href = url.toString();
        },

        /**
         * Build query string from current filters
         */
        buildFilterQueryString: function($facet) {
            const filters = this.gatherFilters();
            const params = new URLSearchParams(window.location.search);

            // Remove existing cfs_ parameters
            const keysToDelete = [];
            params.forEach((value, key) => {
                if (key.startsWith('cfs_')) {
                    keysToDelete.push(key);
                }
            });
            keysToDelete.forEach(key => params.delete(key));

            // Add current filter values
            Object.keys(filters).forEach(key => {
                const value = filters[key];
                if (Array.isArray(value)) {
                    value.forEach(v => params.append(key, v));
                } else if (value !== '' && value !== null && value !== undefined) {
                    params.set(key, value);
                }
            });

            return params.toString();
        },

        doAjaxFilter: function(page, $facet, $targetGrid) {
            const self = this;

            // Use provided grid or find from facet or default
            let $resultsWrapper = $targetGrid || ($facet ? this.getTargetGrid($facet) : null) || $('.cfs-results-wrapper').first();

            // If target doesn't have CFS attributes, try to find a CFS wrapper inside it
            if ($resultsWrapper.length && !$resultsWrapper.hasClass('cfs-results-wrapper')) {
                const $innerWrapper = $resultsWrapper.find('.cfs-results-wrapper');
                if ($innerWrapper.length) {
                    $resultsWrapper = $innerWrapper;
                }
            }

            if (this.isLoading || !$resultsWrapper.length) {
                this.log('Cannot perform AJAX filter - no valid results wrapper found');
                return;
            }

            // Get query settings from target grid OR from the triggering facet
            let postType = $resultsWrapper.data('post-type');
            let postsPerPage = $resultsWrapper.data('posts-per-page');
            let template = $resultsWrapper.data('template') || '';

            // If target doesn't have settings, try to get from facet
            if (!postType && $facet) {
                postType = $facet.data('post-type');
                postsPerPage = $facet.data('posts-per-page') || postsPerPage;
            }

            // Final fallback
            postType = postType || 'post';
            postsPerPage = postsPerPage || 12;

            this.log('AJAX filter using postType:', postType, 'postsPerPage:', postsPerPage);

            this.isLoading = true;

            // Show loading state
            $resultsWrapper.addClass('cfs-loading');

            // Gather filter data from facets targeting this grid
            const gridId = $resultsWrapper.data('grid-id') || $resultsWrapper.attr('id') || $resultsWrapper.data('id');
            const filters = this.gatherFilters(gridId);

            // Get sort order
            const sortVal = $('.cfs-sort-select').val() || 'date-DESC';
            const [orderby, order] = sortVal.split('-');

            // Build filter string
            const filterString = $.param(filters);

            this.log('Sending filter request', {
                postType: postType,
                filters: filters,
                filterString: filterString,
                gridId: gridId
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfs_filter',
                    nonce: this.config.nonce,
                    post_type: postType,
                    posts_per_page: postsPerPage,
                    paged: page || 1,
                    template: template,
                    orderby: orderby,
                    order: order,
                    filters: filterString
                },
                success: function(response) {
                    self.log('Filter response received', {
                        success: response.success,
                        foundPosts: response.data?.found_posts,
                        maxPages: response.data?.max_pages,
                        activeFilters: response.data?.active_filters
                    });

                    if (response.success) {
                        // Find the correct results container
                        let $resultsContainer = self.findResultsContainer($resultsWrapper);

                        if ($resultsContainer && $resultsContainer.length) {
                            // Update results
                            $resultsContainer.html(response.data.html);
                        }

                        // Update pagination
                        const $pagination = $resultsWrapper.find('.cfs-pagination');
                        if ($pagination.length) {
                            $pagination.replaceWith(response.data.pagination);
                        } else if (response.data.pagination) {
                            // Append pagination if it doesn't exist yet
                            $resultsWrapper.append(response.data.pagination);
                        }

                        // Update count
                        const $countEl = $resultsWrapper.find('.cfs-results-count');
                        if ($countEl.length) {
                            $countEl.html(
                                response.data.found_posts + ' ' +
                                (response.data.found_posts === 1 ? self.config.i18n.result || 'result' : self.config.i18n.results || 'results')
                            );
                        }

                        // Update active filters display
                        self.updateActiveFilters(response.data.active_filters);

                        // Update URL
                        if (self.config.settings.ajax_url_update) {
                            self.updateURL();
                        }

                        // Scroll to results
                        if (self.config.settings.scroll_to_results && page) {
                            self.scrollToResults($resultsWrapper);
                        }

                        // Trigger custom event for third-party integrations
                        $(document).trigger('cfs:filtered', [response.data, $resultsWrapper]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('CFS Filter Error:', error);
                },
                complete: function() {
                    self.isLoading = false;
                    $resultsWrapper.removeClass('cfs-loading');
                }
            });
        },
        
        gatherFilters: function(targetGridId) {
            const filters = {};
            const self = this;
            
            // Get all facets that target this grid (or all facets if no grid specified)
            let $facets = $('.cfs-facet');
            
            if (targetGridId) {
                $facets = $facets.filter(function() {
                    const facetTarget = $(this).data('target-grid');
                    // Include facet if:
                    // 1. It specifically targets this grid
                    // 2. It has no target specified (applies to all grids)
                    return !facetTarget || facetTarget === targetGridId;
                });
            }
            
            // Checkboxes
            $facets.find('input[type="checkbox"]:checked').each(function() {
                const name = $(this).attr('name');
                if (!filters[name]) {
                    filters[name] = [];
                }
                filters[name].push($(this).val());
            });
            
            // Radio buttons
            $facets.find('input[type="radio"]:checked').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if (val) {
                    filters[name] = val;
                }
            });
            
            // Dropdowns
            $facets.find('select').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if (val && val.length) {
                    filters[name] = val;
                }
            });
            
            // Search inputs
            $facets.find('.cfs-search-input').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val().trim();
                if (val) {
                    filters[name] = val;
                }
            });
            
            // Range sliders
            $facets.find('.cfs-range-wrapper').each(function() {
                const $wrapper = $(this);
                const $minInput = $wrapper.find('.cfs-range-min');
                const $maxInput = $wrapper.find('.cfs-range-max');
                
                const minName = $minInput.attr('name');
                const maxName = $maxInput.attr('name');
                
                const min = parseFloat($wrapper.data('min'));
                const max = parseFloat($wrapper.data('max'));
                const currentMin = parseFloat($minInput.val());
                const currentMax = parseFloat($maxInput.val());
                
                // Only add if not at default values
                if (currentMin !== min || currentMax !== max) {
                    filters[minName] = currentMin;
                    filters[maxName] = currentMax;
                }
            });
            
            // Date inputs
            $facets.find('.cfs-date-input').each(function() {
                const name = $(this).attr('name');
                const val = $(this).val();
                if (val) {
                    filters[name] = val;
                }
            });
            
            return filters;
        },
        
        updateURL: function($facet) {
            const targetGridId = $facet ? ($facet.data('target-grid') || null) : null;
            const filters = this.gatherFilters(targetGridId);
            const url = new URL(window.location.href);
            
            // Remove existing cfs_ parameters
            const keysToRemove = [];
            url.searchParams.forEach((value, key) => {
                if (key.startsWith('cfs_')) {
                    keysToRemove.push(key);
                }
            });
            keysToRemove.forEach(key => url.searchParams.delete(key));
            
            // Add current filters
            Object.keys(filters).forEach(key => {
                const val = filters[key];
                if (Array.isArray(val)) {
                    val.forEach(v => url.searchParams.append(key, v));
                } else {
                    url.searchParams.set(key, val);
                }
            });
            
            // Update browser URL without reload
            window.history.replaceState({}, '', url.toString());
        },
        
        initFromURL: function() {
            // Range sliders are already initialized with current values from PHP
            // This function can be extended for additional URL parameter handling
        },
        
        goToPage: function(page, $grid) {
            this.doAjaxFilter(page, null, $grid);
        },
        
        loadMore: function(page, $grid) {
            const self = this;
            const $resultsWrapper = $grid || $('.cfs-results-wrapper').first();
            const $loadMoreBtn = $resultsWrapper.find('.cfs-load-more');
            
            if (this.isLoading) {
                return;
            }
            
            this.isLoading = true;
            $loadMoreBtn.prop('disabled', true).text(this.config.i18n.loading);
            
            const gridId = $resultsWrapper.data('grid-id') || $resultsWrapper.attr('id');
            const filters = this.gatherFilters(gridId);
            const postType = $resultsWrapper.data('post-type') || 'post';
            const postsPerPage = $resultsWrapper.data('posts-per-page') || 12;
            const template = $resultsWrapper.data('template') || '';
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfs_load_more',
                    nonce: this.config.nonce,
                    post_type: postType,
                    posts_per_page: postsPerPage,
                    paged: page,
                    template: template,
                    filters: $.param(filters)
                },
                success: function(response) {
                    if (response.success) {
                        // Append new items
                        $resultsWrapper.find('.cfs-results').append(response.data.html);
                        
                        if (response.data.has_more) {
                            $loadMoreBtn.data('page', page + 1).prop('disabled', false).text(self.config.i18n.loadMore || 'Load More');
                        } else {
                            $loadMoreBtn.remove();
                        }
                    }
                },
                complete: function() {
                    self.isLoading = false;
                }
            });
        },
        
        removeFilter: function(slug) {
            // Reset the facet with this slug
            const $facet = $('[data-facet="' + slug + '"]');
            
            // Uncheck checkboxes and radios
            $facet.find('input[type="checkbox"]').prop('checked', false);
            $facet.find('input[type="radio"][value=""]').prop('checked', true);
            
            // Reset dropdowns
            $facet.find('select').val('');
            
            // Reset search inputs
            $facet.find('.cfs-search-input').val('');
            
            // Reset range sliders
            $facet.find('.cfs-range-wrapper').each((i, el) => {
                this.resetRangeSlider($(el));
            });
            
            // Reset date inputs
            $facet.find('.cfs-date-input').val('');
            
            this.triggerFilter();
        },
        
        resetAllFilters: function() {
            // Reset all facets
            $('.cfs-facet').each((i, facet) => {
                const $facet = $(facet);
                
                $facet.find('input[type="checkbox"]').prop('checked', false);
                $facet.find('input[type="radio"][value=""]').prop('checked', true);
                $facet.find('select').val('');
                $facet.find('.cfs-search-input').val('');
                $facet.find('.cfs-date-input').val('');
                
                $facet.find('.cfs-range-wrapper').each((j, el) => {
                    this.resetRangeSlider($(el));
                });
            });
            
            this.triggerFilter();
        },
        
        updateActiveFilters: function(filters) {
            const $container = $('.cfs-active-filters');
            
            if (!$container.length) {
                return;
            }
            
            if (!filters || !filters.length) {
                $container.hide();
                return;
            }
            
            let html = '<span class="cfs-active-filters-label">' + (this.config.i18n.activeFilters || 'Active filters:') + '</span>';
            
            filters.forEach(filter => {
                html += `
                    <span class="cfs-active-filter">
                        <span class="cfs-filter-label">${filter.label}:</span>
                        <span class="cfs-filter-value">${filter.value}</span>
                        <a href="#" class="cfs-remove-filter" data-slug="${filter.slug}">
                            <span class="dashicons dashicons-no-alt"></span>
                        </a>
                    </span>
                `;
            });
            
            html += '<a href="#" class="cfs-clear-all">' + (this.config.i18n.clearAll || 'Clear all') + '</a>';
            
            $container.html(html).show();
        },
        
        scrollToResults: function($wrapper) {
            const $results = $wrapper || $('.cfs-results-wrapper').first();
            if ($results.length) {
                $('html, body').animate({
                    scrollTop: $results.offset().top - 100
                }, 300);
            }
        },

        /**
         * Find the actual results container within a wrapper
         * Handles different page builder container classes
         */
        findResultsContainer: function($wrapper) {
            if (!$wrapper || !$wrapper.length) {
                return null;
            }

            // List of possible container selectors in order of priority
            const containerSelectors = [
                '.cfs-results',                  // Native CFS container
                '.elementor-loop-container',     // Elementor Loop Grid
                '.elementor-posts-container',    // Elementor Posts
                '.elementor-grid',               // Elementor generic grid
                '.brxe-loop',                    // Bricks Loop element
                '.brxe-posts',                   // Bricks Posts element
                '[data-query-loop-id]',          // Bricks query loop
                '.jet-listing-grid__items',      // JetEngine
                '.wpgb-grid',                    // WP Grid Builder
                '.e-loop-items',                 // Elementor loop items
                '[data-elementor-type="loop-item"]' // Elementor loop wrapper
            ];

            for (let i = 0; i < containerSelectors.length; i++) {
                const $container = $wrapper.find(containerSelectors[i]).first();
                if ($container.length) {
                    return $container;
                }
            }

            // If wrapper itself is a container, use it directly
            if ($wrapper.hasClass('cfs-results') ||
                $wrapper.hasClass('elementor-loop-container') ||
                $wrapper.hasClass('elementor-posts-container')) {
                return $wrapper;
            }

            // For Bricks loops detected via HTML comments, the wrapper IS the container
            // Check if wrapper has children with brxe-* classes (loop items)
            const $bricksItems = $wrapper.children('[class*="brxe-"]');
            if ($bricksItems.length > 0) {
                this.log('Using Bricks loop wrapper as container, items:', $bricksItems.length);
                return $wrapper;
            }

            // Fallback: use the wrapper itself
            return $wrapper;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        CFS.init();
    });
    
})(jQuery);
