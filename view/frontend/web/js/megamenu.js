/**
 * Panth MegaMenu - Simple JavaScript with Smart Positioning
 */
define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        var $menu = $(element);
        var $window = $(window);
        var windowWidth = $window.width();

        /**
         * Smart Dropdown Positioning - Real-time on hover
         * ONLY left/right positioning - NEVER bottom
         */
        function smartPosition($dropdown, isTopLevel) {
            if (windowWidth < 992) return;

            var dropdownRect = $dropdown[0].getBoundingClientRect();
            var viewportWidth = window.innerWidth;

            if (isTopLevel) {
                // Top-level dropdown
                var $item = $dropdown.parent();
                $item.removeClass('hover-right');

                // Check if goes off right edge
                if (dropdownRect.right > viewportWidth - 10) {
                    $item.addClass('hover-right');
                }
            } else {
                // Nested dropdown - ONLY left/right, never bottom
                var $item = $dropdown.parent();
                $item.removeClass('hover-right');

                // Check if goes off right edge
                if (dropdownRect.right > viewportWidth - 10) {
                    $item.addClass('hover-right');
                }
            }
        }

        /**
         * Mobile Accordion Navigation
         * Manual implementation for reliable accordion behavior
         */
        if (windowWidth < 992) {
            // Function to completely disable Magento menu widget and theme interference
            // This is called ONCE during setup, not repeatedly
            function disableMagentoMenuWidget($targetNav) {
                // Destroy menu widget if it exists
                if ($targetNav.data('mage-menu')) {
                    try {
                        $targetNav.menu('destroy');
                    } catch(e) {
                        // Menu widget destruction failed - ignore
                    }
                }

                // Destroy navigationMenu widget if it exists (theme-specific)
                if ($targetNav.data('mage-navigationMenu')) {
                    try {
                        $targetNav.navigationMenu('destroy');
                    } catch(e) {
                        // NavigationMenu widget destruction failed - ignore
                    }
                }

                // Remove widget data
                $targetNav.removeData('mage-menu');
                $targetNav.removeData('mage-navigationMenu');
                $targetNav.removeData('menu');
                $targetNav.removeData('navigationMenu');
            }

            // Set first tab as active by default and handle tab clicks
            function initializeTabs() {
                var $navSections = $('.nav-sections');
                var $allTabItems = $navSections.find('.nav-sections-item');
                var $tabTitles = $navSections.find('.nav-sections-item-title');

                // Remove all active classes first
                $allTabItems.removeClass('active _active');

                // Activate first tab (Menu) by default
                $allTabItems.first().addClass('active');

                // Handle clicks on tab titles
                $tabTitles.on('click', function() {
                    var $clickedTitle = $(this);
                    var $clickedItem = $clickedTitle.closest('.nav-sections-item');

                    // Remove active from all tabs
                    $allTabItems.removeClass('active _active');

                    // Add active to clicked tab
                    $clickedItem.addClass('active');
                });
            }

            // Wait for nav-sections to be populated with cloned menu
            // The theme clones the menu into .nav-sections after page load
            function waitForMobileNav(attempt) {
                attempt = attempt || 1;

                var $mobileNav = $('.nav-sections .navigation');

                // Check if the menu has been cloned (has items)
                if ($mobileNav.length > 0 && $mobileNav.find('.parent').length > 0) {
                    setupMobileAccordion($mobileNav);
                } else if (attempt < 20) {
                    // Try again after 200ms, up to 20 times (4 seconds total)
                    setTimeout(function() {
                        waitForMobileNav(attempt + 1);
                    }, 200);
                }
            }

            function setupMobileAccordion($mobileNav) {
                // First attempt to disable widget immediately on the mobile nav
                disableMagentoMenuWidget($mobileNav);

                // Initialize tabs
                initializeTabs();

                // Find ALL parent items at all levels in BOTH original and cloned menus
                var $allParentLinks = $mobileNav.find('.parent > a, .parent > .pmenu-link');

                // Function to attach handlers to newly visible parent items
                function attachAccordionHandlers($parentLinks) {
                    // Remove any existing handlers first to prevent duplicates
                    $parentLinks.off('click.megamenu');

                    // Use ONLY click event - mobile devices will convert tap to click
                    $parentLinks.on('click.megamenu', function(e) {
                        // CRITICAL: Stop ALL propagation immediately to block Magento's menu.js and theme
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        var $link = $(this);
                        var $item = $link.closest('.parent');
                        var $submenu = $link.siblings('.pmenu-dropdown, .submenu, ul');
                        var isCurrentlyActive = $link.hasClass('ui-state-active');

                        // Toggle active state
                        if (isCurrentlyActive) {
                            // Currently open - close it AND all nested children
                            $link.removeClass('ui-state-active');
                            $submenu.slideUp(200);

                            // Also close all nested submenus
                            $submenu.find('.ui-state-active').removeClass('ui-state-active');
                            $submenu.find('.pmenu-dropdown, .submenu').hide();
                        } else {
                            // Currently closed - open it
                            // Close ONLY direct siblings at the same level
                            var siblings = $item.siblings('.parent');

                            siblings.each(function() {
                                var $siblingLink = $(this).find('> a, > .pmenu-link').first();
                                var $siblingSubmenu = $siblingLink.siblings('.pmenu-dropdown, .submenu, ul');

                                if ($siblingLink.hasClass('ui-state-active')) {
                                    $siblingLink.removeClass('ui-state-active');
                                    $siblingSubmenu.slideUp(200);
                                    // Close nested items too
                                    $siblingSubmenu.find('.ui-state-active').removeClass('ui-state-active');
                                    $siblingSubmenu.find('.pmenu-dropdown, .submenu').hide();
                                }
                            });

                            $link.addClass('ui-state-active');
                            $submenu.slideDown(200, function() {
                                // After opening, attach handlers to any newly visible nested parent items
                                var $nestedParents = $submenu.find('.parent > a, .parent > .pmenu-link');

                                if ($nestedParents.length > 0) {
                                    attachAccordionHandlers($nestedParents);
                                }
                            });
                        }

                        return false;
                    });
                }

                // Attach handlers to ALL currently visible parent links
                attachAccordionHandlers($allParentLinks);

                // Try to disable widget a few more times in case theme re-initializes
                var disableAttempts = [500, 1500, 3000];
                disableAttempts.forEach(function(delay) {
                    setTimeout(function() {
                        if ($mobileNav.data('mage-menu') || $mobileNav.data('mage-navigationMenu')) {
                            disableMagentoMenuWidget($mobileNav);
                        }
                    }, delay);
                });
            }

            // Start waiting for mobile nav
            waitForMobileNav();
        }

        /**
         * Desktop Hover - Apply Smart Positioning
         */
        if (windowWidth >= 992) {
            // Top level items
            $menu.find('.pmenu-item-l0').on('mouseenter', function() {
                var $item = $(this);
                var $dropdown = $item.children('.pmenu-dropdown');
                if ($dropdown.length) {
                    setTimeout(function() {
                        smartPosition($dropdown, true);
                    }, 50);
                }
            });

            // Nested items
            $menu.on('mouseenter', '.pmenu-dropdown .pmenu-item', function() {
                var $item = $(this);
                var $dropdown = $item.children('.pmenu-dropdown');
                if ($dropdown.length) {
                    setTimeout(function() {
                        smartPosition($dropdown, false);
                    }, 50);
                }
            });
        }

        /**
         * Active Page Highlighting
         */
        var currentPath = window.location.origin + window.location.pathname;
        $menu.find('.pmenu-link[href]').each(function() {
            if (this.href === currentPath) {
                $(this).addClass('active');
                $(this).closest('.pmenu-item-l0').addClass('has-active');
            }
        });

        /**
         * Dynamic Hover Colors from Data Attributes
         */
        $menu.find('[data-hover-bg-color], [data-hover-text-color]').each(function() {
            var $el = $(this);
            var hoverBgColor = $el.attr('data-hover-bg-color');
            var hoverTextColor = $el.attr('data-hover-text-color');

            var originalBgColor = $el.css('background-color');
            var originalTextColor = $el.css('color');

            $el.hover(
                function() {
                    if (hoverBgColor) $(this).css('background-color', hoverBgColor);
                    if (hoverTextColor) $(this).css('color', hoverTextColor);
                },
                function() {
                    if (hoverBgColor) $(this).css('background-color', originalBgColor);
                    if (hoverTextColor) $(this).css('color', originalTextColor);
                }
            );
        });

        /**
         * Handle Window Resize
         */
        var resizeTimeout;
        $window.on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                var newWidth = $window.width();
                if (Math.abs(windowWidth - newWidth) > 10) { // Only if significant change
                    windowWidth = newWidth;
                }
            }, 250);
        });

        /**
         * Accessibility - Keyboard Navigation
         */
        $menu.find('.pmenu-link').on('keydown', function(e) {
            var $link = $(this);
            var $item = $link.parent('.pmenu-item');
            var $dropdown = $item.children('.pmenu-dropdown');

            // Enter/Space - Toggle dropdown
            if (e.key === 'Enter' || e.key === ' ') {
                if ($dropdown.length) {
                    e.preventDefault();
                    $dropdown.slideToggle(200);
                }
            }

            // Escape - Close dropdown
            if (e.key === 'Escape') {
                $dropdown.slideUp(200);
                $link.focus();
            }
        });

        // Initialize
    };
});
