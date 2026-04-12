/**
 * Menu Preview Component
 * Handles menu preview in different device modes
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'uiRegistry',
    'mage/translate'
], function ($, modal, alert, registry, $t) {
    'use strict';

    return {
        builder: null,

        /**
         * Initialize with reference to builder
         */
        init: function(builder) {
            this.builder = builder;
            return this;
        },

        /**
         * Show preview modal
         */
        show: function () {
            var self = this;

            // Get form data
            var formData = this.getFormData();

            // Build preview URL
            var previewUrl = this.buildPreviewUrl(formData);

            // Create modal HTML with iframe
            var modalContent = this.createPreviewModal(previewUrl);
            var previewElement = $('<div/>').html(modalContent);

            var previewOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Menu Preview - Live Frontend Preview'),
                modalClass: 'megamenu-preview-modal',
                buttons: [{
                    text: $t('Close'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            var previewModal = modal(previewOptions, previewElement);
            previewElement.modal('openModal');

            // Initialize device switcher and refresh button
            setTimeout(function() {
                self.initializeDeviceSwitcher(previewElement);
                self.initializeRefreshButton(previewElement, formData);
            }, 100);
        },

        /**
         * Get form data
         */
        getFormData: function() {
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

            // Get data from form fields
            $('[data-index] input, [data-index] textarea, [data-index] select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');

                if (name && data.hasOwnProperty(name)) {
                    data[name] = $field.val() || '';
                }
            });

            // Get items_json from data source
            try {
                var dataSource = registry.get('panth_menu_form.panth_menu_form_data_source');
                if (dataSource && dataSource.get('data.items_json')) {
                    data.items_json = dataSource.get('data.items_json');
                }
            } catch (e) {
            }

            return data;
        },

        /**
         * Build preview URL
         */
        buildPreviewUrl: function(formData) {
            var baseUrl = window.location.origin + '/';
            var url = baseUrl + 'panth_menu/preview/index?';

            var params = [];
            for (var key in formData) {
                if (formData.hasOwnProperty(key)) {
                    params.push(encodeURIComponent(key) + '=' + encodeURIComponent(formData[key]));
                }
            }

            return url + params.join('&');
        },

        /**
         * Create preview modal HTML
         */
        createPreviewModal: function(previewUrl) {
            return `
                <div class="preview-device-selector">
                    <button type="button" class="preview-device-btn action-default active" data-device="desktop">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="2" y="3" width="20" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 21h8M12 17v4"/>
                        </svg>
                        <span>${$t('Desktop')}</span>
                    </button>
                    <button type="button" class="preview-device-btn action-default" data-device="tablet">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="5" y="2" width="14" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="19" r="1" fill="currentColor"/>
                        </svg>
                        <span>${$t('Tablet')}</span>
                    </button>
                    <button type="button" class="preview-device-btn action-default" data-device="mobile">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="7" y="2" width="10" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="19" r="1" fill="currentColor"/>
                        </svg>
                        <span>${$t('Mobile')}</span>
                    </button>
                    <button type="button" class="preview-refresh-btn action-default" title="${$t('Refresh Preview')}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/>
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                    </button>
                </div>
                <div class="preview-iframe-wrapper">
                    <iframe id="megamenu-preview-iframe" src="${previewUrl}" frameborder="0"></iframe>
                    <div class="preview-loading">
                        <div class="preview-spinner"></div>
                        <p>${$t('Loading preview...')}</p>
                    </div>
                </div>
            `;
        },

        /**
         * Initialize device switcher
         */
        initializeDeviceSwitcher: function(previewElement) {
            var $iframe = previewElement.find('#megamenu-preview-iframe');
            var $wrapper = previewElement.find('.preview-iframe-wrapper');
            var $loading = previewElement.find('.preview-loading');

            // Hide loading when iframe loads
            $iframe.on('load', function() {
                $loading.fadeOut(300);
            });

            // Device switcher
            previewElement.find('.preview-device-btn').on('click', function() {
                var device = $(this).data('device');

                previewElement.find('.preview-device-btn').removeClass('active');
                $(this).addClass('active');

                $wrapper.removeClass('device-desktop device-tablet device-mobile');
                $wrapper.addClass('device-' + device);
            });
        },

        /**
         * Initialize refresh button
         */
        initializeRefreshButton: function(previewElement, originalFormData) {
            var self = this;

            previewElement.find('.preview-refresh-btn').on('click', function() {
                // Get latest form data
                var formData = self.getFormData();

                // Build new preview URL
                var previewUrl = self.buildPreviewUrl(formData);

                // Show loading
                var $loading = previewElement.find('.preview-loading');
                $loading.show();

                // Reload iframe
                var $iframe = previewElement.find('#megamenu-preview-iframe');
                $iframe.attr('src', previewUrl);
            });
        }
    };
});
