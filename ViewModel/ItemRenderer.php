<?php
/**
 * Item Renderer ViewModel - Theme-Agnostic Item Rendering
 *
 * Provides rendering logic for menu items that works with both Hyva (Alpine.js) and Luma (KnockoutJS) themes.
 * This ViewModel handles the presentation layer for individual menu items.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Psr\Log\LoggerInterface;

class ItemRenderer implements ArgumentInterface
{
    /**
     * Theme constants
     */
    const THEME_HYVA = 'hyva';
    const THEME_LUMA = 'luma';
    const THEME_AUTO = 'auto';

    /**
     * @var MenuHelper
     */
    private $menuHelper;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Menu
     */
    private $menuViewModel;

    /**
     * Constructor
     *
     * @param MenuHelper $menuHelper
     * @param FilterProvider $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     * @param LoggerInterface $logger
     * @param Menu $menuViewModel
     */
    public function __construct(
        MenuHelper $menuHelper,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        Escaper $escaper,
        LoggerInterface $logger,
        Menu $menuViewModel
    ) {
        $this->menuHelper = $menuHelper;
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->menuViewModel = $menuViewModel;
    }

    /**
     * Render complete menu item HTML
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    public function render(ItemInterface $item, string $theme = self::THEME_AUTO): string
    {
        if (!$this->menuViewModel->isItemVisible($item)) {
            return '';
        }

        $theme = $this->detectTheme($theme);

        $html = '<li class="' . $this->escaper->escapeHtmlAttr($this->menuViewModel->getItemClass($item)) . '"';
        $html .= $this->renderItemAttributes($item, $theme);
        $html .= '>';

        // Render link or content wrapper
        if ($this->menuViewModel->shouldShowContent($item)) {
            $html .= $this->renderContentWrapper($item, $theme);
        } else {
            $html .= $this->renderLink($item, $theme);
        }

        // Render children if any
        if ($this->menuViewModel->hasChildren($item)) {
            $html .= $this->renderChildrenContainer($item, $theme);
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render menu item icon
     *
     * @param ItemInterface $item
     * @return string
     */
    public function renderIcon(ItemInterface $item): string
    {
        if (!$this->menuHelper->showIcons()) {
            return '';
        }

        $iconClass = $item->getIconClass();
        if (!$iconClass) {
            return '';
        }

        return sprintf(
            '<i class="%s megamenu-icon" aria-hidden="true"></i>',
            $this->escaper->escapeHtmlAttr($iconClass)
        );
    }

    /**
     * Render menu item badge
     *
     * @param ItemInterface $item
     * @return string
     */
    public function renderBadge(ItemInterface $item): string
    {
        // Check if item has badge data in content or custom attribute
        $content = $item->getContent();
        if (!$content) {
            return '';
        }

        // Look for badge markup in content
        if (preg_match('/<badge>(.*?)<\/badge>/i', $content, $matches)) {
            $badgeText = $this->escaper->escapeHtml(trim($matches[1]));
            return sprintf(
                '<span class="megamenu-badge badge bg-primary text-white">%s</span>',
                $badgeText
            );
        }

        // Look for new badge indicator
        if (stripos($content, '[NEW]') !== false || stripos($content, '[new]') !== false) {
            return '<span class="megamenu-badge badge bg-success text-white">New</span>';
        }

        // Look for hot badge indicator
        if (stripos($content, '[HOT]') !== false || stripos($content, '[hot]') !== false) {
            return '<span class="megamenu-badge badge bg-danger text-white">Hot</span>';
        }

        // Look for sale badge indicator
        if (stripos($content, '[SALE]') !== false || stripos($content, '[sale]') !== false) {
            return '<span class="megamenu-badge badge bg-warning text-dark">Sale</span>';
        }

        return '';
    }

    /**
     * Render menu item image
     *
     * @param ItemInterface $item
     * @return string
     */
    public function renderImage(ItemInterface $item): string
    {
        if (!$this->menuHelper->showImages()) {
            return '';
        }

        $imageUrl = $this->menuViewModel->getImageUrl($item, $this->menuHelper->getImageSize());
        if (!$imageUrl) {
            return '';
        }

        $title = $this->escaper->escapeHtmlAttr($item->getTitle() ?? '');
        $lazyLoad = $this->menuHelper->isLazyLoadEnabled();

        $imgAttributes = [
            'src' => $lazyLoad ? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E' : $imageUrl,
            'alt' => $title,
            'class' => 'megamenu-image' . ($lazyLoad ? ' lazy' : ''),
            'loading' => 'lazy'
        ];

        if ($lazyLoad) {
            $imgAttributes['data-src'] = $imageUrl;
        }

        $html = '<span class="megamenu-image-wrapper">';
        $html .= '<img';
        foreach ($imgAttributes as $attr => $value) {
            $html .= sprintf(' %s="%s"', $attr, $this->escaper->escapeHtmlAttr($value));
        }
        $html .= ' />';
        $html .= '</span>';

        return $html;
    }

    /**
     * Render menu item description
     *
     * @param ItemInterface $item
     * @return string
     */
    public function renderDescription(ItemInterface $item): string
    {
        $content = $item->getContent();
        if (!$content) {
            return '';
        }

        // Look for description markup in content
        if (preg_match('/<description>(.*?)<\/description>/is', $content, $matches)) {
            $description = trim($matches[1]);

            try {
                $storeId = $this->storeManager->getStore()->getId();
                $filter = $this->filterProvider->getPageFilter();
                $filter->setStoreId($storeId);
                $description = $filter->filter($description);
            } catch (\Exception $e) {
                // Silently handle errors
            }

            return sprintf(
                '<span class="megamenu-description text-sm text-gray-600">%s</span>',
                $description
            );
        }

        return '';
    }

    /**
     * Render complete link with icon, title, badge, and description
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    public function renderLink(ItemInterface $item, string $theme = self::THEME_AUTO): string
    {
        $theme = $this->detectTheme($theme);
        $url = $this->menuViewModel->getItemUrl($item);
        $target = $this->menuViewModel->getLinkTarget($item);
        $rel = $this->menuViewModel->getLinkRel($item);

        $html = sprintf(
            '<a href="%s" class="megamenu-link" target="%s"',
            $this->escaper->escapeUrl($url),
            $this->escaper->escapeHtmlAttr($target)
        );

        if ($rel) {
            $html .= sprintf(' rel="%s"', $this->escaper->escapeHtmlAttr($rel));
        }

        // Add theme-specific attributes
        if ($theme === self::THEME_HYVA && $this->menuViewModel->hasChildren($item)) {
            $html .= ' @click.prevent="toggleSubmenu($event)"';
        } elseif ($theme === self::THEME_LUMA && $this->menuViewModel->hasChildren($item)) {
            $html .= ' data-bind="click: toggleSubmenu"';
        }

        $html .= '>';

        // Render components
        $html .= $this->renderImage($item);
        $html .= '<span class="megamenu-link-content">';
        $html .= $this->renderIcon($item);
        $html .= '<span class="megamenu-title">' . $this->escaper->escapeHtml($item->getTitle() ?? '') . '</span>';
        $html .= $this->renderBadge($item);

        // Add dropdown indicator for items with children
        if ($this->menuViewModel->hasChildren($item)) {
            $html .= $this->renderDropdownIndicator($item, $theme);
        }

        $html .= '</span>';
        $html .= $this->renderDescription($item);
        $html .= '</a>';

        return $html;
    }

    /**
     * Render dropdown indicator
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    public function renderDropdownIndicator(ItemInterface $item, string $theme = self::THEME_AUTO): string
    {
        $theme = $this->detectTheme($theme);

        $iconClass = 'megamenu-dropdown-icon';

        if ($theme === self::THEME_HYVA) {
            return sprintf(
                '<svg class="%s" :class="{\'rotate-180\': submenuOpen}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>',
                $this->escaper->escapeHtmlAttr($iconClass)
            );
        } else {
            return sprintf(
                '<svg class="%s" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>',
                $this->escaper->escapeHtmlAttr($iconClass)
            );
        }
    }

    /**
     * Render content wrapper for content-type items
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    private function renderContentWrapper(ItemInterface $item, string $theme): string
    {
        $html = '<div class="megamenu-content-wrapper"';

        if ($theme === self::THEME_HYVA) {
            $html .= ' x-data="{ contentExpanded: false }"';
        }

        $html .= '>';
        $html .= '<div class="megamenu-content-title">';
        $html .= $this->renderIcon($item);
        $html .= '<span>' . $this->escaper->escapeHtml($item->getTitle() ?? '') . '</span>';
        $html .= $this->renderBadge($item);
        $html .= '</div>';
        $html .= '<div class="megamenu-content-body">';
        $html .= $this->menuViewModel->renderItemContent($item);
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render children container
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    private function renderChildrenContainer(ItemInterface $item, string $theme): string
    {
        $children = $this->menuViewModel->getChildren($item);
        if (empty($children)) {
            return '';
        }

        $columns = $item->getColumns();
        $gridClass = $this->getGridClass($columns);

        $html = '<ul class="megamenu-submenu ' . $this->escaper->escapeHtmlAttr($gridClass) . '"';

        if ($theme === self::THEME_HYVA) {
            $html .= ' x-show="submenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"';
        } elseif ($theme === self::THEME_LUMA) {
            $html .= ' data-bind="visible: submenuOpen, css: { \'is-open\': submenuOpen }"';
        }

        $html .= '>';

        foreach ($children as $child) {
            $html .= $this->render($child, $theme);
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Render item attributes
     *
     * @param ItemInterface $item
     * @param string $theme
     * @return string
     */
    private function renderItemAttributes(ItemInterface $item, string $theme): string
    {
        $attributes = [];

        // Data attributes
        $attributes[] = sprintf('data-item-id="%d"', $item->getItemId());
        $attributes[] = sprintf('data-level="%d"', $item->getLevel());

        if ($this->menuViewModel->hasChildren($item)) {
            $attributes[] = 'data-has-children="true"';
        }

        // Theme-specific attributes
        if ($theme === self::THEME_HYVA && $this->menuViewModel->hasChildren($item)) {
            $attributes[] = 'x-data="{ submenuOpen: false }"';
            $attributes[] = '@mouseenter="submenuOpen = true"';
            $attributes[] = '@mouseleave="submenuOpen = false"';
        } elseif ($theme === self::THEME_LUMA && $this->menuViewModel->hasChildren($item)) {
            $attributes[] = 'data-bind="css: { \'is-open\': submenuOpen }, event: { mouseenter: openSubmenu, mouseleave: closeSubmenu }"';
        }

        return ' ' . implode(' ', $attributes);
    }

    /**
     * Get grid class based on columns
     *
     * @param int $columns
     * @return string
     */
    private function getGridClass(int $columns): string
    {
        if ($columns <= 1) {
            return '';
        }

        // Return Tailwind grid classes
        return sprintf('grid grid-cols-%d gap-4', min($columns, 4));
    }

    /**
     * Detect theme automatically
     *
     * @param string $theme
     * @return string
     */
    private function detectTheme(string $theme): string
    {
        if ($theme !== self::THEME_AUTO) {
            return $theme;
        }

        // Try to detect Hyva theme
        // This is a simple detection - can be enhanced
        if (class_exists('\Hyva\Theme\ViewModel\HeroiconsSolid')) {
            return self::THEME_HYVA;
        }

        return self::THEME_LUMA;
    }

    /**
     * Get item wrapper classes
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getWrapperClasses(ItemInterface $item): string
    {
        $classes = ['megamenu-item-wrapper'];

        if ($this->menuViewModel->isTopLevel($item)) {
            $classes[] = 'megamenu-item-wrapper--top-level';
        }

        if ($this->menuViewModel->hasChildren($item)) {
            $classes[] = 'megamenu-item-wrapper--has-children';
        }

        $classes[] = 'megamenu-item-wrapper--level-' . $item->getLevel();
        $classes[] = 'megamenu-item-wrapper--' . $item->getItemType();

        return implode(' ', $classes);
    }

    /**
     * Check if item should render as mega dropdown
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function shouldRenderAsMegaDropdown(ItemInterface $item): bool
    {
        return $this->menuViewModel->isTopLevel($item)
            && $this->menuViewModel->hasChildren($item)
            && $item->getColumns() > 1;
    }

    /**
     * Get dropdown position classes
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getDropdownPositionClasses(ItemInterface $item): string
    {
        $classes = ['megamenu-dropdown'];

        if ($this->shouldRenderAsMegaDropdown($item)) {
            $classes[] = 'megamenu-dropdown--mega';
            $classes[] = 'megamenu-dropdown--columns-' . $item->getColumns();
        } else {
            $classes[] = 'megamenu-dropdown--simple';
        }

        return implode(' ', $classes);
    }
}
