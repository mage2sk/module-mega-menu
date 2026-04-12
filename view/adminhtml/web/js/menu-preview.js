/**
 * Mega Menu Preview Modal
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'uiRegistry'
], function ($, modal, $t, registry) {
    'use strict';

    /**
     * Initialize preview modal
     */
    window.previewMegaMenu = function() {
        // Get form data
        var formData = getFormData();

        // Build preview URL with parameters
        var previewUrl = buildPreviewUrl(formData);

        // Create modal content if not exists
        if ($('#megamenu-preview-modal').length === 0) {
            $('body').append(
                '<div id="megamenu-preview-modal" style="display:none;">' +
                    '<div class="preview-device-selector">' +
                        '<button type="button" class="preview-device-btn active" data-device="desktop">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="3" width="20" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 21h8M12 17v4"/></svg>' +
                            '<span>' + $t('Desktop') + '</span>' +
                        '</button>' +
                        '<button type="button" class="preview-device-btn" data-device="tablet">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="2" width="14" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="19" r="1" fill="currentColor"/></svg>' +
                            '<span>' + $t('Tablet') + '</span>' +
                        '</button>' +
                        '<button type="button" class="preview-device-btn" data-device="mobile">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="7" y="2" width="10" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="19" r="1" fill="currentColor"/></svg>' +
                            '<span>' + $t('Mobile') + '</span>' +
                        '</button>' +
                        '<button type="button" class="preview-refresh-btn" title="' + $t('Refresh Preview') + '">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="preview-iframe-wrapper">' +
                        '<iframe id="megamenu-preview-iframe" name="megamenu-preview-iframe" frameborder="0"></iframe>' +
                        '<div class="preview-loading">' +
                            '<div class="preview-spinner"></div>' +
                            '<p>' + $t('Loading preview...') + '</p>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        }

        // Initialize modal
        var modalOptions = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $t('Menu Preview'),
            modalClass: 'megamenu-preview-modal',
            buttons: [{
                text: $t('Close'),
                class: 'action-secondary',
                click: function() {
                    this.closeModal();
                }
            }]
        };

        var previewModal = modal(modalOptions, $('#megamenu-preview-modal'));

        // Open modal
        $('#megamenu-preview-modal').modal('openModal');

        // Load preview in iframe
        loadPreview(previewUrl);

        // Device selector handlers
        $('.preview-device-btn').off('click').on('click', function() {
            var device = $(this).data('device');
            $('.preview-device-btn').removeClass('active');
            $(this).addClass('active');
            setDeviceView(device);
        });

        // Refresh button handler
        $('.preview-refresh-btn').off('click').on('click', function() {
            var formData = getFormData();
            var previewUrl = buildPreviewUrl(formData);
            loadPreview(previewUrl);
        });
    };

    /**
     * Get form data from UI component
     */
    function getFormData() {
        var data = {
            items_json: '[]',
            container_bg_color: '',
            container_padding: '',
            container_margin: '',
            item_gap: '',
            container_max_width: '',
            container_border: '',
            container_border_radius: '',
            container_box_shadow: '',
            menu_alignment: '',
            custom_css: '',
            css_class: ''
        };

        // Try to get data from UI registry first (most reliable)
        try {
            var fieldNames = Object.keys(data);
            fieldNames.forEach(function(fieldName) {
                var field = registry.get('panth_menu_form.panth_menu_form.general.' + fieldName);

                // Try menu_styling fieldset if not in general
                if (!field) {
                    field = registry.get('panth_menu_form.panth_menu_form.menu_styling.' + fieldName);
                }

                // Try menu_items fieldset
                if (!field) {
                    field = registry.get('panth_menu_form.panth_menu_form.menu_items.' + fieldName);
                }

                if (field && typeof field.value === 'function') {
                    var value = field.value();
                    if (value !== null && value !== undefined) {
                        data[fieldName] = value;
                    }
                }
            });
        } catch (e) {
            console.warn('Could not get data from UI registry:', e);
        }

        // Fallback: Get data from DOM form fields
        $('[data-index] input, [data-index] textarea, [data-index] select').each(function() {
            var $field = $(this);
            var name = $field.attr('name');

            if (name && data.hasOwnProperty(name)) {
                var value = $field.val();
                if (value) {
                    data[name] = value;
                }
            }
        });

        // Special handling for items_json from menu builder
        if (window.menuBuilderData && window.menuBuilderData.items) {
            data.items_json = JSON.stringify(window.menuBuilderData.items);
        }

        // Log for debugging
        console.log('Preview data:', {
            items_json_length: data.items_json.length,
            has_items: data.items_json !== '[]',
            items_preview: data.items_json.substring(0, 100)
        });

        return data;
    }

    /**
     * Build preview URL with parameters
     */
    function buildPreviewUrl(data) {
        // Get frontend base URL (strip admin path if present)
        var baseUrl = window.location.origin + '/';

        // If BASE_URL is defined and contains admin path, extract the base
        if (typeof BASE_URL !== 'undefined' && BASE_URL) {
            // Extract just the domain part before any admin path
            var urlParts = BASE_URL.split('/');
            if (urlParts.length >= 3) {
                baseUrl = urlParts[0] + '//' + urlParts[2] + '/';
            }
        }

        // Generate unique secret key for this preview session
        var secretKey = 'preview_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        // Store data for POST submission
        data.secret_key = secretKey;

        // Build URL with key + cache-busting timestamp
        var url = baseUrl + 'panth_menu/preview/index?key=' + encodeURIComponent(secretKey) + '&_t=' + Date.now();

        return {
            url: url,
            data: data
        };
    }

    /**
     * Load preview in iframe
     */
    function loadPreview(previewConfig) {
        var $iframe = $('#megamenu-preview-iframe');
        var $loading = $('.preview-loading');

        $loading.show();

        // Create a form to submit POST data to iframe
        var $form = $('<form>', {
            method: 'POST',
            action: previewConfig.url,
            target: 'megamenu-preview-iframe',
            style: 'display:none;'
        });

        // Add form fields for all data
        for (var key in previewConfig.data) {
            if (previewConfig.data.hasOwnProperty(key)) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: previewConfig.data[key]
                }));
            }
        }

        // Append form to body and submit
        $('body').append($form);

        $iframe.off('load').on('load', function() {
            $loading.fadeOut(300);
            $form.remove(); // Clean up form after load
        });

        $form.submit();
    }

    /**
     * Set device view
     */
    function setDeviceView(device) {
        var $wrapper = $('.preview-iframe-wrapper');

        $wrapper.removeClass('device-desktop device-tablet device-mobile');
        $wrapper.addClass('device-' + device);
    }

    return {
        previewMegaMenu: window.previewMegaMenu
    };
});
