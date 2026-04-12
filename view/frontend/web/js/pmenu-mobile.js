/**
 * Panth MegaMenu - Mobile Drawer + Accordion Widget
 *
 * Handles the hamburger button, off-canvas drawer slide-in/out,
 * overlay, and accordion-style expand/collapse of child items.
 *
 * Usage via data-mage-init on the hamburger button:
 *   data-mage-init='{"pmenuMobile": { ... }}'
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('panth.pmenuMobile', {

        options: {
            position: 'left',            // left | right
            overlay: true,
            accordion: true,
            breakpoint: 1024,
            animationDuration: 250,
            drawerSelector: '#pmenu-mobile-drawer',
            overlaySelector: '#pmenu-mobile-overlay',
            closeSelector: '.pmenu-mobile-close'
        },

        /* ── lifecycle ──────────────────────────────────────────── */

        _create: function () {
            this.$drawer  = $(this.options.drawerSelector);
            this.$overlay = $(this.options.overlaySelector);
            this._isOpen  = false;

            this._bindToggle();
            this._bindClose();
            this._bindOverlay();
            this._bindAccordion();
            this._bindResize();
            this._bindEscape();
        },

        destroy: function () {
            $(document).off('.pmenuMobile');
            $(window).off('.pmenuMobile');
            this._super();
        },

        /* ── open / close ───────────────────────────────────────── */

        open: function () {
            if (this._isOpen) { return; }
            this._isOpen = true;

            this.element.attr('aria-expanded', 'true');
            this.$drawer.addClass('pmenu-drawer-open');
            this.$overlay.addClass('pmenu-overlay-visible');
            $('body').addClass('pmenu-no-scroll');

            // Focus first link inside drawer
            var self = this;
            setTimeout(function () {
                self.$drawer.find('.pmenu-link, .pmenu-mobile-close').first().focus();
            }, self.options.animationDuration);
        },

        close: function () {
            if (!this._isOpen) { return; }
            this._isOpen = false;

            this.element.attr('aria-expanded', 'false');
            this.$drawer.removeClass('pmenu-drawer-open');
            this.$overlay.removeClass('pmenu-overlay-visible');
            $('body').removeClass('pmenu-no-scroll');

            // Return focus to hamburger
            this.element.focus();
        },

        toggle: function () {
            this._isOpen ? this.close() : this.open();
        },

        /* ── event bindings ─────────────────────────────────────── */

        _bindToggle: function () {
            var self = this;

            // Hamburger button click
            this.element.on('click.pmenuMobile', function (e) {
                e.preventDefault();
                self.toggle();
            });
        },

        _bindClose: function () {
            var self = this;

            this.$drawer.on('click.pmenuMobile', this.options.closeSelector, function (e) {
                e.preventDefault();
                self.close();
            });
        },

        _bindOverlay: function () {
            var self = this;

            if (this.options.overlay) {
                this.$overlay.on('click.pmenuMobile', function () {
                    self.close();
                });
            }
        },

        _bindEscape: function () {
            var self = this;

            $(document).on('keydown.pmenuMobile', function (e) {
                if (e.key === 'Escape' && self._isOpen) {
                    self.close();
                }
            });
        },

        _bindResize: function () {
            var self = this,
                timer;

            $(window).on('resize.pmenuMobile', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    if (window.innerWidth >= self.options.breakpoint && self._isOpen) {
                        self.close();
                    }
                }, 100);
            });
        },

        /* ── accordion ──────────────────────────────────────────── */

        _bindAccordion: function () {
            if (!this.options.accordion) { return; }

            var self = this;

            this.$drawer.on('click.pmenuMobile', '.pmenu-accordion-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn  = $(this),
                    $item = $btn.closest('.pmenu-item');

                self._toggleAccordion($item);
            });

            // Also allow tapping the link text on items with children
            this.$drawer.on('click.pmenuMobile', '.pmenu-has-children > .pmenu-link', function (e) {
                var href = $(this).attr('href');
                // If link is just '#', toggle accordion instead of navigating
                if (!href || href === '#') {
                    e.preventDefault();
                    var $item = $(this).closest('.pmenu-item');
                    self._toggleAccordion($item);
                }
                // Otherwise let the link work normally
            });
        },

        _toggleAccordion: function ($item) {
            var $panel = $item.children('.pmenu-accordion-panel'),
                speed  = this.options.animationDuration;

            if (!$panel.length) { return; }

            var isOpen = $item.hasClass('pmenu-accordion-open');

            if (isOpen) {
                // Close
                $item.removeClass('pmenu-accordion-open');
                $panel.slideUp(speed);
                $item.find('.pmenu-accordion-toggle').attr('aria-expanded', 'false');
            } else {
                // Close siblings
                $item.siblings('.pmenu-accordion-open').each(function () {
                    var $sib = $(this);
                    $sib.removeClass('pmenu-accordion-open');
                    $sib.children('.pmenu-accordion-panel').slideUp(speed);
                    $sib.find('.pmenu-accordion-toggle').attr('aria-expanded', 'false');
                });

                // Open this
                $item.addClass('pmenu-accordion-open');
                $panel.slideDown(speed);
                $item.find('> .pmenu-accordion-toggle').attr('aria-expanded', 'true');
            }
        }
    });

    return $.panth.pmenuMobile;
});
