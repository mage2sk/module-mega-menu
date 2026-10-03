define([
    'jquery',
    'Magento_Ui/js/modal/modal-component',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'mage/url'
], function ($, Modal, alert, confirm, $t, urlBuilder) {
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
                content: $t('Are you sure you want to import this menu?') + '<br><br>' +
                         '<strong>' + $t('Title:') + '</strong> ' + menuTitle + '<br>' +
                         '<strong>' + $t('Identifier:') + '</strong> ' + menuIdentifier + '<br><br>' +
                         '<em>' + $t('If a menu with this identifier already exists, it will be updated.') + '</em>',
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
                        self.performImport(menuData);
                    }
                }]
            });
        },

        /**
         * Perform the actual import via AJAX
         */
        performImport: function (menuData) {
            var self = this;

            // Get the correct admin base URL
            var baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, 2).join('/');
            var importUrl = baseUrl + '/panth_menu/menu/import';

            $.ajax({
                url: importUrl,
                type: 'POST',
                data: {
                    menu_data: menuData,
                    form_key: window.FORM_KEY
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: response.message,
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
                    } else {
                        alert({
                            title: $t('Import Failed'),
                            content: $t('Error: ') + response.message,
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
                error: function (xhr, status, error) {
                    alert({
                        title: $t('Import Error'),
                        content: $t('An error occurred while importing the menu: ') + error,
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
            });
        }
    });
});
