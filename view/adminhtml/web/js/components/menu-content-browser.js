/**
 * Content Browser Component
 * Handles browsing and searching for categories, products, CMS pages
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return {
        currentBrowserModal: null,
        contentBrowserModal: null,
        builder: null,

        /**
         * Initialize with reference to builder
         */
        init: function(builder) {
            this.builder = builder;
            return this;
        },

        /**
         * Open unified content browser
         */
        open: function () {
            var self = this;

            // Close any existing content browser modal
            if (this.contentBrowserModal) {
                try {
                    this.contentBrowserModal.closeModal();
                } catch (e) {}
            }

            var browserContent = `
                <div class="content-browser-container">
                    <div class="admin__field" style="margin-bottom: 20px;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text"
                                   class="content-search-input admin__control-text"
                                   placeholder="${$t('Search categories, products, pages...')}"
                                   style="flex: 1;" />
                            <select class="content-type-filter admin__control-select" style="width: 150px;">
                                <option value="all">${$t('All Content')}</option>
                                <option value="category">${$t('Categories')}</option>
                                <option value="product">${$t('Products')}</option>
                                <option value="cms_page">${$t('CMS Pages')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="content-results-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; background: #fff;">
                        <div style="text-align: center; padding: 40px; color: #999;">
                            ${$t('Start typing to search, or select Categories to browse all')}
                        </div>
                    </div>
                </div>

                <style>
                    .content-item {
                        padding: 12px 15px;
                        border-bottom: 1px solid #eee;
                        cursor: pointer;
                        transition: background 0.2s;
                    }
                    .content-item:hover {
                        background: #f5f5f5;
                    }
                    .content-item-title {
                        font-weight: 600;
                        color: #333;
                        margin-bottom: 4px;
                    }
                    .content-item-meta {
                        font-size: 12px;
                        color: #888;
                    }
                    .content-item-type {
                        display: inline-block;
                        padding: 2px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        font-weight: 600;
                        margin-right: 8px;
                    }
                    .content-item-type.type-category { background: #e3f2fd; color: #1976d2; }
                    .content-item-type.type-product { background: #f3e5f5; color: #7b1fa2; }
                    .content-item-type.type-cms_page { background: #e8f5e9; color: #388e3c; }
                </style>
            `;

            var browserElement = $('<div/>').html(browserContent);

            var browserOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Browse Content'),
                modalClass: 'content-browser-modal',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        this.closeModal();
                    }
                }],
                closed: function() {
                    self.contentBrowserModal = null;
                }
            };

            this.contentBrowserModal = modal(browserOptions, browserElement);
            browserElement.modal('openModal');

            setTimeout(function() {
                self.initializeBrowser(browserElement);
            }, 150);
        },

        /**
         * Initialize content browser event handlers
         */
        initializeBrowser: function (modalElement) {
            var self = this;
            var searchTimer;

            var $searchInput = modalElement.find('.content-search-input');
            var $typeFilter = modalElement.find('.content-type-filter');
            var $resultsContainer = modalElement.find('.content-results-container');

            this.currentBrowserModal = {
                searchInput: $searchInput,
                typeFilter: $typeFilter,
                resultsContainer: $resultsContainer
            };

            $searchInput.on('input', function() {
                clearTimeout(searchTimer);
                var searchTerm = $(this).val();
                var contentType = $typeFilter.val();

                searchTimer = setTimeout(function() {
                    self.search(searchTerm, contentType);
                }, 300);
            });

            $typeFilter.on('change', function() {
                var contentType = $(this).val();
                var searchTerm = $searchInput.val();

                if (contentType === 'category' && !searchTerm) {
                    self.search('', 'category');
                } else {
                    self.search(searchTerm, contentType);
                }
            });
        },

        /**
         * Search content across all types
         */
        search: function (searchTerm, contentType) {
            var self = this;

            if (!this.currentBrowserModal || !this.currentBrowserModal.resultsContainer) {
                return;
            }

            var $resultsContainer = this.currentBrowserModal.resultsContainer;

            $resultsContainer.html('<div style="text-align: center; padding: 40px;"><div class="loader"></div><p>' + $t('Searching...') + '</p></div>');

            $.ajax({
                url: self.builder.getCategoriesUrl,
                type: 'GET',
                data: {
                    type: contentType === 'all' ? 'all' : contentType,
                    search: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    self.renderResults(response, searchTerm);
                },
                error: function(xhr, status, error) {
                    var errorMsg = $t('Error loading content. Please try again.');
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            if (xhr.responseText.length < 500) {
                                errorMsg = xhr.responseText;
                            }
                        }
                    }
                    $resultsContainer.html(
                        '<div class="message message-error" style="margin: 20px;">' +
                        errorMsg + '<br><small>Status: ' + xhr.status + '</small>' +
                        '</div>'
                    );
                }
            });
        },

        /**
         * Render search results
         */
        renderResults: function (response, searchTerm) {
            var self = this;
            var html = '';
            var hasResults = false;

            if (!this.currentBrowserModal || !this.currentBrowserModal.resultsContainer) {
                return;
            }

            var $resultsContainer = this.currentBrowserModal.resultsContainer;

            if (response.error) {
                $resultsContainer.html(
                    '<div class="message message-error" style="margin: 20px;">' +
                    (response.message || 'Unknown error') +
                    '</div>'
                );
                return;
            }

            // Render categories
            if (response.categories && response.categories.length > 0) {
                response.categories.forEach(function(item) {
                    hasResults = true;
                    var indent = (item.level || 0) * 20;
                    var categoryUrl = item.url || '/catalog/category/view/id/' + item.id;
                    html += `
                        <div class="content-item" data-url="${categoryUrl}" style="padding-left: ${15 + indent}px;">
                            <div class="content-item-title">
                                <span class="content-item-type type-category">${$t('Category')}</span>
                                ${self.escapeHtml(item.name)}
                            </div>
                            <div class="content-item-meta">ID: ${item.id} | URL: ${categoryUrl}</div>
                        </div>
                    `;
                });
            }

            // Render products
            if (response.products && response.products.length > 0) {
                response.products.forEach(function(item) {
                    hasResults = true;
                    html += `
                        <div class="content-item" data-url="${item.url}">
                            <div class="content-item-title">
                                <span class="content-item-type type-product">${$t('Product')}</span>
                                ${self.escapeHtml(item.name)}
                            </div>
                            <div class="content-item-meta">SKU: ${item.sku}</div>
                        </div>
                    `;
                });
            }

            // Render CMS pages
            if (response.pages && response.pages.length > 0) {
                response.pages.forEach(function(item) {
                    hasResults = true;
                    html += `
                        <div class="content-item" data-url="/${item.identifier}">
                            <div class="content-item-title">
                                <span class="content-item-type type-cms_page">${$t('CMS Page')}</span>
                                ${self.escapeHtml(item.title)}
                            </div>
                            <div class="content-item-meta">${item.identifier}</div>
                        </div>
                    `;
                });
            }

            if (!hasResults) {
                html = '<div style="text-align: center; padding: 40px; color: #999;">' +
                       $t('No results found. Try a different search term.') +
                       '</div>';
            }

            $resultsContainer.html(html);

            $resultsContainer.find('.content-item').on('click', function() {
                var url = $(this).data('url');
                $('#item-url').val(url);
                if (self.contentBrowserModal) {
                    self.contentBrowserModal.closeModal();
                }
                if (self.builder && self.builder.showNotification) {
                    self.builder.showNotification('success', $t('Content URL set successfully'));
                }
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }
    };
});
