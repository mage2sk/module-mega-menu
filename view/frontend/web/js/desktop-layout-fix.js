/**
 * Panth MegaMenu - Desktop 2-Column Layout Fix
 *
 * Forces 2-column flexbox layout for product dropdowns on desktop
 * This script runs independently and doesn't rely on theme's widget
 */
define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        var $menu = $(element);
        var isDesktop = $(window).width() >= 992;

        if (!isDesktop) {
            return; // Only run on desktop
        }

        /**
         * Force flexbox layout for 2-column product grids
         */
        function forceFlexboxLayout() {
            // Find all Level 1 dropdowns (containing products)
            var $level1Dropdowns = $menu.find('.pmenu-dropdown-l1, .pmenu-item-l1 > .pmenu-dropdown, .pmenu-item-l1 > ul.submenu');

            $level1Dropdowns.each(function() {
                var $dropdown = $(this);
                var $parent = $dropdown.parent();

                // Check if parent has hover class or is being hovered
                if ($parent.hasClass('hover') || $parent.is(':hover') || $dropdown.is(':visible')) {
                    // Force flexbox display for 2-column layout
                    $dropdown.css({
                        'display': 'flex !important',
                        'flex-wrap': 'wrap',
                        'flex-direction': 'row',
                        'gap': '0',
                        'align-items': 'flex-start',
                        'justify-content': 'flex-start'
                    });

                    // Ensure child items are properly sized
                    $dropdown.children('.pmenu-item-l2.has-image, li.pmenu-item-l2.has-image').each(function() {
                        $(this).css({
                            'width': 'calc(50% - 6px)',
                            'flex': '0 0 calc(50% - 6px)',
                            'display': 'block'
                        });
                    });
                }
            });
        }

        /**
         * Start monitoring - check every 50ms for better responsiveness
         */
        var monitorInterval = setInterval(function() {
            // Only run on desktop
            if ($(window).width() >= 992) {
                forceFlexboxLayout();
            } else {
                clearInterval(monitorInterval);
            }
        }, 50);

        /**
         * Also apply on hover for immediate response
         */
        $menu.find('.pmenu-item-l0, .pmenu-item-l1').on('mouseenter', function() {
            if ($(window).width() >= 992) {
                setTimeout(forceFlexboxLayout, 10);
            }
        });

        /**
         * Handle window resize
         */
        var resizeTimer;
        $(window).on('resize.pmenu-desktop', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                isDesktop = $(window).width() >= 992;
                if (!isDesktop && monitorInterval) {
                    clearInterval(monitorInterval);
                    monitorInterval = null;
                } else if (isDesktop && !monitorInterval) {
                    monitorInterval = setInterval(forceFlexboxLayout, 50);
                }
            }, 250);
        });

        // Initial run
        setTimeout(forceFlexboxLayout, 100);
    };
});
