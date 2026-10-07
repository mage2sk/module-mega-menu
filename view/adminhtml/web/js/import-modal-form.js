define([
    'jquery',
    'Magento_Ui/js/modal/modal-component',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'mage/url',
    'uiRegistry'
], function ($, Modal, alert, confirm, $t, urlBuilder, registry) {
    'use strict';

    return Modal.extend({
        defaults: {
            imports: {
                provider: '${ $.provider }'
            }
        },

        /**
         * Initialize modal and bind file input handler
         */
        initialize: function () {
            this._super();

            // Bind file input handler multiple times to ensure it catches the element
            var self = this;
            setTimeout(function() { self.bindFileInput(); }, 500);
            setTimeout(function() { self.bindFileInput(); }, 1000);
            setTimeout(function() { self.bindFileInput(); }, 2000);

            return this;
        },

        /**
         * Override openModal to bind file input when modal opens
         */
        openModal: function () {
            this._super();

            // Bind file input when modal opens
            var self = this;
            setTimeout(function() { self.bindFileInput(); }, 100);
            setTimeout(function() { self.bindFileInput(); }, 500);

            return this;
        },

        /**
         * Bind file input change handler
         */
        bindFileInput: function () {
            var self = this;
            var $fileInput = $('#json-file-input');
            var $statusDiv = $('#file-upload-status');

            if ($fileInput.length === 0) {
                return;
            }

            // Unbind first to prevent multiple handlers
            $fileInput.off('change.fileupload');

            // Bind the change event
            $fileInput.on('change.fileupload', function (e) {
                var file = e.target.files[0];
                $statusDiv.hide();

                if (!file) {
                    return;
                }

                // Check if it's a JSON file
                if (!file.name.endsWith('.json')) {
                    $statusDiv.css('color', '#d32f2f').text($t('Please select a JSON file.')).show();
                    $(this).val('');
                    return;
                }

                // Show loading status
                $statusDiv.css('color', '#1979c3').text($t('Loading file...')).show();

                // Read the file
                var reader = new FileReader();

                reader.onload = function (event) {
                    try {
                        var jsonContent = event.target.result;

                        // Validate it's valid JSON
                        var parsedData = JSON.parse(jsonContent);

                        // Get the textarea element and populate it
                        var textareaElement = self.elems()[0].elems().find(function(elem) {
                            return elem.index === 'menu_data';
                        });

                        if (textareaElement) {
                            // Format JSON with indentation for better readability
                            var formattedJson = JSON.stringify(parsedData, null, 2);
                            textareaElement.value(formattedJson);

                            // Show success message
                            $statusDiv.css('color', '#006400').html('✓ ' + $t('File loaded successfully: ') + file.name).show();
                        } else {
                            throw new Error('Could not find textarea element');
                        }
                    } catch (error) {
                        $statusDiv.css('color', '#d32f2f').html('✗ ' + $t('Invalid JSON file: ') + error.message).show();
                        $('#json-file-input').val('');
                    }
                };

                reader.onerror = function () {
                    $statusDiv.css('color', '#d32f2f').html('✗ ' + $t('Error reading file. Please try again.')).show();
                    $('#json-file-input').val('');
                };

                reader.readAsText(file);
            });
        },

        /**
         * Handle import action
         */
        actionDone: function () {
            var self = this;

            // Get the textarea element
            var textareaElement = this.elems()[0].elems().find(function(elem) {
                return elem.index === 'menu_data';
            });

            var menuData = textareaElement ? textareaElement.value() : '';

            if (!menuData) {
                alert({
                    title: $t('Validation Error'),
                    content: $t('Please enter menu JSON data or upload a JSON file.'),
                    modalClass: 'confirm',
                    buttons: [{
                        text: $t('OK'),
                        class: 'action-primary action-accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
                return;
            }

            // Validate JSON
            var parsedData;
            try {
                parsedData = JSON.parse(menuData);
            } catch (e) {
                alert({
                    title: $t('Invalid JSON'),
                    content: $t('Invalid JSON format: ') + e.message,
                    modalClass: 'confirm',
                    buttons: [{
                        text: $t('OK'),
                        class: 'action-primary action-accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
                return;
            }

            // Show confirmation dialog
            var menuTitle = parsedData.menu && parsedData.menu.title ? parsedData.menu.title : 'Unknown';
            var menuIdentifier = parsedData.menu && parsedData.menu.identifier ? parsedData.menu.identifier : 'Unknown';

            confirm({
                title: $t('Confirm Import'),
                content: $t('Are you sure you want to import this menu data into the current form?') + '<br><br>' +
                         '<strong>' + $t('Title:') + '</strong> ' + menuTitle + '<br>' +
                         '<strong>' + $t('Identifier:') + '</strong> ' + menuIdentifier + '<br><br>' +
                         '<em>' + $t('This will replace all current form data with the imported data. Any unsaved changes will be lost.') + '</em>',
                modalClass: 'confirm',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        this.closeModal(true);
                    }
                }, {
                    text: $t('Import'),
                    class: 'action-primary action-accept',
                    click: function () {
                        this.closeModal(true);
                        self.performImport(parsedData);
                    }
                }]
            });
        },

        /**
         * Perform the import by populating form fields
         */
        performImport: function (parsedData) {
            var self = this;

            try {
                // Get the form data provider
                var formProvider = registry.get('panth_menu_form.panth_menu_form_data_source');

                if (!formProvider) {
                    throw new Error('Form provider not found');
                }

                // Close the import modal
                self.closeModal();

                // Update form fields with imported data
                var menuData = parsedData.menu || {};
                var itemsData = parsedData.items || [];

                // Set basic fields
                if (menuData.title) {
                    this.updateFormField('title', menuData.title);
                }
                if (menuData.identifier) {
                    this.updateFormField('identifier', menuData.identifier);
                }
                if (menuData.is_active !== undefined) {
                    this.updateFormField('is_active', menuData.is_active);
                }
                if (menuData.css_class !== undefined) {
                    this.updateFormField('css_class', menuData.css_class);
                }
                if (menuData.sort_order !== undefined) {
                    this.updateFormField('sort_order', menuData.sort_order);
                }
                if (menuData.description !== undefined) {
                    this.updateFormField('description', menuData.description);
                }
                if (menuData.custom_css !== undefined) {
                    this.updateFormField('custom_css', menuData.custom_css);
                }

                // Set container styling fields
                if (menuData.container_bg_color !== undefined) {
                    this.updateFormField('container_bg_color', menuData.container_bg_color);
                }
                if (menuData.container_padding !== undefined) {
                    this.updateFormField('container_padding', menuData.container_padding);
                }
                if (menuData.container_margin !== undefined) {
                    this.updateFormField('container_margin', menuData.container_margin);
                }
                if (menuData.item_gap !== undefined) {
                    this.updateFormField('item_gap', menuData.item_gap);
                }
                if (menuData.container_max_width !== undefined) {
                    this.updateFormField('container_max_width', menuData.container_max_width);
                }
                if (menuData.container_border !== undefined) {
                    this.updateFormField('container_border', menuData.container_border);
                }
                if (menuData.container_border_radius !== undefined) {
                    this.updateFormField('container_border_radius', menuData.container_border_radius);
                }
                if (menuData.container_box_shadow !== undefined) {
                    this.updateFormField('container_box_shadow', menuData.container_box_shadow);
                }
                if (menuData.menu_alignment !== undefined) {
                    this.updateFormField('menu_alignment', menuData.menu_alignment);
                }

                // Set items JSON
                var itemsJson = JSON.stringify(itemsData);
                this.updateFormField('items_json', itemsJson);

                // Update menu builder if available
                if (window.menuBuilderData) {
                    window.menuBuilderData.items = itemsData;
                }

                // Trigger form refresh
                setTimeout(function() {
                    // Reload the page to ensure the menu builder picks up the new items
                    alert({
                        title: $t('Success'),
                        content: $t('Menu data imported successfully. Please save the form to apply changes.'),
                        modalClass: 'confirm',
                        buttons: [{
                            text: $t('OK'),
                            class: 'action-primary action-accept',
                            click: function () {
                                this.closeModal(true);
                                window.location.reload();
                            }
                        }]
                    });
                }, 500);

            } catch (error) {
                alert({
                    title: $t('Import Error'),
                    content: $t('An error occurred while importing: ') + error.message,
                    modalClass: 'confirm',
                    buttons: [{
                        text: $t('OK'),
                        class: 'action-primary action-accept',
                        click: function () {
                            this.closeModal(true);
                        }
                    }]
                });
            }
        },

        /**
         * Update a form field value
         */
        updateFormField: function (fieldName, value) {
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
                field.value(value);
            }
        }
    });
});
