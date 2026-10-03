/**
 * Panth MegaMenu - KnockoutJS View Component
 *
 * Main KnockoutJS component for reactive menu data binding.
 * Works in conjunction with the jQuery widget for full functionality.
 *
 * @category    Panth
 * @package     Panth_MegaMenu
 * @copyright   Copyright (c) 2025 Panth InfoTech
 * @license     Proprietary
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function(Component, ko, $, storage, customerData, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Panth_MegaMenu/megamenu',
            menuData: [],
            storeId: null,
            customerId: null,
            cacheEnabled: true,
            cacheLifetime: 3600,
            lazyLoad: true,
            mobileBreakpoint: 768,
            stickyEnabled: false,
            stickyOffset: 100,
            hoverDelay: 300,
            animationSpeed: 300,
            closeOnClick: true,
            showIcons: true,
            rtlEnabled: false
        },

        /**
         * Initialize component
         */
        initialize: function() {
            this._super();

            // Observable properties
            this.menuItems = ko.observableArray([]);
            this.isLoading = ko.observable(false);
            this.isDesktop = ko.observable(true);
            this.isMobile = ko.observable(false);
            this.activeItem = ko.observable(null);
            this.error = ko.observable(null);

            // Computed observables
            this.hasMenuItems = ko.computed(function() {
                return this.menuItems().length > 0;
            }, this);

            this.hasError = ko.computed(function() {
                return this.error() !== null;
            }, this);

            // Initialize
            this._detectDevice();
            this._loadMenuData();
            this._bindEvents();

            return this;
        },

        /**
         * Detect device type
         * @private
         */
        _detectDevice: function() {
            var self = this;
            var width = $(window).width();

            this.isDesktop(width >= this.mobileBreakpoint);
            this.isMobile(!this.isDesktop());

            // Update on resize
            $(window).on('resize.panthMegaMenuKo', _.debounce(function() {
                var newWidth = $(window).width();
                self.isDesktop(newWidth >= self.mobileBreakpoint);
                self.isMobile(!self.isDesktop());
            }, 100));
        },

        /**
         * Load menu data
         * @private
         */
        _loadMenuData: function() {
            var self = this;

            // Check if data is already provided
            if (this.menuData && this.menuData.length > 0) {
                this._processMenuData(this.menuData);
                return;
            }

            // Try to load from cache
            if (this.cacheEnabled) {
                var cached = this._loadFromCache();
                if (cached) {
                    this._processMenuData(cached);
                    return;
                }
            }

            // Load from server
            if (this.lazyLoad) {
                this._loadMenuFromServer();
            }
        },

        /**
         * Load menu from server
         * @private
         */
        _loadMenuFromServer: function() {
            var self = this;

            this.isLoading(true);
            this.error(null);

            storage.get(
                'rest/V1/panth-megamenu/menu',
                {},
                false
            ).done(function(response) {
                if (response && response.items) {
                    self._processMenuData(response.items);

                    if (self.cacheEnabled) {
                        self._saveToCache(response.items);
                    }
                } else {
                    self.error($t('Failed to load menu data'));
                }
            }).fail(function(error) {
                self.error($t('Failed to load menu'));
            }).always(function() {
                self.isLoading(false);
            });
        },

        /**
         * Process menu data
         * @private
         */
        _processMenuData: function(data) {
            var processedItems = this._processItems(data, 0);
            this.menuItems(processedItems);
        },

        /**
         * Process menu items recursively
         * @private
         */
        _processItems: function(items, level) {
            var self = this;

            return _.map(items, function(item, index) {
                var processedItem = {
                    id: item.id || 'item-' + level + '-' + index,
                    label: item.label || '',
                    url: item.url || '#',
                    title: item.title || item.label || '',
                    target: item.target || '',
                    cssClass: item.css_class || '',
                    iconClass: item.icon_class || '',
                    badgeText: item.badge_text || '',
                    badgeClass: item.badge_class || '',
                    isActive: ko.observable(item.is_active || false),
                    isVisible: ko.observable(item.is_visible !== false),
                    isDisabled: ko.observable(item.is_disabled || false),
                    isLoading: ko.observable(false),
                    isMegaMenu: item.is_mega_menu || false,
                    megaMenuColumns: item.mega_menu_columns || 3,
                    megaMenuContent: item.mega_menu_content || '',
                    megaMenuFooter: item.mega_menu_footer || '',
                    columnTitle: item.column_title || '',
                    description: item.description || '',
                    level: level,
                    index: index,
                    hasChildren: ko.observable(item.children && item.children.length > 0),
                    children: ko.observableArray([])
                };

                // Process children recursively
                if (item.children && item.children.length > 0) {
                    var children = self._processItems(item.children, level + 1);
                    processedItem.children(children);
                }

                // Observable name for template binding
                processedItem['isActive' + index] = processedItem.isActive;
                processedItem['isLoading' + index] = processedItem.isLoading;
                processedItem['children' + index] = processedItem.children;

                return processedItem;
            });
        },

        /**
         * Load from cache
         * @private
         */
        _loadFromCache: function() {
            try {
                var cacheKey = 'panth_megamenu_ko_' + this.storeId;
                var cached = localStorage.getItem(cacheKey);

                if (cached) {
                    var data = JSON.parse(cached);
                    var now = Math.floor(Date.now() / 1000);

                    if (data.expires > now) {
                        return data.items;
                    }
                }
            } catch (e) {
            }

            return null;
        },

        /**
         * Save to cache
         * @private
         */
        _saveToCache: function(items) {
            try {
                var cacheKey = 'panth_megamenu_ko_' + this.storeId;
                var now = Math.floor(Date.now() / 1000);

                var cacheData = {
                    items: items,
                    created: now,
                    expires: now + this.cacheLifetime
                };

                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            } catch (e) {
            }
        },

        /**
         * Bind events
         * @private
         */
        _bindEvents: function() {
            var self = this;

            // Listen to customer data changes
            var sections = customerData.get('menu-updates');
            sections.subscribe(function(data) {
                if (data && data.reload) {
                    self.refresh();
                }
            });
        },

        /**
         * Handle menu item click
         */
        onItemClick: function(item, event) {
            // Set active item
            this.activeItem(item);

            // Toggle active state
            if (item.hasChildren()) {
                event.preventDefault();
                item.isActive(!item.isActive());

                // Close siblings
                var parent = this.menuItems();
                _.each(parent, function(sibling) {
                    if (sibling.id !== item.id) {
                        sibling.isActive(false);
                    }
                });
            }

            return true;
        },

        /**
         * Handle menu item hover
         */
        onItemHover: function(item, event) {
            // Prefetch submenu content if lazy loading
            if (this.lazyLoad && item.hasChildren() && item.children().length === 0) {
                this._loadSubmenuContent(item);
            }
        },

        /**
         * Load submenu content
         * @private
         */
        _loadSubmenuContent: function(item) {
            var self = this;

            if (item.isLoading()) {
                return;
            }

            item.isLoading(true);

            storage.get(
                'rest/V1/panth-megamenu/menu/' + item.id,
                {},
                false
            ).done(function(response) {
                if (response && response.children) {
                    var children = self._processItems(response.children, item.level + 1);
                    item.children(children);
                    item.hasChildren(children.length > 0);
                }
            }).fail(function(error) {
            }).always(function() {
                item.isLoading(false);
            });
        },

        /**
         * Close all menus
         */
        closeAll: function() {
            this.activeItem(null);
            this._closeAllItems(this.menuItems());
        },

        /**
         * Close all items recursively
         * @private
         */
        _closeAllItems: function(items) {
            var self = this;

            _.each(items, function(item) {
                item.isActive(false);

                if (item.hasChildren()) {
                    self._closeAllItems(item.children());
                }
            });
        },

        /**
         * Refresh menu data
         */
        refresh: function() {
            this.menuItems([]);
            this._loadMenuFromServer();
        },

        /**
         * Get menu item by ID
         */
        getItemById: function(itemId) {
            return this._findItem(this.menuItems(), itemId);
        },

        /**
         * Find item recursively
         * @private
         */
        _findItem: function(items, itemId) {
            var self = this;
            var found = null;

            _.each(items, function(item) {
                if (item.id === itemId) {
                    found = item;
                    return false; // break
                }

                if (item.hasChildren()) {
                    var childResult = self._findItem(item.children(), itemId);
                    if (childResult) {
                        found = childResult;
                        return false; // break
                    }
                }
            });

            return found;
        },

        /**
         * Get active items
         */
        getActiveItems: function() {
            return this._getActiveItemsRecursive(this.menuItems());
        },

        /**
         * Get active items recursively
         * @private
         */
        _getActiveItemsRecursive: function(items) {
            var self = this;
            var active = [];

            _.each(items, function(item) {
                if (item.isActive()) {
                    active.push(item);
                }

                if (item.hasChildren()) {
                    active = active.concat(self._getActiveItemsRecursive(item.children()));
                }
            });

            return active;
        },

        /**
         * Destroy component
         */
        destroy: function() {
            $(window).off('.panthMegaMenuKo');
            this._super();
        }
    });
});
