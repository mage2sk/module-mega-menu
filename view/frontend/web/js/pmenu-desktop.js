/**
 * Panth MegaMenu - Desktop Navigation Widget
 *
 * Handles hover-intent dropdowns, keyboard navigation, smart positioning,
 * sticky header, and animation types (fade / slide-down / grow).
 *
 * Usage via data-mage-init:
 *   data-mage-init='{"pmenuDesktop": { ... }}'
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('panth.pmenuDesktop', {

        options: {
            hoverDelay: 150,
            animationType: 'fade',       // fade | slide-down | grow
            animationDuration: 200,
            breakpoint: 1024,
            stickyEnabled: false,
            stickyOffset: 0
        },

        /* ── lifecycle ──────────────────────────────────────────── */

        _create: function () {
            this._openTimers = {};
            this._closeTimers = {};
            this._isMobile = window.innerWidth < this.options.breakpoint;

            this._bindHover();
            this._bindKeyboard();
            this._bindOutsideClick();
            this._bindResize();

            if (this.options.stickyEnabled) {
                this._initSticky();
            }
        },

        destroy: function () {
            this._clearAllTimers();
            $(document).off('.pmenuDesktop');
            $(window).off('.pmenuDesktop');
            this._super();
        },

        /* ── hover handlers ─────────────────────────────────────── */

        _bindHover: function () {
            var self = this;

            this.element.on('mouseenter.pmenuDesktop', '.pmenu-item', function (e) {
                if (self._isMobile) { return; }
                e.stopPropagation();
                var $item = $(this);
                self._cancelClose($item);
                self._scheduleOpen($item);
            });

            this.element.on('mouseleave.pmenuDesktop', '.pmenu-item', function (e) {
                if (self._isMobile) { return; }
                e.stopPropagation();
                var $item = $(this);
                self._cancelOpen($item);
                self._scheduleClose($item);
            });
        },

        _scheduleOpen: function ($item) {
            var self = this,
                id   = $item.data('item-id');

            this._openTimers[id] = setTimeout(function () {
                self._openDropdown($item);
            }, this.options.hoverDelay);
        },

        _cancelOpen: function ($item) {
            var id = $item.data('item-id');
            if (this._openTimers[id]) {
                clearTimeout(this._openTimers[id]);
                delete this._openTimers[id];
            }
        },

        _scheduleClose: function ($item) {
            var self = this,
                id   = $item.data('item-id');

            this._closeTimers[id] = setTimeout(function () {
                self._closeDropdown($item);
            }, this.options.hoverDelay + 100);
        },

        _cancelClose: function ($item) {
            var id = $item.data('item-id');
            if (this._closeTimers[id]) {
                clearTimeout(this._closeTimers[id]);
                delete this._closeTimers[id];
            }
        },

        _clearAllTimers: function () {
            var k;
            for (k in this._openTimers)  { clearTimeout(this._openTimers[k]); }
            for (k in this._closeTimers) { clearTimeout(this._closeTimers[k]); }
            this._openTimers  = {};
            this._closeTimers = {};
        },

        /* ── open / close / closeAll ────────────────────────────── */

        _openDropdown: function ($item) {
            var $dd = $item.children('.pmenu-dropdown');
            if (!$dd.length) { return; }

            // Close siblings first
            $item.siblings('.pmenu-item-open').each($.proxy(function (_, el) {
                this._closeDropdown($(el));
            }, this));

            $item.addClass('pmenu-item-open');
            $item.children('.pmenu-link').attr('aria-expanded', 'true');

            var speed = this.options.animationDuration;

            switch (this.options.animationType) {
                case 'slide-down':
                    $dd.stop(true, true).slideDown(speed);
                    break;
                case 'grow':
                    $dd.css({ display: 'block', transform: 'scale(0.95)', opacity: 0 })
                       .animate({ opacity: 1 }, speed, function () {
                           $(this).css('transform', 'scale(1)');
                       });
                    break;
                case 'fade':
                default:
                    $dd.stop(true, true).fadeIn(speed);
                    break;
            }

            this._smartPosition($item, $dd);
        },

        _closeDropdown: function ($item) {
            var $dd = $item.children('.pmenu-dropdown');
            if (!$dd.length) { return; }

            // Close children first
            $dd.find('.pmenu-item-open').each($.proxy(function (_, el) {
                this._closeDropdown($(el));
            }, this));

            $item.removeClass('pmenu-item-open');
            $item.children('.pmenu-link').attr('aria-expanded', 'false');

            var speed = this.options.animationDuration;

            switch (this.options.animationType) {
                case 'slide-down':
                    $dd.stop(true, true).slideUp(speed);
                    break;
                case 'grow':
                    $dd.animate({ opacity: 0 }, speed, function () {
                        $(this).css({ display: 'none', transform: '' });
                    });
                    break;
                case 'fade':
                default:
                    $dd.stop(true, true).fadeOut(speed);
                    break;
            }
        },

        _closeAll: function () {
            var self = this;
            this.element.find('.pmenu-item-open').each(function () {
                self._closeDropdown($(this));
            });
            this._clearAllTimers();
        },

        /* ── smart positioning ──────────────────────────────────── */

        _smartPosition: function ($item, $dd) {
            // Reset
            $dd.removeClass('pmenu-dd-right pmenu-dd-left');

            var ddOffset = $dd.offset(),
                ddWidth  = $dd.outerWidth(),
                vpWidth  = $(window).width();

            if (!ddOffset) { return; }

            // Overflow right
            if ((ddOffset.left + ddWidth) > vpWidth) {
                $dd.addClass('pmenu-dd-right');
            }
            // Overflow left
            if (ddOffset.left < 0) {
                $dd.addClass('pmenu-dd-left');
            }
        },

        /* ── keyboard navigation ────────────────────────────────── */

        _bindKeyboard: function () {
            var self = this;

            this.element.on('keydown.pmenuDesktop', '.pmenu-link', function (e) {
                var $link = $(this),
                    $item = $link.closest('.pmenu-item'),
                    level = parseInt($item.data('level'), 10) || 0;

                switch (e.key) {
                    case 'Enter':
                    case ' ':
                        if ($item.hasClass('pmenu-has-children')) {
                            e.preventDefault();
                            if ($item.hasClass('pmenu-item-open')) {
                                self._closeDropdown($item);
                            } else {
                                self._openDropdown($item);
                                // Focus first child link
                                $item.children('.pmenu-dropdown').find('.pmenu-link').first().focus();
                            }
                        }
                        break;

                    case 'Escape':
                        e.preventDefault();
                        self._closeDropdown($item);
                        $link.focus();
                        break;

                    case 'ArrowDown':
                        e.preventDefault();
                        if (level === 0 && $item.hasClass('pmenu-has-children')) {
                            self._openDropdown($item);
                            $item.children('.pmenu-dropdown').find('.pmenu-link').first().focus();
                        } else {
                            // Next sibling
                            var $next = $item.next('.pmenu-item').children('.pmenu-link');
                            if ($next.length) { $next.focus(); }
                        }
                        break;

                    case 'ArrowUp':
                        e.preventDefault();
                        var $prev = $item.prev('.pmenu-item').children('.pmenu-link');
                        if ($prev.length) { $prev.focus(); }
                        break;

                    case 'ArrowRight':
                        e.preventDefault();
                        if (level === 0) {
                            var $nextTop = $item.next('.pmenu-item').children('.pmenu-link');
                            if ($nextTop.length) { $nextTop.focus(); }
                        } else if ($item.hasClass('pmenu-has-children')) {
                            self._openDropdown($item);
                            $item.children('.pmenu-dropdown').find('.pmenu-link').first().focus();
                        }
                        break;

                    case 'ArrowLeft':
                        e.preventDefault();
                        if (level === 0) {
                            var $prevTop = $item.prev('.pmenu-item').children('.pmenu-link');
                            if ($prevTop.length) { $prevTop.focus(); }
                        } else {
                            // Go up to parent
                            var $parent = $item.closest('.pmenu-dropdown').closest('.pmenu-item');
                            self._closeDropdown($parent);
                            $parent.children('.pmenu-link').focus();
                        }
                        break;

                    case 'Tab':
                        // Let default tab happen, but close menus after a tick
                        setTimeout(function () {
                            if (!self.element.find(':focus').length) {
                                self._closeAll();
                            }
                        }, 10);
                        break;
                }
            });
        },

        /* ── outside click ──────────────────────────────────────── */

        _bindOutsideClick: function () {
            var self = this;

            $(document).on('click.pmenuDesktop', function (e) {
                if (!$(e.target).closest('.pmenu-nav').length) {
                    self._closeAll();
                }
            });

            $(document).on('keydown.pmenuDesktop', function (e) {
                if (e.key === 'Escape') {
                    self._closeAll();
                }
            });
        },

        /* ── responsive ─────────────────────────────────────────── */

        _bindResize: function () {
            var self = this,
                timer;

            $(window).on('resize.pmenuDesktop', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var wasMobile = self._isMobile;
                    self._isMobile = window.innerWidth < self.options.breakpoint;
                    if (wasMobile !== self._isMobile) {
                        self._closeAll();
                    }
                }, 100);
            });
        },

        /* ── sticky header ──────────────────────────────────────── */

        _initSticky: function () {
            var self = this,
                $nav = this.element,
                offset = this.options.stickyOffset,
                navTop = $nav.offset().top,
                placeholder = $('<div class="pmenu-sticky-placeholder"></div>').hide().insertAfter($nav);

            $(window).on('scroll.pmenuDesktop', function () {
                var scrollTop = $(window).scrollTop();

                if (scrollTop > navTop + offset) {
                    if (!$nav.hasClass('pmenu-sticky')) {
                        placeholder.css('height', $nav.outerHeight()).show();
                        $nav.addClass('pmenu-sticky');
                    }
                } else {
                    if ($nav.hasClass('pmenu-sticky')) {
                        $nav.removeClass('pmenu-sticky');
                        placeholder.hide();
                    }
                }
            });
        }
    });

    return $.panth.pmenuDesktop;
});
