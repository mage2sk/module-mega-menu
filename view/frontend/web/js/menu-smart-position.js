/**
 * Panth MegaMenu - Smart Submenu Positioning
 * Automatically positions submenus to stay within viewport
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */

define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    return function(config, element) {
        var $menu = $(element);

        /**
         * Smart submenu positioning function
         * Calculates optimal position to keep submenu within viewport
         */
        function positionSubmenu($submenu, $parentItem) {
            if (!$submenu.length || !$parentItem.length) {
                return;
            }

            var viewportWidth = $(window).width();
            var parentOffset = $parentItem.offset();
            var submenuWidth = $submenu.outerWidth();
            var parentLeft = parentOffset.left;
            var parentWidth = $parentItem.outerWidth();

            // Calculate if submenu overflows right edge
            var submenuRight = parentLeft + parentWidth + submenuWidth;
            var overflowsRight = submenuRight > viewportWidth - 20;

            // Apply hover-right class if overflows
            if (overflowsRight) {
                $parentItem.addClass('hover-right');
            } else {
                $parentItem.removeClass('hover-right');
            }
        }

        /**
         * Position all visible dropdowns
         */
        function positionAllDropdowns() {
            // Position level 1 items (categories)
            $menu.find('.pmenu-item-l1:hover').each(function() {
                var $item = $(this);
                var $dropdown = $item.find('> .pmenu-dropdown-l1');
                if ($dropdown.length && $dropdown.is(':visible')) {
                    positionSubmenu($dropdown, $item);
                }
            });

            // Position level 2 items (products with nested menus)
            $menu.find('.pmenu-item-l2:hover').each(function() {
                var $item = $(this);
                var $dropdown = $item.find('> .pmenu-dropdown-l2');
                if ($dropdown.length && $dropdown.is(':visible')) {
                    positionSubmenu($dropdown, $item);
                }
            });

            // Position level 3+ items
            $menu.find('.pmenu-item-l3:hover, .pmenu-item-l4:hover, .pmenu-item-l5:hover').each(function() {
                var $item = $(this);
                var $dropdown = $item.children('.pmenu-dropdown');
                if ($dropdown.length && $dropdown.is(':visible')) {
                    positionSubmenu($dropdown, $item);
                }
            });
        }

        /**
         * Initialize smart positioning
         */
        function init() {
            // Position level 1 dropdowns (categories -> products)
            $menu.on('mouseenter', '.pmenu-item-l1', function() {
                var $item = $(this);
                var $dropdown = $item.find('> .pmenu-dropdown-l1');

                if ($dropdown.length) {
                    positionSubmenu($dropdown, $item);
                }
            });

            // Position level 2 dropdowns (products -> sub-items)
            $menu.on('mouseenter', '.pmenu-item-l2', function() {
                var $item = $(this);
                var $dropdown = $item.find('> .pmenu-dropdown-l2');

                if ($dropdown.length) {
                    positionSubmenu($dropdown, $item);
                }
            });

            // Position level 3+ dropdowns
            $menu.on('mouseenter', '.pmenu-item-l3, .pmenu-item-l4, .pmenu-item-l5', function() {
                var $item = $(this);
                var $dropdown = $item.children('.pmenu-dropdown');

                if ($dropdown.length) {
                    positionSubmenu($dropdown, $item);
                }
            });

            // Reposition on window resize (debounced)
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    positionAllDropdowns();
                }, 250);
            });

            // Reposition on scroll (for fixed/sticky headers)
            var scrollTimer;
            $(window).on('scroll', function() {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    positionAllDropdowns();
                }, 100);
            });

            // Initial positioning
            positionAllDropdowns();
        }

        // Initialize
        init();

        // Return public API
        return {
            positionSubmenu: positionSubmenu,
            positionAllDropdowns: positionAllDropdowns
        };
    };
});
