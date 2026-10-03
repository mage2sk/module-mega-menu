(function () {
    'use strict';

    if (window.PanthMegaMenu) {
        window.PanthMegaMenu.scan();
        return;
    }

    var FOCUSABLE = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
    var EDGE = 16;
    var openDrawer = null;

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function isHashUrl(link) {
        var href = (link.getAttribute('href') || '').trim();
        return href === '' || href === '#' || href.indexOf('javascript:') === 0;
    }

    function visibleLinks(container) {
        return toArray(container.querySelectorAll('a[href], button.pmm-btn')).filter(function (el) {
            return el.offsetWidth > 0 || el.offsetHeight > 0;
        });
    }

    function containerBox(root) {
        var host = root;
        var el = root.parentElement;
        while (el && el !== document.body) {
            var mw = window.getComputedStyle(el).maxWidth;
            if (el.classList.contains('container') || (mw !== 'none' && mw.indexOf('px') !== -1)) {
                host = el;
                break;
            }
            el = el.parentElement;
        }
        var rect = host.getBoundingClientRect();
        var cs = window.getComputedStyle(host);
        var left = rect.left + (parseFloat(cs.paddingLeft) || 0);
        var right = rect.right - (parseFloat(cs.paddingRight) || 0);
        var vw = document.documentElement.clientWidth;
        if (right - left < 320 || right > vw) {
            rect = root.getBoundingClientRect();
            left = rect.left;
            right = rect.right;
        }
        left = Math.max(left, EDGE / 2);
        right = Math.min(right, vw - EDGE / 2);
        return { left: left, width: Math.max(0, right - left) };
    }

    function Desktop(root) {
        this.root = root;
        this.bar = root.querySelector('.pmm-bar');
        this.items = toArray(root.querySelectorAll('.pmm-top'));
        this.triggers = this.items.map(function (li) { return li.querySelector('.pmm-trigger'); });
        this.delay = parseInt(root.getAttribute('data-pmm-delay'), 10);
        if (isNaN(this.delay) || this.delay < 0) {
            this.delay = 150;
        }
        this.delay = Math.min(this.delay, 400);
        this.current = null;
        this.timer = null;
        this.closeTimer = null;
        this.lastPointer = 'mouse';
        this.bind();
        this.initScroll();
    }

    Desktop.prototype.clear = function () {
        clearTimeout(this.timer);
        clearTimeout(this.closeTimer);
        this.timer = null;
        this.closeTimer = null;
    };

    Desktop.prototype.panelOf = function (li) {
        return li ? li.querySelector(':scope > .pmm-panel') : null;
    };

    Desktop.prototype.position = function (li) {
        var panel = this.panelOf(li);
        if (!panel) {
            return;
        }
        var rootRect = this.root.getBoundingClientRect();
        var vw = document.documentElement.clientWidth;
        panel.style.maxHeight = '';
        if (panel.classList.contains('pmm-panel--full')) {
            var box = containerBox(this.root);
            panel.style.left = (box.left - rootRect.left) + 'px';
            panel.style.width = box.width + 'px';
        } else {
            var trig = li.querySelector('.pmm-trigger').getBoundingClientRect();
            var fit = panel.classList.contains('pmm-panel--fit');
            var minLeft = EDGE;
            var maxRight = vw - EDGE;
            if (fit) {
                var area = containerBox(this.root);
                minLeft = Math.max(EDGE / 2, area.left);
                maxRight = Math.min(vw - EDGE / 2, area.left + area.width);
                panel.style.maxWidth = Math.max(0, maxRight - minLeft) + 'px';
            }
            panel.style.left = '0px';
            var width = panel.offsetWidth;
            var left = trig.left;
            if (left + width > maxRight) {
                left = fit ? maxRight - width : Math.min(trig.right, maxRight) - width;
            }
            left = Math.max(minLeft, left);
            panel.style.left = (left - rootRect.left) + 'px';
        }
        var top = panel.getBoundingClientRect().top;
        var room = window.innerHeight - Math.max(top, 0) - EDGE;
        panel.style.maxHeight = Math.max(room, 240) + 'px';
    };

    Desktop.prototype.open = function (li) {
        this.clear();
        if (this.current === li) {
            return;
        }
        this.closeNow();
        if (!this.panelOf(li)) {
            return;
        }
        li.classList.add('is-open');
        var trig = li.querySelector('.pmm-trigger');
        trig.setAttribute('aria-expanded', 'true');
        this.current = li;
        this.position(li);
    };

    Desktop.prototype.closeNow = function () {
        if (!this.current) {
            return;
        }
        var trig = this.current.querySelector('.pmm-trigger');
        this.current.classList.remove('is-open');
        if (trig) {
            trig.setAttribute('aria-expanded', 'false');
        }
        this.current = null;
    };

    Desktop.prototype.scheduleOpen = function (li) {
        var self = this;
        clearTimeout(this.closeTimer);
        clearTimeout(this.timer);
        if (this.current === li) {
            return;
        }
        var wait = this.current ? Math.min(this.delay, 120) : this.delay;
        this.timer = setTimeout(function () {
            if (li.querySelector(':scope > .pmm-panel')) {
                self.open(li);
            } else {
                self.closeNow();
            }
        }, wait);
    };

    Desktop.prototype.scheduleClose = function () {
        var self = this;
        clearTimeout(this.timer);
        clearTimeout(this.closeTimer);
        this.closeTimer = setTimeout(function () { self.closeNow(); }, 280);
    };

    Desktop.prototype.focusTrigger = function (index) {
        var n = this.triggers.length;
        if (!n) {
            return;
        }
        var t = this.triggers[(index + n) % n];
        t.focus();
        if (t.scrollIntoView) {
            t.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    };

    Desktop.prototype.focusInPanel = function (li, which) {
        var panel = this.panelOf(li);
        if (!panel) {
            return;
        }
        var links = visibleLinks(panel);
        if (links.length) {
            (which === 'last' ? links[links.length - 1] : links[0]).focus();
        }
    };

    Desktop.prototype.bind = function () {
        var self = this;
        this.items.forEach(function (li, index) {
            var trig = li.querySelector('.pmm-trigger');
            var panel = self.panelOf(li);
            li.addEventListener('pointerenter', function (e) {
                if (e.pointerType === 'mouse') {
                    self.scheduleOpen(li);
                }
            });
            if (panel) {
                panel.addEventListener('pointerenter', function (e) {
                    if (e.pointerType === 'mouse' && self.current === li) {
                        self.clear();
                    }
                });
            }
            trig.addEventListener('pointerdown', function (e) {
                self.lastPointer = e.pointerType || 'mouse';
            });
            trig.addEventListener('click', function (e) {
                if (!panel) {
                    if (isHashUrl(trig) && trig.getAttribute('href') !== '#') {
                        e.preventDefault();
                    }
                    return;
                }
                var touchFirstTap = self.lastPointer !== 'mouse' && self.current !== li;
                if (isHashUrl(trig) || touchFirstTap) {
                    e.preventDefault();
                    if (self.current === li) {
                        self.closeNow();
                    } else {
                        self.open(li);
                    }
                }
                self.lastPointer = 'mouse';
            });
            trig.addEventListener('keydown', function (e) {
                var key = e.key;
                if (key === 'ArrowDown' || key === ' ' || key === 'Spacebar' || (key === 'Enter' && panel && isHashUrl(trig))) {
                    if (!panel) {
                        return;
                    }
                    e.preventDefault();
                    self.open(li);
                    self.focusInPanel(li, 'first');
                } else if (key === 'ArrowUp' && panel) {
                    e.preventDefault();
                    self.open(li);
                    self.focusInPanel(li, 'last');
                } else if (key === 'ArrowRight') {
                    e.preventDefault();
                    self.closeNow();
                    self.focusTrigger(index + 1);
                } else if (key === 'ArrowLeft') {
                    e.preventDefault();
                    self.closeNow();
                    self.focusTrigger(index - 1);
                } else if (key === 'Home') {
                    e.preventDefault();
                    self.focusTrigger(0);
                } else if (key === 'End') {
                    e.preventDefault();
                    self.focusTrigger(self.triggers.length - 1);
                } else if (key === 'Escape' && self.current) {
                    e.preventDefault();
                    self.closeNow();
                }
            });
            if (panel) {
                panel.addEventListener('keydown', function (e) {
                    var links = visibleLinks(panel);
                    var pos = links.indexOf(document.activeElement);
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (links.length) {
                            links[(pos + 1) % links.length].focus();
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (pos <= 0) {
                            trig.focus();
                        } else {
                            links[pos - 1].focus();
                        }
                    } else if (e.key === 'Home') {
                        e.preventDefault();
                        if (links.length) {
                            links[0].focus();
                        }
                    } else if (e.key === 'End') {
                        e.preventDefault();
                        if (links.length) {
                            links[links.length - 1].focus();
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        e.stopPropagation();
                        self.closeNow();
                        trig.focus();
                    }
                });
            }
            li.addEventListener('focusout', function (e) {
                if (self.current === li && e.relatedTarget && !li.contains(e.relatedTarget)) {
                    self.closeNow();
                }
            });
        });
        this.root.addEventListener('pointerleave', function (e) {
            if (e.pointerType === 'mouse') {
                self.scheduleClose();
            }
        });
        this.root.addEventListener('pointerenter', function (e) {
            if (e.pointerType === 'mouse') {
                clearTimeout(self.closeTimer);
            }
        });
        document.addEventListener('pointerdown', function (e) {
            if (self.current && !self.root.contains(e.target)) {
                self.closeNow();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && self.current) {
                var trig = self.current.querySelector('.pmm-trigger');
                var inside = self.current.contains(document.activeElement);
                self.closeNow();
                if (inside && trig) {
                    trig.focus();
                }
            }
        });
        window.addEventListener('resize', function () {
            if (self.current) {
                self.position(self.current);
            }
        });
    };

    Desktop.prototype.initScroll = function () {
        var self = this;
        var bar = this.bar;
        var prev = this.root.querySelector('.pmm-scroll--prev');
        var next = this.root.querySelector('.pmm-scroll--next');
        if (!bar || !prev || !next) {
            return;
        }
        var el = this.root.parentElement;
        for (var i = 0; i < 3 && el && el !== document.body; i++) {
            var display = window.getComputedStyle(el.parentElement || el).display;
            if (display.indexOf('flex') !== -1 || display.indexOf('grid') !== -1) {
                el.style.minWidth = '0';
            }
            el = el.parentElement;
        }
        function update() {
            var max = bar.scrollWidth - bar.clientWidth;
            prev.classList.toggle('is-visible', max > 2 && bar.scrollLeft > 2);
            next.classList.toggle('is-visible', max > 2 && bar.scrollLeft < max - 2);
        }
        function step(dir) {
            self.closeNow();
            bar.scrollBy({ left: dir * Math.max(160, bar.clientWidth * 0.6), behavior: 'smooth' });
        }
        prev.querySelector('button').addEventListener('click', function () { step(-1); });
        next.querySelector('button').addEventListener('click', function () { step(1); });
        bar.addEventListener('scroll', function () {
            update();
            if (self.current) {
                self.position(self.current);
            }
        }, { passive: true });
        bar.addEventListener('wheel', function (e) {
            var max = bar.scrollWidth - bar.clientWidth;
            if (max <= 2 || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                return;
            }
            var target = bar.scrollLeft + e.deltaY;
            if ((e.deltaY < 0 && bar.scrollLeft <= 0) || (e.deltaY > 0 && bar.scrollLeft >= max)) {
                return;
            }
            e.preventDefault();
            bar.scrollLeft = Math.max(0, Math.min(max, target));
        }, { passive: false });
        window.addEventListener('resize', update);
        if (window.ResizeObserver) {
            new ResizeObserver(update).observe(bar);
        }
        update();
    };

    function Mobile(root) {
        var self = this;
        this.root = root;
        this.burger = root.querySelector('.pmm-burger');
        this.layer = root.querySelector('.pmm-layer');
        if (!this.burger || !this.layer) {
            return;
        }
        this.drawer = this.layer.querySelector('.pmm-drawer');
        this.backdrop = this.layer.querySelector('.pmm-backdrop');
        document.body.appendChild(this.layer);
        this.burger.addEventListener('click', function () { self.open(); });
        toArray(this.layer.querySelectorAll('[data-pmm-close]')).forEach(function (el) {
            el.addEventListener('click', function () { self.close(); });
        });
        this.drawer.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                self.close();
            } else if (e.key === 'Tab') {
                var all = toArray(self.drawer.querySelectorAll(FOCUSABLE)).filter(function (x) {
                    return x.offsetWidth > 0 || x.offsetHeight > 0;
                });
                if (!all.length) {
                    return;
                }
                var first = all[0];
                var last = all[all.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
        toArray(this.layer.querySelectorAll('.pmm-m-toggle')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.pmm-m-item');
                var open = !item.classList.contains('is-open');
                item.classList.toggle('is-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
        toArray(this.layer.querySelectorAll('.pmm-m-link')).forEach(function (link) {
            link.addEventListener('click', function (e) {
                var row = link.parentElement;
                var btn = row ? row.querySelector('.pmm-m-toggle') : null;
                if (btn && isHashUrl(link)) {
                    e.preventDefault();
                    btn.click();
                }
            });
        });
        window.addEventListener('resize', function () {
            if (openDrawer === self && window.getComputedStyle(self.root.querySelector('.pmm-mobile') || self.root).display === 'none') {
                self.close(true);
            }
        });
    }

    Mobile.prototype.open = function () {
        if (openDrawer && openDrawer !== this) {
            openDrawer.close(true);
        }
        openDrawer = this;
        this.backdrop.classList.add('is-open');
        this.drawer.classList.add('is-open');
        this.drawer.setAttribute('aria-hidden', 'false');
        this.burger.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('panth-overlay-open', 'pmm-lock');
        var close = this.drawer.querySelector('.pmm-drawer-close');
        var drawer = this.drawer;
        setTimeout(function () { (close || drawer).focus(); }, 50);
    };

    Mobile.prototype.close = function (silent) {
        if (openDrawer === this) {
            openDrawer = null;
        }
        this.backdrop.classList.remove('is-open');
        this.drawer.classList.remove('is-open');
        this.drawer.setAttribute('aria-hidden', 'true');
        this.burger.setAttribute('aria-expanded', 'false');
        if (!openDrawer) {
            document.documentElement.classList.remove('panth-overlay-open', 'pmm-lock');
        }
        if (!silent) {
            this.burger.focus();
        }
    };

    function init(root) {
        if (root.getAttribute('data-pmm-ready')) {
            return;
        }
        root.setAttribute('data-pmm-ready', '1');
        if (root.querySelector('.pmm-desktop')) {
            new Desktop(root.querySelector('.pmm-desktop'));
        }
        if (root.querySelector('.pmm-mobile')) {
            new Mobile(root);
        }
    }

    function scan() {
        toArray(document.querySelectorAll('[data-pmm]')).forEach(init);
    }

    window.PanthMegaMenu = { scan: scan };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})();
