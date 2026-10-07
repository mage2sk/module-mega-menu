/**
 * Panth MegaMenu - Hover Effects Handler
 * Handles dynamic hover color changes and effects based on data attributes
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $menu = $(element);

        /**
         * Initialize hover effects for menu items
         */
        function initHoverEffects() {
            // Handle items with hover background color
            $menu.find('[data-hover-bg-color]').each(function () {
                var $item = $(this);
                var hoverBgColor = $item.data('hover-bg-color');
                var originalBgColor = $item.css('background-color');

                $item.addClass('js-hover-active');

                $item.on('mouseenter', function () {
                    if (hoverBgColor) {
                        $item.css('background-color', hoverBgColor);
                    }
                });

                $item.on('mouseleave', function () {
                    $item.css('background-color', originalBgColor);
                });
            });

            // Handle links with hover text color
            $menu.find('[data-hover-text-color]').each(function () {
                var $link = $(this);
                var hoverTextColor = $link.data('hover-text-color');
                var originalTextColor = $link.css('color');

                $link.addClass('js-hover-active');

                $link.on('mouseenter', function () {
                    if (hoverTextColor) {
                        $link.css('color', hoverTextColor);
                    }
                });

                $link.on('mouseleave', function () {
                    $link.css('color', originalTextColor);
                });
            });

            // Handle custom hover effects
            $menu.find('[data-hover-effect]').each(function () {
                var $item = $(this);
                var hoverEffect = $item.data('hover-effect');

                if (hoverEffect && hoverEffect !== 'default') {
                    $item.addClass('pmenu-item--hover-' + hoverEffect);
                }
            });
        }

        /**
         * Initialize tooltips
         */
        function initTooltips() {
            // Native HTML title attribute tooltips work automatically
            // This function is here for potential future enhancements
            // like using a tooltip library
        }

        /**
         * Initialize custom click actions
         */
        function initCustomClickActions() {
            // Custom click actions are handled inline via onclick attributes
            // This function is here for potential future enhancements
        }

        /**
         * Initialize accessibility features
         */
        function initAccessibility() {
            // Ensure keyboard navigation works properly
            $menu.find('.pmenu-link').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    var $link = $(this);
                    var href = $link.attr('href');
                    var onclick = $link.attr('onclick');

                    // Execute custom click action if present
                    if (onclick && e.key === 'Enter') {
                        // Trigger the native click which runs the inline onclick handler
                        // instead of using eval() which is a code injection risk
                        $link[0].click();
                        if (href === '#') {
                            e.preventDefault();
                        }
                    }
                }
            });
        }

        /**
         * Initialize animation observers
         * Trigger animations when items come into view
         */
        function initAnimationObservers() {
            if ('IntersectionObserver' in window) {
                var animatedItems = $menu.find('.animate__animated');

                if (animatedItems.length > 0) {
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                $(entry.target).addClass('animate__animated');
                            }
                        });
                    }, {
                        threshold: 0.1
                    });

                    animatedItems.each(function () {
                        observer.observe(this);
                    });
                }
            }
        }

        /**
         * Initialize all features
         */
        function init() {
            initHoverEffects();
            initTooltips();
            initCustomClickActions();
            initAccessibility();
            initAnimationObservers();
        }

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    };
});
