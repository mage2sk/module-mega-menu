<?php
namespace Panth\MegaMenu\Helper;

use Magento\Framework\Escaper;
use Magento\Cms\Model\Block as CmsBlock;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

/**
 * Reusable Menu Renderer Helper
 * Used by BOTH admin preview and frontend template
 * Ensures admin and frontend look EXACTLY the same
 */
class MenuRenderer
{
    protected $escaper;
    protected $cmsBlock;
    protected $filterProvider;
    protected $storeManager;
    protected $assetRepository;

    public function __construct(
        Escaper $escaper,
        CmsBlock $cmsBlock,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        AssetRepository $assetRepository
    ) {
        $this->escaper = $escaper;
        $this->cmsBlock = $cmsBlock;
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get view file URL for static assets
     */
    protected function getViewFileUrl($fileId)
    {
        try {
            return $this->assetRepository->getUrl($fileId);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Render icon HTML from raw icon value and library
     */
    public function renderIcon($icon, $library = 'fontawesome')
    {
        if (!$icon) {
            return '';
        }

        if ($library === 'fontawesome') {
            return '<i class="' . $this->escaper->escapeHtmlAttr($icon) . '"></i>';
        } elseif ($library === 'emoji') {
            return '<span class="menu-icon menu-icon-emoji">' . $this->escaper->escapeHtml($icon) . '</span>';
        } elseif ($library === 'material') {
            return '<span class="material-symbols-outlined">' . $this->escaper->escapeHtml($icon) . '</span>';
        } elseif ($library === 'svg') {
            // SVG icon value is expected to be a complete SVG string or a path
            if (strpos($icon, '<svg') !== false) {
                return '<span class="menu-icon menu-icon-svg">' . $icon . '</span>';
            }
            return '<img src="' . $this->escaper->escapeUrl($icon) . '" alt="" class="menu-icon menu-icon-svg" loading="lazy" />';
        }
        return '';
    }

    /**
     * Render icon HTML from a menu item array
     *
     * @param array $item Menu item data with 'icon' and 'icon_library' keys
     * @return string
     */
    public function renderItemIcon(array $item): string
    {
        $icon = $item['icon'] ?? '';
        $library = $item['icon_library'] ?? 'fontawesome';
        return $this->renderIcon($icon, $library);
    }

    /**
     * Render menu item image with proper sizing
     *
     * @param array $item Menu item data with 'image', 'image_alt', 'image_width', 'image_height' keys
     * @param string $size Size preset: thumbnail, small, medium, large (default: thumbnail)
     * @return string
     */
    public function renderImage(array $item, string $size = 'thumbnail'): string
    {
        $imageUrl = $item['image'] ?? '';
        if (empty($imageUrl)) {
            return '';
        }

        $alt = $this->escaper->escapeHtmlAttr($item['image_alt'] ?? $item['title'] ?? '');

        // Size presets (width x height)
        $sizes = [
            'thumbnail' => ['width' => 50, 'height' => 50],
            'small'     => ['width' => 100, 'height' => 100],
            'medium'    => ['width' => 200, 'height' => 200],
            'large'     => ['width' => 400, 'height' => 400],
        ];

        $dimensions = $sizes[$size] ?? $sizes['thumbnail'];

        // Allow per-item overrides
        $width = (int)($item['image_width'] ?? $dimensions['width']);
        $height = (int)($item['image_height'] ?? $dimensions['height']);

        return '<img src="' . $this->escaper->escapeUrl($imageUrl) . '"'
            . ' alt="' . $alt . '"'
            . ' width="' . $width . '"'
            . ' height="' . $height . '"'
            . ' class="menu-item-image"'
            . ' loading="lazy"'
            . ' />';
    }

    /**
     * Render CMS block content
     */
    public function renderCmsBlock($blockId, $isPreview = false)
    {
        $cmsBlock = $this->cmsBlock->load($blockId);

        if ($cmsBlock->getId() && $cmsBlock->isActive()) {
            if ($isPreview) {
                // In admin preview, show placeholder
                return '<div class="text-sm text-gray-500 dark:text-gray-400 italic mb-2">CMS Block Content (ID: ' . $this->escaper->escapeHtml((string)$blockId) . ')</div>' .
                       '<div class="text-gray-700 dark:text-gray-300">CMS block content will be displayed here on the frontend.</div>';
            } else {
                // On frontend, render actual content
                $storeId = $this->storeManager->getStore()->getId();
                try {
                    $html = $this->filterProvider->getBlockFilter()->setStoreId($storeId)->filter($cmsBlock->getContent());
                    return $html;
                } catch (\Exception $e) {
                    return '<div class="text-sm text-red-600">Error loading CMS block</div>';
                }
            }
        }

        return '<div class="text-sm text-gray-500 italic">CMS block not found or inactive (ID: ' . $this->escaper->escapeHtml((string)$blockId) . ')</div>';
    }

    /**
     * Render desktop/tablet menu - EXACT same for admin preview and frontend
     */
    public function renderDesktopMenu($items, $isPreview = false)
    {
        // Outer wrapper - EXACTLY like backend preview
        $html = '<div id="panthMenuContent" class="panth-desktop" data-mobile-layout="accordion" x-data="{ preventScroll: true }" x-init="$nextTick(() => { document.documentElement.style.overflowX = \'hidden\'; document.body.style.overflowX = \'hidden\'; document.body.style.width = \'100%\'; document.body.style.maxWidth = \'100vw\'; })">';

        // Load all icon libraries locally (no CDN)
        $html .= '<link rel="stylesheet" href="' . $this->getViewFileUrl('Panth_MegaMenu::css/fontawesome/all.min.css') . '">';
        $html .= '<link rel="stylesheet" href="' . $this->getViewFileUrl('Panth_MegaMenu::css/lineicons/lineicons.css') . '">';
        $html .= '<link rel="stylesheet" href="' . $this->getViewFileUrl('Panth_MegaMenu::css/material-icons/material-icons.css') . '">';

        // Start with inline CSS - EXACTLY like backend preview
        $html .= '<style>';
        // FontAwesome fix for Luma theme (overrides Luma's icon fonts)
        $html .= '.fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands { font-family: "Font Awesome 6 Free" !important; }';
        $html .= '.fa-solid, .fas { font-weight: 900 !important; }';
        $html .= '.fa-regular, .far { font-weight: 400 !important; }';
        $html .= '.fa-brands, .fab { font-family: "Font Awesome 6 Brands" !important; font-weight: 400 !important; }';
        $html .= 'html, body { overflow-x: hidden !important; width: 100% !important; max-width: 100vw !important; position: relative; }';
        $html .= '#panthMenuContent { width: 100%; max-width: 100vw; position: relative; }';
        $html .= '.panth-dropdown { opacity: 0; visibility: hidden; transform: translateY(-10px); transition: opacity 0.3s, visibility 0.3s, transform 0.3s; max-height: none; overflow: visible; z-index: 1000; position: absolute; left: 0; top: 100%; max-width: min(750px, calc(100vw - 2rem)); width: max-content; }';
        $html .= '.panth-dropdown-nested { opacity: 0; visibility: hidden; transform: translateX(-10px); transition: opacity 0.3s, visibility 0.3s, transform 0.3s; max-height: none; overflow: visible; z-index: 1200; position: absolute; left: 100%; top: 0; margin-left: 0.5rem; background: white; border: 2px solid #e5e7eb; border-radius: 0.75rem; padding: 0.75rem; min-width: 220px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: min(300px, calc(100vw - 2rem)); width: max-content; }';
        $html .= '.panth-dropdown-nested.open-left { left: auto; right: 100%; margin-left: 0; margin-right: 0.5rem; transform: translateX(10px); }';
        $html .= '.group.menu-open > .panth-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }';
        $html .= '.group:hover > .panth-dropdown-nested { opacity: 1; visibility: visible; transform: translateX(0); }';
        $html .= '.group:hover > .panth-dropdown-nested.open-left { transform: translateX(0); }';
        $html .= '.panth-dropdown .group:hover > .panth-dropdown-nested { z-index: 1300; }';
        $html .= '.hover-fade:hover { opacity: 0.7 !important; transition: opacity 0.3s; }';
        $html .= '.hover-slide:hover { transform: translateY(-4px) !important; transition: transform 0.3s; }';
        $html .= '.hover-zoom:hover { transform: scale(1.05) !important; transition: transform 0.3s; }';
        $html .= '.hover-underline:hover { text-decoration: underline !important; text-underline-offset: 4px; }';
        $html .= '.hover-glow:hover { box-shadow: 0 0 20px rgba(99, 102, 241, 0.5) !important; transition: box-shadow 0.3s; }';
        $html .= '#panthMenuContent { max-width: 100%; }';
        $html .= '.megamenu-container { max-width: 100%; }';
        $html .= '.megamenu-container ul { max-width: 100%; }';
        $html .= '.sale-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px; }';
        $html .= '.material-symbols-outlined { font-family: "Material Symbols Outlined"; }';
        $html .= '.menu-arrow { display: inline-block; margin-left: 0.5rem; font-size: 0.75rem; transition: transform 0.3s; color: #6b7280; }';
        $html .= '.group.menu-open > a .menu-arrow { transform: rotate(180deg); }';
        $html .= '.group:hover > a .menu-arrow { transform: rotate(180deg); }';
        $html .= '.has-dropdown > a { display: flex; align-items: center; }';
        // Horizontal scroll for overflow menu items
        $html .= '.panth-menu-scroll-wrap { position: relative; }';
        $html .= '.panth-menu-scroll-wrap > nav { overflow-x: clip; overflow-y: visible; }';
        $html .= '.panth-menu-scroll-wrap > nav > ul { flex-wrap: nowrap !important; white-space: nowrap; transition: transform 0.3s ease; }';
        $html .= '.panth-menu-scroll-wrap > nav > ul > li { flex-shrink: 0; }';
        $html .= '.panth-menu-scroll-wrap > nav > ul > li > .panth-dropdown { white-space: normal; }';
        $html .= '.panth-menu-scroll-btn { position: absolute; top: 50%; transform: translateY(-50%); z-index: 50; width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #374151; transition: all 0.2s; opacity: 0; pointer-events: none; }';
        $html .= '.panth-menu-scroll-btn:hover { background: #f3f4f6; box-shadow: 0 4px 12px rgba(0,0,0,0.2); color: #111827; }';
        $html .= '.panth-menu-scroll-btn.visible { opacity: 1; pointer-events: auto; }';
        $html .= '.panth-menu-scroll-btn.scroll-left { left: -16px; }';
        $html .= '.panth-menu-scroll-btn.scroll-right { right: -16px; }';
        $html .= '.panth-menu-scroll-fade { position: absolute; top: 0; bottom: 0; width: 50px; pointer-events: none; z-index: 40; opacity: 0; transition: opacity 0.2s; }';
        $html .= '.panth-menu-scroll-fade.fade-left { left: 0; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); }';
        $html .= '.panth-menu-scroll-fade.fade-right { right: 0; background: linear-gradient(to left, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); }';
        $html .= '.panth-menu-scroll-fade.visible { opacity: 1; }';
        $html .= '</style>';

        // Container wrapper - EXACTLY like backend
        $html .= '<div class="megamenu-container ultimate-menu">';

        $html .= '<div class="panth-menu-scroll-wrap" id="panthMenuScrollWrap">';
        $html .= '<button type="button" class="panth-menu-scroll-btn scroll-left" id="panthMenuScrollLeft" aria-label="Scroll menu left">';
        $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
        $html .= '</button>';
        $html .= '<div class="panth-menu-scroll-fade fade-left" id="panthMenuFadeLeft"></div>';
        $html .= '<nav class="bg-white dark:bg-gray-800 rounded-xl p-4" x-data="panthMenuNav()" @mouseleave="closeAll()">';
        $html .= '<ul class="flex flex-wrap gap-4" id="panthMenuList">';

        foreach ($items as $item) {
            if (($item['level'] ?? 0) === 0 && ($item['is_active'] ?? 1)) {
                $html .= $this->renderRootItem($item, $isPreview);
            }
        }

        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '<div class="panth-menu-scroll-fade fade-right" id="panthMenuFadeRight"></div>';
        $html .= '<button type="button" class="panth-menu-scroll-btn scroll-right" id="panthMenuScrollRight" aria-label="Scroll menu right">';
        $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        $html .= '</button>';
        $html .= '</div>'; // Close panth-menu-scroll-wrap
        $html .= '</div>'; // Close megamenu-container

        // Add dynamic positioning script for nested dropdowns from level 3+
        $html .= '<script>';
        $html .= '(function() {';
        $html .= '  function checkDropdownPosition() {';
        $html .= '    const dropdowns = document.querySelectorAll(".panth-dropdown-nested");';
        $html .= '    dropdowns.forEach((dropdown) => {';
        $html .= '      let currentLevel = 1;';
        $html .= '      let parent = dropdown.parentElement;';
        $html .= '      while (parent && !parent.classList.contains("megamenu-container")) {';
        $html .= '        if (parent.classList.contains("panth-dropdown-nested")) {';
        $html .= '          currentLevel++;';
        $html .= '        }';
        $html .= '        parent = parent.parentElement;';
        $html .= '      }';
        $html .= '      if (currentLevel >= 3) {';
        $html .= '        const parentLi = dropdown.parentElement;';
        $html .= '        parentLi.addEventListener("mouseenter", function checkSpace() {';
        $html .= '          setTimeout(function() {';
        $html .= '            const rect = dropdown.getBoundingClientRect();';
        $html .= '            const viewportWidth = window.innerWidth;';
        $html .= '            const spaceRight = viewportWidth - rect.left;';
        $html .= '            const spaceLeft = rect.left;';
        $html .= '            const dropdownWidth = dropdown.offsetWidth;';
        $html .= '            const rightPercentage = (spaceRight / viewportWidth) * 100;';
        $html .= '            const leftPercentage = (spaceLeft / viewportWidth) * 100;';
        $html .= '            if (rightPercentage < 15 && leftPercentage > 15) {';
        $html .= '              dropdown.classList.add("open-left");';
        $html .= '            } else if (spaceRight < dropdownWidth && spaceLeft > dropdownWidth) {';
        $html .= '              dropdown.classList.add("open-left");';
        $html .= '            } else {';
        $html .= '              dropdown.classList.remove("open-left");';
        $html .= '            }';
        $html .= '          }, 10);';
        $html .= '        });';
        $html .= '      }';
        $html .= '    });';
        $html .= '  }';
        $html .= '  if (document.readyState === "loading") {';
        $html .= '    document.addEventListener("DOMContentLoaded", checkDropdownPosition);';
        $html .= '  } else {';
        $html .= '    checkDropdownPosition();';
        $html .= '  }';
        $html .= '})();';
        $html .= '</script>';

        // Alpine.js menu hover controller
        $html .= '<script>';
        $html .= 'function panthMenuNav() {';
        $html .= '  return {';
        $html .= '    openId: null,';
        $html .= '    closeTimer: null,';
        $html .= '    openMenu(id) {';
        $html .= '      clearTimeout(this.closeTimer);';
        $html .= '      if (this.openId !== null && this.openId !== id) {';
        $html .= '        this.removeMenuOpen(this.openId);';
        $html .= '      }';
        $html .= '      this.openId = id;';
        $html .= '      this.addMenuOpen(id);';
        $html .= '    },';
        $html .= '    closeMenu(id) {';
        $html .= '      this.closeTimer = setTimeout(() => {';
        $html .= '        if (this.openId === id) {';
        $html .= '          this.removeMenuOpen(id);';
        $html .= '          this.openId = null;';
        $html .= '        }';
        $html .= '      }, 150);';
        $html .= '    },';
        $html .= '    cancelClose() {';
        $html .= '      clearTimeout(this.closeTimer);';
        $html .= '    },';
        $html .= '    closeAll() {';
        $html .= '      clearTimeout(this.closeTimer);';
        $html .= '      if (this.openId !== null) {';
        $html .= '        this.removeMenuOpen(this.openId);';
        $html .= '        this.openId = null;';
        $html .= '      }';
        $html .= '    },';
        $html .= '    addMenuOpen(id) {';
        $html .= '      const li = this.$el.querySelector("[data-root-id=\'" + id + "\']");';
        $html .= '      if (li) li.classList.add("menu-open");';
        $html .= '    },';
        $html .= '    removeMenuOpen(id) {';
        $html .= '      const li = this.$el.querySelector("[data-root-id=\'" + id + "\']");';
        $html .= '      if (li) li.classList.remove("menu-open");';
        $html .= '    }';
        $html .= '  };';
        $html .= '}';
        $html .= '</script>';

        // Vanilla JS scroll controller for menu overflow (works for both Hyva and Luma)
        $html .= '<script>';
        $html .= '(function() {';
        $html .= '  function initMenuScroll() {';
        $html .= '    var wrap = document.getElementById("panthMenuScrollWrap");';
        $html .= '    var nav = wrap ? wrap.querySelector("nav") : null;';
        $html .= '    var ul = wrap ? wrap.querySelector("nav > ul") : null;';
        $html .= '    var btnL = document.getElementById("panthMenuScrollLeft");';
        $html .= '    var btnR = document.getElementById("panthMenuScrollRight");';
        $html .= '    var fadeL = document.getElementById("panthMenuFadeLeft");';
        $html .= '    var fadeR = document.getElementById("panthMenuFadeRight");';
        $html .= '    if (!ul || !nav || !btnL || !btnR) return;';
        $html .= '    var offset = 0;';
        $html .= '    function getMaxOffset() { return Math.max(0, ul.scrollWidth - nav.clientWidth); }';
        $html .= '    function update() {';
        $html .= '      var maxOff = getMaxOffset();';
        $html .= '      var hasOverflow = maxOff > 2;';
        $html .= '      btnL.classList.toggle("visible", hasOverflow && offset > 2);';
        $html .= '      btnR.classList.toggle("visible", hasOverflow && offset < maxOff - 2);';
        $html .= '      if (fadeL) fadeL.classList.toggle("visible", hasOverflow && offset > 2);';
        $html .= '      if (fadeR) fadeR.classList.toggle("visible", hasOverflow && offset < maxOff - 2);';
        $html .= '    }';
        $html .= '    btnL.addEventListener("click", function() { offset = Math.max(0, offset - 200); targetOff = offset; ul.style.transform = "translateX(-" + offset + "px)"; update(); });';
        $html .= '    btnR.addEventListener("click", function() { offset = Math.min(getMaxOffset(), offset + 200); targetOff = offset; ul.style.transform = "translateX(-" + offset + "px)"; update(); });';
        $html .= '    var animFrame = null, targetOff = 0;';
        $html .= '    function smoothTo() { var diff = targetOff - offset; if (Math.abs(diff) < 0.5) { offset = targetOff; animFrame = null; } else { offset += diff * 0.2; animFrame = requestAnimationFrame(smoothTo); } ul.style.transform = offset > 0 ? "translateX(-" + offset + "px)" : ""; update(); }';
        $html .= '    nav.addEventListener("wheel", function(e) { var m = getMaxOffset(); if (m <= 2) return; e.preventDefault(); targetOff = Math.max(0, Math.min(m, (animFrame ? targetOff : offset) + (e.deltaY !== 0 ? e.deltaY : e.deltaX) * 0.5)); if (!animFrame) animFrame = requestAnimationFrame(smoothTo); }, { passive: false });';
        $html .= '    window.addEventListener("resize", function() { var m = getMaxOffset(); if (offset > m) { offset = m; ul.style.transform = offset > 0 ? "translateX(-" + offset + "px)" : ""; } update(); });';
        $html .= '    update();';
        $html .= '  }';
        $html .= '  if (document.readyState === "loading") {';
        $html .= '    document.addEventListener("DOMContentLoaded", initMenuScroll);';
        $html .= '  } else {';
        $html .= '    initMenuScroll();';
        $html .= '  }';
        $html .= '})();';
        $html .= '</script>';

        $html .= '</div>'; // Close panthMenuContent
        return $html;
    }

    /**
     * Render root level item
     */
    protected function renderRootItem($item, $isPreview = false)
    {
        $title = $this->escaper->escapeHtml($item['title'] ?? '');
        $url = $this->escaper->escapeUrl($item['url'] ?? '#');
        $target = $this->escaper->escapeHtmlAttr($item['target'] ?? '_self');
        $icon = $item['icon'] ?? '';
        $iconLibrary = $item['icon_library'] ?? 'fontawesome';
        $bgColor = $item['background_color'] ?? '';
        $textColor = $item['text_color'] ?? '';
        $hoverEffect = $item['hover_effect'] ?? 'fade';
        $cssClass = $item['css_class'] ?? '';
        $itemType = $item['item_type'] ?? 'custom';
        $cmsBlockId = $item['cms_block_id'] ?? null;

        // Build item style
        $itemStyle = '';
        if ($bgColor) {
            $itemStyle .= 'background-color: ' . $this->escaper->escapeHtmlAttr($bgColor) . '; ';
        }
        if ($textColor) {
            $itemStyle .= 'color: ' . $this->escaper->escapeHtmlAttr($textColor) . ';';
        }

        // Get children
        $children = $this->getChildren($item);
        $hasChildren = count($children) > 0;

        // Hover effect class
        $hoverClass = 'hover-' . $this->escaper->escapeHtmlAttr($hoverEffect);

        // Icon HTML
        $iconHtml = $this->renderIcon($icon, $iconLibrary);

        // Determine tag and attributes
        $hasUrl = $url && trim($url) !== '' && $url !== '#';
        $tagName = ($itemType === 'cms_block' && !$hasUrl) ? 'span' : 'a';
        $hrefAttr = $tagName === 'a' ? 'href="' . $url . '" target="' . $target . '"' : '';

        $html = '';
        $itemId = (int)($item['item_id'] ?? 0);
        $liClass = $hasChildren ? 'relative group has-dropdown' : 'relative group';
        if ($hasChildren) {
            $html .= '<li class="' . $liClass . '" data-root-id="' . $itemId . '" @mouseenter="openMenu(' . $itemId . ')" @mouseleave="closeMenu(' . $itemId . ')">';
        } else {
            $html .= '<li class="' . $liClass . '">';
        }
        $html .= '<' . $tagName . ' ' . $hrefAttr . ' class="flex items-center gap-2 px-4 py-3 rounded-lg ' . $hoverClass . ' ' . $cssClass . ' font-semibold text-base cursor-pointer" style="' . $itemStyle . '">';
        $html .= '<span class="flex items-center gap-2">';
        if ($iconHtml) {
            $html .= $iconHtml . ' ';
        }
        $html .= $title;
        $html .= '</span>';
        // Add down arrow for items with children
        if ($hasChildren) {
            $html .= '<span class="menu-arrow">▼</span>';
        }
        $html .= '</' . $tagName . '>';

        // Render children dropdown
        if ($hasChildren) {
            $submenuCols = $item['submenu_columns'] ?? 1;
            $minWidthPx = $submenuCols > 1 ? min($submenuCols * 250, 1200) : 250;

            // If CMS block AND children: show CMS block above, then children below
            if ($itemType === 'cms_block' && $cmsBlockId) {
                // Outer dropdown wrapper
                $html .= '<div class="panth-dropdown absolute left-0 top-full mt-2 bg-white dark:bg-gray-800 shadow-2xl rounded-xl p-4 border-2 border-gray-200 dark:border-gray-700" @mouseenter="cancelClose()" style="min-width: ' . $minWidthPx . 'px; max-width: 1200px;">';

                // CMS block section with grid layout
                $cmsGridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-6' : '';
                $html .= '<div class="cms-block-section mb-4 pb-4 border-b-2 border-gray-300 dark:border-gray-600 ' . $cmsGridClass . '">';
                $html .= $this->renderCmsBlock($cmsBlockId, $isPreview);
                $html .= '</div>';

                // Children section - visible and clear
                $html .= '<div class="children-section">';
                $html .= '<div class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Browse Categories</div>';
                $childGridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : 'flex flex-col gap-2';
                $html .= '<div class="' . $childGridClass . '">';

                foreach ($children as $child) {
                    $html .= $this->renderChildItem($child, $isPreview, false); // Don't wrap in <li>
                }

                $html .= '</div>'; // Close children grid
                $html .= '</div>'; // Close children-section
                $html .= '</div>'; // Close outer dropdown
            } else {
                // No CMS block, use grid for children if needed
                $gridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : '';
                $html .= '<ul class="panth-dropdown absolute left-0 top-full mt-2 bg-white dark:bg-gray-800 shadow-2xl rounded-xl p-3 border-2 border-gray-200 dark:border-gray-700 ' . $gridClass . '" @mouseenter="cancelClose()" style="min-width: ' . $minWidthPx . 'px;">';

                foreach ($children as $child) {
                    $html .= $this->renderChildItem($child, $isPreview);
                }

                $html .= '</ul>';
            }
        } elseif ($itemType === 'cms_block' && $cmsBlockId) {
            // Show CMS block content in dropdown if no children
            // Apply column layout to CMS block wrapper if submenu_columns > 1
            $submenuCols = $item['submenu_columns'] ?? 1;
            $cmsGridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : '';
            $cmsMinWidthPx = $submenuCols > 1 ? min($submenuCols * 250, 1200) : 300;
            $html .= '<div class="panth-dropdown absolute left-0 top-full mt-2 bg-white dark:bg-gray-800 shadow-2xl rounded-xl p-3 border-2 border-gray-200 dark:border-gray-700 ' . $cmsGridClass . '" @mouseenter="cancelClose()" style="min-width: ' . $cmsMinWidthPx . 'px; max-width: 1200px;">';
            $html .= $this->renderCmsBlock($cmsBlockId, $isPreview);
            $html .= '</div>';
        }

        $html .= '</li>';
        return $html;
    }

    /**
     * Render child item (recursive for nested items)
     */
    protected function renderChildItem($child, $isPreview = false, $wrapInLi = true)
    {
        $title = $this->escaper->escapeHtml($child['title'] ?? '');
        $url = $this->escaper->escapeUrl($child['url'] ?? '#');
        $target = $this->escaper->escapeHtmlAttr($child['target'] ?? '_self');
        $icon = $child['icon'] ?? '';
        $iconLibrary = $child['icon_library'] ?? 'fontawesome';
        $bgColor = $child['background_color'] ?? '';
        $textColor = $child['text_color'] ?? '';
        $hoverEffect = $child['hover_effect'] ?? 'fade';
        $cssClass = $child['css_class'] ?? '';
        $itemType = $child['item_type'] ?? 'custom';
        $cmsBlockId = $child['cms_block_id'] ?? null;

        // Build item style
        $itemStyle = '';
        if ($bgColor) {
            $itemStyle .= 'background-color: ' . $this->escaper->escapeHtmlAttr($bgColor) . '; ';
        }
        if ($textColor) {
            $itemStyle .= 'color: ' . $this->escaper->escapeHtmlAttr($textColor) . ';';
        }

        // Get grandchildren
        $grandchildren = $this->getChildren($child);
        $hasGrandchildren = count($grandchildren) > 0;

        // Hover effect class
        $hoverClass = 'hover-' . $this->escaper->escapeHtmlAttr($hoverEffect);

        // Icon HTML
        $iconHtml = $this->renderIcon($icon, $iconLibrary);

        // Determine tag and attributes
        $hasUrl = $url && trim($url) !== '' && $url !== '#';
        $tagName = ($itemType === 'cms_block' && !$hasUrl) ? 'span' : 'a';
        $hrefAttr = $tagName === 'a' ? 'href="' . $url . '" target="' . $target . '"' : '';

        $html = '';
        if ($wrapInLi) {
            $html .= '<li class="relative ' . ($hasGrandchildren || $itemType === 'cms_block' ? 'group' : '') . '">';
        } else {
            $html .= '<div class="relative ' . ($hasGrandchildren || $itemType === 'cms_block' ? 'group' : '') . '">';
        }
        $html .= '<' . $tagName . ' ' . $hrefAttr . ' class="flex items-center justify-between px-4 py-2.5 rounded-lg ' . $hoverClass . ' transition-all text-base ' . $cssClass . ' cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" style="' . $itemStyle . '">';
        $html .= '<span class="flex items-center gap-2">';
        if ($iconHtml) {
            $html .= $iconHtml . ' ';
        }
        $html .= $title;
        $html .= '</span>';
        if ($hasGrandchildren || $itemType === 'cms_block') {
            $html .= '<i class="fa-solid fa-chevron-right text-gray-500 text-xs"></i>';
        }
        $html .= '</' . $tagName . '>';

        if ($hasGrandchildren) {
            $submenuCols = $child['submenu_columns'] ?? 1;
            $gridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : '';
            $minWidthPx = $submenuCols > 1 ? min($submenuCols * 220, 1000) : 220;

            $html .= '<ul class="panth-dropdown-nested absolute left-full top-0 ml-2 bg-white dark:bg-gray-800 shadow-2xl rounded-xl p-3 border-2 border-gray-200 dark:border-gray-700 ' . $gridClass . '" style="min-width: ' . $minWidthPx . 'px;">';

            // Show CMS block content first if it's a CMS block type
            if ($itemType === 'cms_block' && $cmsBlockId) {
                // Apply column layout to CMS block content if submenu_columns > 1
                $cmsGridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : '';
                $html .= '<li class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 ' . $cmsGridClass . '">';
                $html .= $this->renderCmsBlock($cmsBlockId, $isPreview);
                $html .= '</li>';
            }

            foreach ($grandchildren as $grandchild) {
                $html .= $this->renderChildItem($grandchild, $isPreview);
            }
            $html .= '</ul>';
        } elseif ($itemType === 'cms_block' && $cmsBlockId) {
            // Show CMS block content in dropdown if no grandchildren
            // Apply column layout to CMS block wrapper if submenu_columns > 1
            $submenuCols = $child['submenu_columns'] ?? 1;
            $cmsGridClass = $submenuCols > 1 ? 'grid grid-cols-' . $submenuCols . ' gap-4' : '';
            $cmsMinWidthPx = $submenuCols > 1 ? min($submenuCols * 220, 1000) : 300;
            $html .= '<div class="panth-dropdown-nested absolute left-full top-0 ml-2 bg-white dark:bg-gray-800 shadow-2xl rounded-xl p-3 border-2 border-gray-200 dark:border-gray-700 ' . $cmsGridClass . '" style="min-width: ' . $cmsMinWidthPx . 'px; max-width: 1000px;">';
            $html .= $this->renderCmsBlock($cmsBlockId, $isPreview);
            $html .= '</div>';
        }

        if ($wrapInLi) {
            $html .= '</li>';
        } else {
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Get children items from parent
     */
    protected function getChildren($parent)
    {
        $children = [];
        $parentId = $parent['temp_id'] ?? $parent['item_id'] ?? null;

        if (!$parentId) {
            return $children;
        }

        if (isset($parent['children']) && is_array($parent['children'])) {
            foreach ($parent['children'] as $child) {
                if (($child['is_active'] ?? 1)) {
                    $children[] = $child;
                }
            }
        }

        return $children;
    }

    /**
     * Get common CSS styles for dropdown and hover effects
     * EXACT same styles for both admin preview and frontend
     */
    public function getCommonStyles()
    {
        return <<<CSS
/* Typography - Significantly increased font sizes */
.megamenu-container,
.megamenu-container * {
    font-size: 18px !important;
}

.megamenu-container > nav > ul > li > a,
.megamenu-container > nav > ul > li > span {
    font-size: 22px !important;
    font-weight: 700 !important;
    padding: 1rem 1.5rem !important;
}

.megamenu-container .panth-dropdown > li > a,
.megamenu-container .panth-dropdown > li > span {
    font-size: 18px !important;
    font-weight: 500 !important;
    padding: 0.75rem 1.25rem !important;
}

.megamenu-container .panth-dropdown-nested > li > a,
.megamenu-container .panth-dropdown-nested > li > span {
    font-size: 17px !important;
    font-weight: 500 !important;
    padding: 0.65rem 1.15rem !important;
}

/* Dropdown styles - EXACT same for admin preview and frontend */
.panth-dropdown {
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.3s ease-out, visibility 0.3s, transform 0.3s ease-out;
    max-height: none;
    overflow: visible;
    z-index: 1000;
    position: absolute;
    left: 0;
    top: 100%;
}

.panth-dropdown-nested {
    opacity: 0;
    visibility: hidden;
    transform: translateX(-10px);
    transition: opacity 0.3s ease-out, visibility 0.3s, transform 0.3s ease-out;
    max-height: none;
    overflow: visible;
    z-index: 1200;
    position: absolute;
    left: 100%;
    top: 0;
}

.group:hover > .panth-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.group:hover > .panth-dropdown-nested {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

.panth-dropdown .group:hover > .panth-dropdown-nested {
    z-index: 1300;
}

/* Desktop hover effects */
@media (min-width: 769px) {
    .panth-dropdown li:hover > a,
    .panth-dropdown li:hover > span {
        background: rgba(99, 102, 241, 0.05);
    }

    .panth-dropdown-nested li:hover > a,
    .panth-dropdown-nested li:hover > span {
        background: rgba(99, 102, 241, 0.05);
    }
}

/* Hover effects - EXACT same for admin preview and frontend */
.hover-fade:hover {
    opacity: 0.7 !important;
    transition: opacity 0.3s;
}

.hover-slide:hover {
    transform: translateY(-4px) !important;
    transition: transform 0.3s;
}

.hover-zoom:hover {
    transform: scale(1.05) !important;
    transition: transform 0.3s;
}

.hover-underline:hover {
    text-decoration: underline !important;
    text-underline-offset: 4px;
}

.hover-glow:hover {
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.5) !important;
    transition: box-shadow 0.3s;
}
CSS;
    }

    /**
     * Render desktop menu with scoped CSS for Luma theme
     * Prevents conflicts with Luma theme and other modules
     */
    public function renderDesktopMenuLuma($items, $isPreview = false)
    {
        // Get the standard Hyva rendering
        $html = $this->renderDesktopMenu($items, $isPreview);

        // Scope ALL CSS to .megamenu-container to prevent conflicts in Luma
        $html = $this->scopeCssToMegamenu($html);

        return $html;
    }

    /**
     * Scope CSS selectors to .megamenu-container
     * Prevents Luma CSS conflicts while keeping Hyva unchanged
     */
    private function scopeCssToMegamenu($html)
    {
        // Extract the <style> content
        if (preg_match('/<style>(.*?)<\/style>/s', $html, $matches)) {
            $css = $matches[1];

            // Scope selectors to .megamenu-container (except html, body, #panthMenuContent)
            $cssLines = explode(';', $css);
            $scopedCss = '';

            foreach ($cssLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Extract selector and rules
                if (preg_match('/^([^{]+)\{(.+)$/s', $line, $parts)) {
                    $selector = trim($parts[1]);
                    $rules = trim($parts[2]);

                    // Don't scope html, body, #panthMenuContent, or @media/@keyframes
                    if (strpos($selector, 'html') === 0 ||
                        strpos($selector, 'body') === 0 ||
                        strpos($selector, '#panthMenuContent') === 0 ||
                        strpos($selector, '@') === 0) {
                        $scopedCss .= $selector . ' { ' . $rules . ' } ';
                    } else {
                        // Scope to .megamenu-container
                        $scopedCss .= '.megamenu-container ' . $selector . ' { ' . $rules . ' } ';
                    }
                } else {
                    $scopedCss .= $line . '; ';
                }
            }

            // Replace in HTML
            $html = str_replace($css, $scopedCss, $html);
        }

        // Remove Alpine.js x-init that sets overflow-x (causes issues in Luma)
        $html = preg_replace('/x-init="[^"]*"/', '', $html);

        // Remove html, body overflow-x rule for Luma (causes vertical scrollbar)
        $html = preg_replace('/html,\s*body\s*\{[^}]*overflow-x[^}]*\}/', '', $html);

        return $html;
    }
}
