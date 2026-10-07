/**
 * Import Handler Component
 * Handles importing categories as menu items
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, modal, confirm, $t) {
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
         * Import all categories
         */
        importCategories: function () {
            var self = this;

            confirm({
                title: $t('Import Categories'),
                content: $t('This will import all active categories from your store as menu items. Existing items will not be affected. Continue?'),
                actions: {
                    confirm: function () {
                        self.performImport();
                    }
                }
            });
        },

        /**
         * Perform the actual import
         */
        performImport: function () {
            var self = this;

            // Show loading modal
            var loadingContent = '<div style="text-align: center; padding: 40px;">' +
                                '<div class="loader"></div>' +
                                '<p>' + $t('Importing categories...') + '</p>' +
                                '</div>';

            var importElement = $('<div/>').html(loadingContent);

            var importOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Import Categories'),
                modalClass: 'import-categories-modal',
                buttons: []
            };

            var importModal = modal(importOptions, importElement);
            importElement.modal('openModal');

            // Fetch all categories
            $.ajax({
                url: self.builder.getCategoriesUrl,
                type: 'GET',
                data: {
                    type: 'category'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        importElement.html(
                            '<div class="message message-error" style="margin: 20px;">' +
                            $t('Error: ') + (response.message || 'Unknown error') +
                            '</div>'
                        );
                        setTimeout(function() {
                            importElement.modal('closeModal');
                        }, 3000);
                        return;
                    }

                    if (!response.categories || response.categories.length === 0) {
                        importElement.html(
                            '<div class="message message-warning" style="margin: 20px;">' +
                            $t('No categories found to import.') +
                            '</div>'
                        );
                        setTimeout(function() {
                            importElement.modal('closeModal');
                        }, 2000);
                        return;
                    }

                    // Process categories
                    self.processImportedCategories(response.categories, importElement);
                },
                error: function(xhr, status, error) {
                    var errorMsg = $t('An error occurred while importing categories: ') + error;

                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = $t('Error: ') + response.message;
                            }
                        } catch (e) {
                            if (xhr.responseText.indexOf('<!DOCTYPE') === 0 || xhr.responseText.indexOf('<html') === 0) {
                                errorMsg = $t('An error occurred while importing categories: Server returned an error page. Please check if you are still logged in.');
                            }
                        }
                    }

                    importElement.html(
                        '<div class="message message-error" style="margin: 20px;">' +
                        errorMsg +
                        '</div>'
                    );

                    setTimeout(function() {
                        importElement.modal('closeModal');
                    }, 4000);
                }
            });
        },

        /**
         * Process imported categories and add to menu
         */
        processImportedCategories: function(categories, importElement) {
            var self = this;

            if (!this.builder) {
                return;
            }

            try {
                // Map category IDs to menu item IDs
                var categoryIdMap = {};
                var importedItems = [];

                // Sort categories by level to ensure parents are created first
                categories.sort(function(a, b) {
                    return (a.level || 0) - (b.level || 0);
                });

                categories.forEach(function(category) {
                    // Generate unique menu item ID
                    var menuItemId = 'cat_' + category.id;

                    // Map parent_id - only if parent exists in our map
                    var parentMenuItemId = 0;
                    if (category.parent_id && category.parent_id > 2 && categoryIdMap[category.parent_id]) {
                        parentMenuItemId = categoryIdMap[category.parent_id];
                    }

                    // Calculate actual level for the menu (0-based from root)
                    var actualLevel = Math.max(0, (category.level || 0));

                    // Store mapping
                    categoryIdMap[category.id] = menuItemId;

                    // Create menu item
                    var menuItem = {
                        item_id: menuItemId,
                        title: category.name,
                        url: category.url || '#',
                        item_type: 'category',
                        parent_id: parentMenuItemId,
                        position: importedItems.length,
                        level: actualLevel,
                        is_active: 1,
                        css_class: '',
                        icon_class: '',
                        image: '',
                        target: '_self',
                        device_visibility: 'all'
                    };

                    importedItems.push(menuItem);
                });

                // Add items to the builder
                var currentItems = this.builder.items() || [];
                var combinedItems = currentItems.concat(importedItems);
                this.builder.items(combinedItems);

                // Show success message
                importElement.html(
                    '<div class="message message-success" style="margin: 20px;">' +
                    $t('Successfully imported %1 categories!').replace('%1', importedItems.length) +
                    '</div>'
                );

                // Refresh the menu display
                if (this.builder.render) {
                    this.builder.render();
                }

                setTimeout(function() {
                    importElement.modal('closeModal');
                }, 2000);

            } catch (error) {
                importElement.html(
                    '<div class="message message-error" style="margin: 20px;">' +
                    $t('Error processing categories: ') + error.message +
                    '</div>'
                );

                setTimeout(function() {
                    importElement.modal('closeModal');
                }, 3000);
            }
        }
    };
});
