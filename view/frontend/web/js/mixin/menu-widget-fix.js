/**
 * Panth MegaMenu - Mixin to fix theme's navigationMenu widget
 *
 * This mixin extends the base menu widget to override _applySubmenuStyles
 * AFTER theme's widget has loaded and extended it
 */
define([
    'jquery'
], function($) {
    'use strict';

    return function(widget) {
        // Wait for theme's widget extensions to load
        // Theme extends $.mage.navigationMenu multiple times in navigation-menu.js
        $(document).ready(function() {
            setTimeout(function() {
                // Check if navigationMenu widget exists (added by theme)
                if ($.mage && $.mage.navigationMenu && $.mage.navigationMenu.prototype) {
                    var originalApplySubmenuStyles = $.mage.navigationMenu.prototype._applySubmenuStyles;

                    // Override the problematic method
                    $.mage.navigationMenu.prototype._applySubmenuStyles = function() {
                        var $element = $(this.element);

                        // Check if this is a Panth MegaMenu
                        if ($element.hasClass('pmenu-wrapper') ||
                            $element.closest('.pmenu-wrapper').length ||
                            ($element.attr('id') && $element.attr('id').indexOf('panth-megamenu') === 0)) {

                            var windowWidth = $(window).width();
                            if (windowWidth >= 992) {
                                // Desktop: Force visibility for Panth MegaMenu main element
                                $element.css({
                                    'display': 'block',
                                    'height': 'auto',
                                    'overflow': 'visible'
                                });

                                // Remove ALL inline styles from dropdown elements at all levels
                                // This allows our CSS hover styles to work
                                var $menuWrapper = $element.hasClass('pmenu-wrapper') ? $element : $element.closest('.pmenu-wrapper');
                                $menuWrapper.find('.pmenu-dropdown, .submenu').removeAttr('style');

                                // Don't apply theme's default styles
                                return;
                            }
                        }

                        // For other menus or mobile, call original method
                        if (originalApplySubmenuStyles) {
                            originalApplySubmenuStyles.apply(this, arguments);
                        }
                    };

                    // Also override _open method to ensure dropdowns can open
                    if ($.mage.navigationMenu.prototype._open) {
                        var originalOpen = $.mage.navigationMenu.prototype._open;
                        $.mage.navigationMenu.prototype._open = function(submenu) {
                            var $element = $(this.element);

                            // Check if this is a Panth MegaMenu on desktop
                            if (($element.hasClass('pmenu-wrapper') || $element.closest('.pmenu-wrapper').length) &&
                                $(window).width() >= 992) {
                                // Don't interfere with our CSS hover behavior on desktop
                                return;
                            }

                            // For other menus or mobile, call original method
                            if (originalOpen) {
                                originalOpen.apply(this, arguments);
                            }
                        };
                    }

                    // Continuously monitor and force flexbox layout for Panth MegaMenu dropdowns on desktop ONLY
                    // Do NOT interfere with mobile navigation - theme's widget handles mobile slide navigation
                    var monitorInterval;

                    function startDesktopMonitoring() {
                        if ($(window).width() >= 992 && !monitorInterval) {
                            monitorInterval = setInterval(function() {
                                // Only run on desktop
                                if ($(window).width() >= 992) {
                                    $('.pmenu-wrapper').each(function() {
                                        var $wrapper = $(this);

                                        // Force main wrapper to be visible
                                        $wrapper.css({
                                            'display': 'block',
                                            'height': 'auto',
                                            'overflow': 'visible'
                                        });

                                        // For level 2 dropdowns (product grids), force flexbox for 2-column layout
                                        $wrapper.find('.pmenu-dropdown-l1, .pmenu-dropdown-l0 > .pmenu-item > .pmenu-dropdown').each(function() {
                                            var $dropdown = $(this);
                                            var $parent = $dropdown.parent();

                                            // If parent is hovered, show dropdown with flexbox
                                            if ($parent.is(':hover')) {
                                                $dropdown.css({
                                                    'display': 'flex',
                                                    'flex-wrap': 'wrap',
                                                    'gap': '0',
                                                    'opacity': '1',
                                                    'visibility': 'visible'
                                                });
                                            } else {
                                                // Hidden state - remove inline styles to let CSS handle it
                                                $dropdown.removeAttr('style');
                                            }
                                        });

                                        // For all other dropdowns, remove theme's inline styles
                                        $wrapper.find('.pmenu-dropdown:not(.pmenu-dropdown-l1), .submenu:not(.pmenu-dropdown-l1)').each(function() {
                                            var $dropdown = $(this);
                                            if ($dropdown.attr('style')) {
                                                $dropdown.removeAttr('style');
                                            }
                                        });
                                    });
                                }
                            }, 100); // Check every 100ms
                        }
                    }

                    function stopDesktopMonitoring() {
                        if (monitorInterval) {
                            clearInterval(monitorInterval);
                            monitorInterval = null;
                        }
                    }

                    // Start monitoring on desktop
                    startDesktopMonitoring();

                    // Handle window resize - stop monitoring on mobile, resume on desktop
                    $(window).on('resize', function() {
                        if ($(window).width() < 992) {
                            stopDesktopMonitoring();
                        } else {
                            startDesktopMonitoring();
                        }
                    });
                }
            }, 100);
        });

        // Return the original widget unchanged
        return widget;
    };
});
