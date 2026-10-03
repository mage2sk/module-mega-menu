/**
 * Panth MegaMenu - Magento Menu Widget Mixin
 *
 * Extends Magento's default menu widget with custom functionality
 * for mega menu support and enhanced features.
 *
 * @category    Panth
 * @package     Panth_MegaMenu
 * @copyright   Copyright (c) 2025 Panth InfoTech
 * @license     Proprietary
 */

define([
    'jquery'
], function($) {
    'use strict';

    /**
     * Menu widget mixin
     *
     * Extends mage/menu widget functionality
     */
    return function(widget) {
        $.widget('mage.menu', widget, {
            options: {
                // Extended options for Panth MegaMenu
                megaMenuEnabled: true,
                megaMenuClass: 'mega-menu',
                megaMenuItemClass: 'mega-menu-item',
                hoverIntentDelay: 300,
                closeOnClickOutside: true,
                closeOnEscape: true,
                animationDuration: 300,
                rtlSupport: false,
                touchSupport: true,
                keyboardNavigation: true,
                lazyLoadSubmenus: false,
                trackInteractions: false
            },

            /**
             * Widget creation - extended
             * @private
             */
            _create: function() {
                this._superApply(arguments);

                // Initialize Panth MegaMenu enhancements
                this._initPanthEnhancements();
            },

            /**
             * Initialize Panth MegaMenu enhancements
             * @private
             */
            _initPanthEnhancements: function() {
                var self = this;

                // Detect mega menu items
                if (this.options.megaMenuEnabled) {
                    this._detectMegaMenus();
                }

                // Add touch support
                if (this.options.touchSupport && this._isTouchDevice()) {
                    this._addTouchSupport();
                }

                // Add enhanced keyboard navigation
                if (this.options.keyboardNavigation) {
                    this._enhanceKeyboardNavigation();
                }

                // Close on outside click
                if (this.options.closeOnClickOutside) {
                    $(document).on('click.panthMenu', function(e) {
                        if (!$(e.target).closest(self.element).length) {
                            self.collapseAll();
                        }
                    });
                }

                // Close on ESC key
                if (this.options.closeOnEscape) {
                    $(document).on('keydown.panthMenu', function(e) {
                        if (e.keyCode === 27) { // ESC
                            self.collapseAll();
                        }
                    });
                }

            },

            /**
             * Detect and mark mega menu items
             * @private
             */
            _detectMegaMenus: function() {
                var self = this;

                this.element.find('.' + this.options.megaMenuItemClass).each(function() {
                    var $item = $(this);
                    var $submenu = $item.find('> .' + self.options.submenuClass);

                    if ($submenu.length) {
                        $submenu.addClass(self.options.megaMenuClass);

                        // Adjust positioning for mega menus
                        $item.on('mouseenter.panthMenu', function() {
                            self._positionMegaMenu($submenu);
                        });
                    }
                });
            },

            /**
             * Position mega menu
             * @private
             */
            _positionMegaMenu: function($submenu) {
                var windowWidth = $(window).width();
                var menuOffset = this.element.offset().left;
                var menuWidth = this.element.outerWidth();

                // Center mega menu relative to main menu container
                var leftPosition = -menuOffset;

                $submenu.css({
                    left: leftPosition + 'px',
                    right: 'auto',
                    width: windowWidth + 'px',
                    maxWidth: '1280px',
                    marginLeft: 'auto',
                    marginRight: 'auto'
                });

                // RTL support
                if (this.options.rtlSupport) {
                    $submenu.css({
                        left: 'auto',
                        right: leftPosition + 'px'
                    });
                }
            },

            /**
             * Check if touch device
             * @private
             */
            _isTouchDevice: function() {
                return 'ontouchstart' in window ||
                    navigator.maxTouchPoints > 0 ||
                    navigator.msMaxTouchPoints > 0;
            },

            /**
             * Add touch support
             * @private
             */
            _addTouchSupport: function() {
                var self = this;

                this.element.find('.parent > a').on('touchstart.panthMenu', function(e) {
                    var $link = $(this);
                    var $item = $link.parent();
                    var $submenu = $item.find('> .' + self.options.submenuClass);

                    if ($submenu.length && !$item.hasClass(self.options.activeClass)) {
                        e.preventDefault();
                        e.stopPropagation();

                        // Close all other submenus
                        self.collapseAll();

                        // Open this submenu
                        self.expand($item);

                        // Track interaction
                        if (self.options.trackInteractions) {
                            self._trackInteraction('touch', $item);
                        }
                    }
                });
            },

            /**
             * Enhance keyboard navigation
             * @private
             */
            _enhanceKeyboardNavigation: function() {
                var self = this;

                this.element.on('keydown.panthMenu', 'a', function(e) {
                    var $link = $(this);
                    var $item = $link.parent();
                    var handled = false;

                    switch(e.keyCode) {
                        case 36: // Home
                            e.preventDefault();
                            self.element.find('> li:first > a').focus();
                            handled = true;
                            break;

                        case 35: // End
                            e.preventDefault();
                            self.element.find('> li:last > a').focus();
                            handled = true;
                            break;

                        case 9: // Tab
                            // Allow natural tab behavior but close menus when tabbing out
                            if (!$(e.target).closest(self.element).length) {
                                self.collapseAll();
                            }
                            break;
                    }

                    if (handled) {
                        e.stopPropagation();
                    }
                });
            },

            /**
             * Enhanced expand method
             */
            expand: function($item) {
                var self = this;

                // Call original expand
                this._superApply(arguments);

                // Add custom animation
                var $submenu = $item.find('> .' + this.options.submenuClass);
                if ($submenu.length) {
                    $submenu.hide().fadeIn(this.options.animationDuration);
                }

                // Lazy load submenu content if needed
                if (this.options.lazyLoadSubmenus && $item.data('lazy-load')) {
                    this._loadSubmenuContent($item);
                }

                // Track interaction
                if (this.options.trackInteractions) {
                    this._trackInteraction('expand', $item);
                }
            },

            /**
             * Enhanced collapse method
             */
            collapse: function($item) {
                var self = this;
                var $submenu = $item.find('> .' + this.options.submenuClass);

                // Fade out submenu
                if ($submenu.length) {
                    $submenu.fadeOut(this.options.animationDuration, function() {
                        self._superApply(arguments);
                    });
                } else {
                    this._superApply(arguments);
                }

                // Track interaction
                if (this.options.trackInteractions) {
                    this._trackInteraction('collapse', $item);
                }
            },

            /**
             * Load submenu content via AJAX
             * @private
             */
            _loadSubmenuContent: function($item) {
                var itemId = $item.data('item-id');
                var $submenu = $item.find('> .' + this.options.submenuClass);

                if (!itemId || $item.data('loaded')) {
                    return;
                }

                // Show loading indicator
                $submenu.addClass('loading');

                // Load content
                $.ajax({
                    url: '/rest/V1/panth-megamenu/menu/' + itemId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.content) {
                            $submenu.html(response.content);
                            $item.data('loaded', true);
                        }
                    },
                    error: function(xhr, status, error) {
                    },
                    complete: function() {
                        $submenu.removeClass('loading');
                    }
                });
            },

            /**
             * Track menu interaction
             * @private
             */
            _trackInteraction: function(action, $item) {
                var itemId = $item.data('item-id');
                var label = $item.find('> a').text().trim();

                // Send to analytics
                if (window.dataLayer) {
                    window.dataLayer.push({
                        'event': 'menuInteraction',
                        'menuAction': action,
                        'menuItemId': itemId,
                        'menuItemLabel': label,
                        'timestamp': Date.now()
                    });
                }

                    action: action,
                    itemId: itemId,
                    label: label
                });
            },

            /**
             * Widget destruction - extended
             * @private
             */
            _destroy: function() {
                // Clean up Panth enhancements
                $(document).off('.panthMenu');
                this.element.off('.panthMenu');

                // Call original destroy
                this._superApply(arguments);
            }
        });

        return $.mage.menu;
    };
});
