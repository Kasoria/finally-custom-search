/**
 * Custom Facet Search - Admin JavaScript
 */

(function($) {
    'use strict';

    const CFSAdmin = {
        init: function() {
            this.bindEvents();
            this.initFormHandlers();
        },

        bindEvents: function() {
            const self = this;

            // Facet type change
            $('#cfs-type').on('change', function() {
                self.toggleTypeSettings($(this).val());
            });

            // Source change
            $('#cfs-source').on('change', function() {
                self.toggleSourceFields($(this).val());
            });

            // Delete facet
            $(document).on('click', '.cfs-delete-facet', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                self.deleteFacet(id, $(this).closest('tr'));
            });

            // Copy shortcode
            $(document).on('click', '.cfs-copy-shortcode', function() {
                const shortcode = $(this).siblings('.cfs-shortcode-display').text();
                self.copyToClipboard(shortcode);
                
                const $btn = $(this);
                $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                setTimeout(() => {
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            });

            // Auto-generate slug from name
            $('#cfs-name').on('blur', function() {
                const $slug = $('#cfs-slug');
                if (!$slug.val()) {
                    $slug.val(self.generateSlug($(this).val()));
                }
            });

            // Meta key suggestions
            $('#cfs-meta-key').on('focus', function() {
                self.loadMetaKeySuggestions();
            });
        },

        initFormHandlers: function() {
            const self = this;

            // Form submission
            $('#cfs-facet-form').on('submit', function(e) {
                e.preventDefault();
                self.saveFacet($(this));
            });

            // Initial state
            this.toggleTypeSettings($('#cfs-type').val());
            this.toggleSourceFields($('#cfs-source').val());
        },

        toggleTypeSettings: function(type) {
            // Hide all type-specific settings
            $('.cfs-type-settings').hide();

            // Show relevant settings
            if (type === 'range') {
                $('#cfs-range-settings').show();
            } else if (['checkbox', 'radio', 'dropdown'].includes(type)) {
                $('#cfs-list-settings').show();
                
                // Toggle dropdown-specific options
                if (type === 'dropdown') {
                    $('.cfs-dropdown-only').show();
                } else {
                    $('.cfs-dropdown-only').hide();
                }
            } else if (type === 'date') {
                $('#cfs-date-settings').show();
            }
        },

        toggleSourceFields: function(source) {
            // Hide all source fields
            $('.cfs-source-taxonomy, .cfs-source-custom-field, .cfs-source-post-attribute').hide();

            // Show relevant field
            if (source === 'taxonomy') {
                $('.cfs-source-taxonomy').show();
            } else if (source === 'custom_field') {
                $('.cfs-source-custom-field').show();
            } else if (source === 'post_attribute') {
                $('.cfs-source-post-attribute').show();
            }
        },

        saveFacet: function($form) {
            const $submitBtn = $form.find('button[type="submit"]');
            const $result = $('#cfs-save-result');
            
            $submitBtn.prop('disabled', true).text('Saving...');
            $result.html('');

            // Get correct source_key based on source type
            const source = $form.find('[name="source"]').val();
            let sourceKey = '';
            
            if (source === 'taxonomy') {
                sourceKey = $form.find('[name="source_key_taxonomy"]').val();
            } else if (source === 'custom_field') {
                sourceKey = $form.find('[name="source_key_meta"]').val();
            } else if (source === 'post_attribute') {
                sourceKey = $form.find('[name="source_key_attribute"]').val();
            }

            // Build form data
            const formData = new FormData($form[0]);
            formData.append('action', 'cfs_save_facet');
            formData.append('nonce', cfsAdmin.nonce);
            formData.append('source_key', sourceKey);

            $.ajax({
                url: cfsAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p><p>Shortcode: <code>' + response.data.shortcode + '</code></p></div>');
                        
                        // If new facet, redirect to edit page
                        if (!$form.find('[name="id"]').val()) {
                            setTimeout(function() {
                                window.location.href = 'admin.php?page=custom-facet-search';
                            }, 1500);
                        }
                    } else {
                        $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $result.html('<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text($form.find('[name="id"]').val() ? 'Update Facet' : 'Create Facet');
                }
            });
        },

        deleteFacet: function(id, $row) {
            if (!confirm('Are you sure you want to delete this facet?')) {
                return;
            }

            $.ajax({
                url: cfsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfs_delete_facet',
                    nonce: cfsAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        loadMetaKeySuggestions: function() {
            const $suggestions = $('#cfs-meta-key-suggestions');
            const postTypes = [];
            
            $('input[name="post_types[]"]:checked').each(function() {
                postTypes.push($(this).val());
            });

            if (!postTypes.length) {
                postTypes.push('post');
            }

            $.ajax({
                url: cfsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cfs_get_meta_keys',
                    nonce: cfsAdmin.nonce,
                    post_type: postTypes[0]
                },
                success: function(response) {
                    if (response.success && response.data.meta_keys.length) {
                        let html = '<p class="description">Available meta keys:</p><div class="cfs-meta-keys-list">';
                        response.data.meta_keys.forEach(function(key) {
                            html += '<button type="button" class="button button-small cfs-meta-key-btn" data-key="' + key + '">' + key + '</button> ';
                        });
                        html += '</div>';
                        $suggestions.html(html);

                        // Click handler for suggestions
                        $suggestions.find('.cfs-meta-key-btn').on('click', function() {
                            $('#cfs-meta-key').val($(this).data('key'));
                            $suggestions.empty();
                        });
                    }
                }
            });
        },

        generateSlug: function(text) {
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/--+/g, '-')
                .trim();
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            } else {
                // Fallback
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
            }
        }
    };

    $(document).ready(function() {
        CFSAdmin.init();
    });

})(jQuery);
