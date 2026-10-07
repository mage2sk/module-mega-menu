/**
 * Panth MegaMenu - jQuery Widget for Luma Theme
 *
 * Main jQuery widget for MegaMenu functionality in Luma theme.
 * Handles desktop mega menu, mobile navigation, sticky header, and all interactions.
 *
 * @category    Panth
 * @package     Panth_MegaMenu
 * @copyright   Copyright (c) 2025 Panth InfoTech
 * @license     Proprietary
 *
 * Usage:
 * $('#menu-element').panthMegaMenu({
 *     mobileBreakpoint: 768,
 *     stickyEnabled: true,
 *     hoverDelay: 300
 * });
 */

define([
    'jquery',
    'jquery-ui-modules/widget',
    'mage/menu',
    'mage/translate',
    'domReady!'
], function($, widget, menu, $t) {
    'use strict';

    /**
     * PanthMegaMenu Widget
     *
     * @widget panthMegaMenu
     */
    $.widget('panth.megaMenu', {
        options: {
            // Menu data
            menuData: null,
            storeId: null,
            customerId: null,

            // Cache options
            cacheEnabled: true,
            cacheLifetime: 3600,
            cacheKey: 'panth_megamenu_',

            // Lazy loading
            lazyLoad: true,

            // Responsive
            mobileBreakpoint: 768,
            isDesktop: true,
            isMobile: false,

            // Sticky header
            stickyEnabled: false,
            stickyOffset: 100,
            stickyClass: 'sticky',

            // Hover behavior
            hoverDelay: 300,
            hoverIntent: true,

            // Animation
            animationSpeed: 300,
            animationEasing: 'swing',

            // Behavior
            closeOnClick: true,
            closeOnOutsideClick: true,
            closeOnEscape: true,

            // Appearance
            showIcons: true,
            rtlEnabled: false,

            // Analytics
            trackClicks: false,
            trackHovers: false,

            // Accessibility
            keyboardNavigation: true,
            ariaLabels: true,
            focusTrap: true,

            // Selectors
            desktopMenuSelector: '.panth-megamenu-desktop',
            mobileMenuSelector: '.panth-megamenu-mobile',
            menuItemSelector: '.level0',
            submenuSelector: '.submenu',
            mobileToggleSelector: '[data-action="toggle-nav"]',
            mobileCloseSelector: '[data-action="close-nav"]',

            // Classes
            activeClass: 'active',
            hoverClass: 'hover',
            loadingClass: 'loading',
            disabledClass: 'disabled',

            // Callbacks
            onInit: null,
            onReady: null,
            onMenuOpen: null,
            onMenuClose: null,
            onItemClick: null,
            onResize: null,
            onScroll: null
        },

        /**
         * Widget creation
         * @private
         */
        _create: function() {
            this.isInitialized = false;
            this.currentBreakpoint = null;
            this.lastScrollTop = 0;
            this.scrollDirection = null;
            this.hoverTimers = {};
            this.activeMenus = [];
            this.cache = {};

            this._loadFromCache();
            this._detectDevice();
            this._bind();
            this._initializeMenu();

            this.isInitialized = true;
            this._trigger('init', null, { widget: this });
        },

        /**
         * Load menu data from cache
         * @private
         */
        _loadFromCache: function() {
            if (!this.options.cacheEnabled) {
                return;
            }

            try {
                var cacheKey = this.options.cacheKey + this.options.storeId;
                var cached = localStorage.getItem(cacheKey);

                if (cached) {
                    var data = JSON.parse(cached);
                    var now = Math.floor(Date.now() / 1000);

                    if (data.expires > now) {
                        this.cache = data;
                        return true;
                    }
                }
            } catch (e) {
            }

            return false;
        },

        /**
         * Save menu data to cache
         * @private
         */
        _saveToCache: function(data) {
            if (!this.options.cacheEnabled) {
                return;
            }

            try {
                var cacheKey = this.options.cacheKey + this.options.storeId;
                var now = Math.floor(Date.now() / 1000);

                var cacheData = {
                    data: data,
                    created: now,
                    expires: now + this.options.cacheLifetime
                };

                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
            } catch (e) {
            }
        },

        /**
         * Detect device type and set breakpoint
         * @private
         */
        _detectDevice: function() {
            var width = $(window).width();
            var wasDesktop = this.options.isDesktop;

            this.options.isDesktop = width >= this.options.mobileBreakpoint;
            this.options.isMobile = !this.options.isDesktop;

            if (wasDesktop !== this.options.isDesktop) {
                this._onBreakpointChange();
            }

            this.currentBreakpoint = this.options.isDesktop ? 'desktop' : 'mobile';
        },

        /**
         * Handle breakpoint change
         * @private
         */
        _onBreakpointChange: function() {
            // Close all open menus
            this._closeAllMenus();

            // Re-initialize menu for new breakpoint
            this._initializeMenu();

        },

        /**
         * Bind event handlers
         * @private
         */
        _bind: function() {
            var self = this;

            // Window resize (debounced)
            var resizeTimer;
            $(window).on('resize.panthMegaMenu', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    self._detectDevice();
                    self._trigger('resize', null, {
                        width: $(window).width(),
                        breakpoint: self.currentBreakpoint
                    });
                }, 100);
            });

            // Window scroll (throttled)
            var scrollTimer;
            var scrollTicking = false;
            $(window).on('scroll.panthMegaMenu', function() {
                if (!scrollTicking) {
                    window.requestAnimationFrame(function() {
                        self._onScroll();
                        scrollTicking = false;
                    });
                    scrollTicking = true;
                }
            });

            // Document click for outside click detection
            if (this.options.closeOnOutsideClick) {
                $(document).on('click.panthMegaMenu', function(e) {
                    if (!$(e.target).closest(self.element).length) {
                        self._closeAllMenus();
                    }
                });
            }

            // ESC key to close menus
            if (this.options.closeOnEscape) {
                $(document).on('keydown.panthMegaMenu', function(e) {
                    if (e.keyCode === 27) { // ESC
                        self._closeAllMenus();
                    }
                });
            }

            // Keyboard navigation
            if (this.options.keyboardNavigation) {
                this._bindKeyboardNavigation();
            }
        },

        /**
         * Bind keyboard navigation
         * @private
         */
        _bindKeyboardNavigation: function() {
            var self = this;

            this.element.on('keydown.panthMegaMenu', '[role="menuitem"]', function(e) {
                var $item = $(this);
                var $parent = $item.parent();
                var handled = false;

                switch(e.keyCode) {
                    case 37: // Left arrow
                        if (self.options.rtlEnabled) {
                            self._navigateRight($item);
                        } else {
                            self._navigateLeft($item);
                        }
                        handled = true;
                        break;

                    case 39: // Right arrow
                        if (self.options.rtlEnabled) {
                            self._navigateLeft($item);
                        } else {
                            self._navigateRight($item);
                        }
                        handled = true;
                        break;

                    case 38: // Up arrow
                        self._navigateUp($item);
                        handled = true;
                        break;

                    case 40: // Down arrow
                        self._navigateDown($item);
                        handled = true;
                        break;

                    case 13: // Enter
                    case 32: // Space
                        if ($item.next(self.options.submenuSelector).length) {
                            self._toggleSubmenu($parent);
                            handled = true;
                        }
                        break;

                    case 27: // Escape
                        self._closeParentMenu($item);
                        handled = true;
                        break;
                }

                if (handled) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        },

        /**
         * Navigate left in menu
         * @private
         */
        _navigateLeft: function($item) {
            var $prev = $item.parent().prev().find('> a, > [role="menuitem"]').first();
            if ($prev.length) {
                $prev.focus();
            }
        },

        /**
         * Navigate right in menu
         * @private
         */
        _navigateRight: function($item) {
            var $next = $item.parent().next().find('> a, > [role="menuitem"]').first();
            if ($next.length) {
                $next.focus();
            }
        },

        /**
         * Navigate up in menu
         * @private
         */
        _navigateUp: function($item) {
            var $parent = $item.parent();

            if ($parent.parent().hasClass(this.options.submenuSelector.replace('.', ''))) {
                var $prev = $parent.prev().find('> a, > [role="menuitem"]').first();
                if ($prev.length) {
                    $prev.focus();
                } else {
                    // Focus parent menu item
                    $parent.parent().prev('a, [role="menuitem"]').focus();
                }
            }
        },

        /**
         * Navigate down in menu
         * @private
         */
        _navigateDown: function($item) {
            var $submenu = $item.next(this.options.submenuSelector);

            if ($submenu.length) {
                // Open submenu and focus first item
                this._openSubmenu($item.parent());
                $submenu.find('> li:first > a, > li:first > [role="menuitem"]').first().focus();
            } else {
                // Navigate to next sibling
                var $next = $item.parent().next().find('> a, > [role="menuitem"]').first();
                if ($next.length) {
                    $next.focus();
                }
            }
        },

        /**
         * Close parent menu
         * @private
         */
        _closeParentMenu: function($item) {
            var $parentMenu = $item.closest(this.options.submenuSelector);

            if ($parentMenu.length) {
                var $parentItem = $parentMenu.prev('a, [role="menuitem"]');
                this._closeSubmenu($parentMenu.parent());
                $parentItem.focus();
            } else {
                this._closeAllMenus();
            }
        },

        /**
         * Initialize menu based on current breakpoint
         * @private
         */
        _initializeMenu: function() {
            if (this.options.isDesktop) {
                this._initializeDesktopMenu();
            } else {
                this._initializeMobileMenu();
            }

            if (this.options.stickyEnabled) {
                this._initializeStickyHeader();
            }

            this._trigger('ready', null, {
                breakpoint: this.currentBreakpoint,
                menuData: this.options.menuData
            });
        },

        /**
         * Initialize desktop menu
         * @private
         */
        _initializeDesktopMenu: function() {
            var self = this;
            var $desktop = this.element.find(this.options.desktopMenuSelector);

            if (!$desktop.length) {
                return;
            }

            // Show desktop menu
            $desktop.show();

            // Initialize Magento menu widget
            var $menu = $desktop.find('.nav-primary');
            if ($menu.length && !$menu.data('mage-menu')) {
                $menu.menu({
                    'responsive': true,
                    'expanded': false,
                    'showDelay': this.options.hoverDelay,
                    'hideDelay': 300
                });
            }

            // Add hover intent
            if (this.options.hoverIntent) {
                $desktop.find(this.options.menuItemSelector + '.parent > a').hover(
                    function() {
                        var $link = $(this);
                        var itemId = $link.closest('li').data('item-id');

                        self.hoverTimers[itemId] = setTimeout(function() {
                            self._openSubmenu($link.parent());
                        }, self.options.hoverDelay);
                    },
                    function() {
                        var itemId = $(this).closest('li').data('item-id');
                        clearTimeout(self.hoverTimers[itemId]);
                    }
                );
            }

            // Track menu item clicks
            if (this.options.trackClicks) {
                $desktop.on('click', 'a', function(e) {
                    var $link = $(this);
                    self._trackClick($link);
                });
            }

            // Add smart positioning for nested dropdowns
            this._initSmartPositioning($desktop);

        },

        /**
         * Initialize smart positioning for dropdowns
         * Detects viewport edges and positions dropdowns left/right accordingly
         * @private
         */
        _initSmartPositioning: function($container) {
            var self = this;

            // Handle all menu items with dropdowns
            $container.on('mouseenter.positioning', '.pmenu-item', function() {
                var $item = $(this);
                var $dropdown = $item.children('[class*="pmenu-dropdown"]').first();

                if (!$dropdown.length) {
                    return;
                }

                // Small delay to ensure dropdown is visible for measurement
                setTimeout(function() {
                    if (!$dropdown.is(':visible')) {
                        return;
                    }

                    var dropdownRect = $dropdown[0].getBoundingClientRect();
                    var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                    var spaceOnRight = viewportWidth - dropdownRect.right;
                    var spaceOnLeft = dropdownRect.left;

                    // If dropdown goes off right edge and there's more space on left
                    if (spaceOnRight < 0 && spaceOnLeft > Math.abs(spaceOnRight)) {
                        $item.addClass('hover-right');
                    } else {
                        $item.removeClass('hover-right');
                    }
                }, 10);
            });

            // Clean up class on mouse leave
            $container.on('mouseleave.positioning', '.pmenu-item', function() {
                $(this).removeClass('hover-right');
            });
        },

        /**
         * Initialize mobile menu
         * @private
         */
        _initializeMobileMenu: function() {
            var self = this;
            var $mobile = this.element.find(this.options.mobileMenuSelector);

            if (!$mobile.length) {
                return;
            }

            // Show mobile menu
            $mobile.show();

            // Mobile toggle button
            var $toggle = $(this.options.mobileToggleSelector);
            var $nav = $mobile.find('.mobile-navigation');
            var $overlay = $mobile.find('.mobile-menu-overlay');

            // Toggle button click
            $toggle.on('click.panthMegaMenu', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if ($nav.hasClass(self.options.activeClass)) {
                    self._closeMobileMenu();
                } else {
                    self._openMobileMenu();
                }
            });

            // Close button click
            $(this.options.mobileCloseSelector).on('click.panthMegaMenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self._closeMobileMenu();
            });

            // Overlay click
            $overlay.on('click.panthMegaMenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self._closeMobileMenu();
            });

            // Submenu toggles
            $mobile.on('click.panthMegaMenu', '.submenu-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $toggle = $(this);
                var $item = $toggle.closest('.mobile-nav-item');
                self._toggleMobileSubmenu($item);
            });

        },

        /**
         * Initialize sticky header
         * @private
         */
        _initializeStickyHeader: function() {
            var self = this;
            this.stickyElement = this.element.find('.panth-megamenu-sticky-container');

            if (!this.stickyElement.length) {
                return;
            }

            // Initial sticky check
            this._checkStickyPosition();

        },

        /**
         * Handle scroll events
         * @private
         */
        _onScroll: function() {
            var scrollTop = $(window).scrollTop();

            this.scrollDirection = scrollTop > this.lastScrollTop ? 'down' : 'up';
            this.lastScrollTop = scrollTop;

            if (this.options.stickyEnabled) {
                this._checkStickyPosition();
            }

            this._trigger('scroll', null, {
                scrollTop: scrollTop,
                direction: this.scrollDirection
            });
        },

        /**
         * Check and update sticky position
         * @private
         */
        _checkStickyPosition: function() {
            if (!this.stickyElement || !this.stickyElement.length) {
                return;
            }

            var scrollTop = $(window).scrollTop();
            var isSticky = this.stickyElement.hasClass(this.options.stickyClass);

            if (scrollTop >= this.options.stickyOffset) {
                if (!isSticky) {
                    this.stickyElement.addClass(this.options.stickyClass + ' ' + this.options.activeClass);
                    $('body').addClass('has-sticky-header');
                }
            } else {
                if (isSticky) {
                    this.stickyElement.removeClass(this.options.stickyClass + ' ' + this.options.activeClass);
                    $('body').removeClass('has-sticky-header');
                }
            }
        },

        /**
         * Open mobile menu
         * @private
         */
        _openMobileMenu: function() {
            var $nav = this.element.find('.mobile-navigation');
            var $overlay = this.element.find('.mobile-menu-overlay');
            var $toggle = $(this.options.mobileToggleSelector);

            $nav.addClass(this.options.activeClass).attr('aria-hidden', 'false');
            $overlay.addClass(this.options.activeClass);
            $toggle.attr('aria-expanded', 'true');
            $('body').addClass('mobile-menu-open').css('overflow', 'hidden');

            // Focus first menu item
            $nav.find('a, button').first().focus();

            this._trigger('menuOpen', null, { type: 'mobile' });
        },

        /**
         * Close mobile menu
         * @private
         */
        _closeMobileMenu: function() {
            var $nav = this.element.find('.mobile-navigation');
            var $overlay = this.element.find('.mobile-menu-overlay');
            var $toggle = $(this.options.mobileToggleSelector);

            $nav.removeClass(this.options.activeClass).attr('aria-hidden', 'true');
            $overlay.removeClass(this.options.activeClass);
            $toggle.attr('aria-expanded', 'false');
            $('body').removeClass('mobile-menu-open').css('overflow', '');

            // Return focus to toggle
            $toggle.focus();

            this._trigger('menuClose', null, { type: 'mobile' });
        },

        /**
         * Toggle mobile submenu
         * @private
         */
        _toggleMobileSubmenu: function($item) {
            var $submenu = $item.find('> .submenu');
            var $toggle = $item.find('> .mobile-nav-link .submenu-toggle');
            var isActive = $item.hasClass(this.options.activeClass);

            if (isActive) {
                // Close
                $submenu.slideUp(this.options.animationSpeed, function() {
                    $item.removeClass(this.options.activeClass);
                    $toggle.attr('aria-expanded', 'false');
                }.bind(this));
            } else {
                // Close siblings
                $item.siblings('.' + this.options.activeClass).each(function() {
                    var $sibling = $(this);
                    $sibling.find('> .submenu').slideUp(this.options.animationSpeed, function() {
                        $sibling.removeClass(this.options.activeClass);
                        $sibling.find('> .mobile-nav-link .submenu-toggle').attr('aria-expanded', 'false');
                    }.bind(this));
                }.bind(this));

                // Open
                $submenu.slideDown(this.options.animationSpeed, function() {
                    $item.addClass(this.options.activeClass);
                    $toggle.attr('aria-expanded', 'true');
                }.bind(this));
            }
        },

        /**
         * Open submenu
         * @private
         */
        _openSubmenu: function($item) {
            if (!$item.hasClass(this.options.activeClass)) {
                $item.addClass(this.options.activeClass);
                $item.find('> a').attr('aria-expanded', 'true');

                this.activeMenus.push($item);

                this._trigger('menuOpen', null, {
                    type: 'submenu',
                    item: $item
                });
            }
        },

        /**
         * Close submenu
         * @private
         */
        _closeSubmenu: function($item) {
            if ($item.hasClass(this.options.activeClass)) {
                $item.removeClass(this.options.activeClass);
                $item.find('> a').attr('aria-expanded', 'false');

                var index = this.activeMenus.indexOf($item);
                if (index > -1) {
                    this.activeMenus.splice(index, 1);
                }

                this._trigger('menuClose', null, {
                    type: 'submenu',
                    item: $item
                });
            }
        },

        /**
         * Toggle submenu
         * @private
         */
        _toggleSubmenu: function($item) {
            if ($item.hasClass(this.options.activeClass)) {
                this._closeSubmenu($item);
            } else {
                this._openSubmenu($item);
            }
        },

        /**
         * Close all open menus
         * @private
         */
        _closeAllMenus: function() {
            var self = this;

            // Close desktop submenus
            this.element.find('.' + this.options.activeClass).each(function() {
                self._closeSubmenu($(this));
            });

            // Close mobile menu
            if (this.options.isMobile) {
                this._closeMobileMenu();
            }

            this.activeMenus = [];
        },

        /**
         * Track menu item click
         * @private
         */
        _trackClick: function($link) {
            var itemId = $link.closest('li').data('item-id');
            var label = $link.find('.menu-label').text() || $link.text();
            var url = $link.attr('href');

            this._trigger('itemClick', null, {
                itemId: itemId,
                label: label,
                url: url,
                timestamp: Date.now()
            });

            // Send to analytics (if available)
            if (window.dataLayer) {
                window.dataLayer.push({
                    'event': 'menuItemClick',
                    'menuItemId': itemId,
                    'menuItemLabel': label,
                    'menuItemUrl': url
                });
            }

        },

        /**
         * Public: Refresh menu
         */
        refresh: function() {
            this._closeAllMenus();
            this._detectDevice();
            this._initializeMenu();
        },

        /**
         * Public: Open menu item by ID
         */
        openItem: function(itemId) {
            var $item = this.element.find('[data-item-id="' + itemId + '"]');
            if ($item.length) {
                this._openSubmenu($item);
            }
        },

        /**
         * Public: Close menu item by ID
         */
        closeItem: function(itemId) {
            var $item = this.element.find('[data-item-id="' + itemId + '"]');
            if ($item.length) {
                this._closeSubmenu($item);
            }
        },

        /**
         * Public: Get active menus
         */
        getActiveMenus: function() {
            return this.activeMenus;
        },

        /**
         * Public: Set option
         */
        setOption: function(key, value) {
            this.options[key] = value;
            this.refresh();
        },

        /**
         * Widget destruction
         * @private
         */
        _destroy: function() {
            // Unbind events
            $(window).off('.panthMegaMenu');
            $(document).off('.panthMegaMenu');
            this.element.off('.panthMegaMenu');

            // Remove classes
            this.element.find('.' + this.options.activeClass).removeClass(this.options.activeClass);
            $('body').removeClass('mobile-menu-open has-sticky-header');

            // Clean up
            this.activeMenus = [];
            this.hoverTimers = {};

        }
    });

    return $.panth.megaMenu;
});
