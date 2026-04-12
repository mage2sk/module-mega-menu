<?php
/**
 * Navigation Block
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Panth\MegaMenu\Helper\MenuRenderer;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;
use Psr\Log\LoggerInterface;

class Navigation extends Menu
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var array
     */
    protected $breadcrumbData = [];

    /**
     * @var string
     */
    protected $_template = 'Panth_MegaMenu::navigation.phtml';

    public function __construct(
        Context $context,
        MenuRepositoryInterface $menuRepository,
        ItemRepositoryInterface $itemRepository,
        StoreManagerInterface $storeManager,
        MenuHelper $menuHelper,
        MenuViewModel $menuViewModel,
        LoggerInterface $logger,
        ThemeHelper $themeHelper,
        MenuRenderer $menuRenderer,
        Http $request,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $menuRepository,
            $itemRepository,
            $storeManager,
            $menuHelper,
            $menuViewModel,
            $logger,
            $themeHelper,
            $menuRenderer,
            $data
        );
        $this->request = $request;
    }

    /**
     * Check if device is mobile
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        $userAgent = $this->request->getHeader('User-Agent');

        if (!$userAgent) {
            return false;
        }

        // Basic mobile detection
        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod',
            'BlackBerry', 'Windows Phone', 'webOS'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if mobile menu should be shown
     *
     * @return bool
     */
    public function shouldShowMobileMenu(): bool
    {
        return $this->menuHelper->isMobileEnabled() && $this->isMobile();
    }

    /**
     * Get mobile breakpoint
     *
     * @return int
     */
    public function getMobileBreakpoint(): int
    {
        return $this->menuHelper->getMobileBreakpoint();
    }

    /**
     * Render navigation with mobile support
     *
     * @param string $identifier
     * @param string $cssClass
     * @return string
     */
    public function getNavigationHtml(string $identifier, string $cssClass = ''): string
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $menu = $this->getMenu($identifier);

        if (!$menu) {
            return '';
        }

        $menuTree = $this->getMenuTree($identifier);

        if (empty($menuTree)) {
            return '';
        }

        $classes = ['megamenu-navigation', 'menu-' . $identifier];
        if ($cssClass) {
            $classes[] = $cssClass;
        }

        if ($this->shouldShowMobileMenu()) {
            $classes[] = 'mobile-menu';
        }

        $html = '<nav class="' . $this->escapeHtmlAttr(implode(' ', $classes)) . '" role="navigation" aria-label="Main navigation">';

        // Add mobile toggle button
        if ($this->menuHelper->isMobileEnabled()) {
            $html .= $this->renderMobileToggle();
        }

        // Render menu
        $html .= '<div class="menu-wrapper">';
        $html .= '<ul class="menu-root level-0" role="menubar">';

        foreach ($menuTree as $item) {
            $html .= $this->renderNavigationItem($item, 0);
        }

        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render mobile toggle button
     *
     * @return string
     */
    protected function renderMobileToggle(): string
    {
        $html = '<button type="button" class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">';
        $html .= '<span class="menu-toggle-icon"></span>';
        $html .= '<span class="menu-toggle-text">' . __('Menu') . '</span>';
        $html .= '</button>';

        return $html;
    }

    /**
     * Render navigation item with accessibility attributes
     *
     * @param ItemInterface $item
     * @param int $level
     * @return string
     */
    protected function renderNavigationItem(ItemInterface $item, int $level = 0): string
    {
        if (!$item->getIsActive()) {
            return '';
        }

        $isActive = $this->isCurrentPage($item);
        $classes = $this->menuViewModel->getItemClass($item);

        if ($isActive) {
            $classes .= ' active current';
        }

        $html = '<li class="' . $this->escapeHtmlAttr($classes) . '" role="none">';

        // Render link with accessibility
        if ($this->menuViewModel->shouldShowContent($item)) {
            $html .= $this->renderContent($item);
        } else {
            $html .= $this->renderNavigationLink($item, $isActive);
        }

        // Render children if any
        if ($item->hasChildren()) {
            $html .= $this->renderNavigationChildren($item, $level + 1);
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render navigation link with accessibility
     *
     * @param ItemInterface $item
     * @param bool $isActive
     * @return string
     */
    protected function renderNavigationLink(ItemInterface $item, bool $isActive = false): string
    {
        $url = $this->menuViewModel->getItemUrl($item);
        $title = $this->escapeHtml($item->getTitle());
        $target = $this->menuViewModel->getLinkTarget($item);
        $rel = $this->menuViewModel->getLinkRel($item);

        $attributes = [
            'href="' . $this->escapeUrl($url) . '"',
            'title="' . $title . '"',
            'target="' . $target . '"',
            'role="menuitem"'
        ];

        if ($rel) {
            $attributes[] = 'rel="' . $this->escapeHtmlAttr($rel) . '"';
        }

        if ($item->hasChildren()) {
            $attributes[] = 'aria-haspopup="true"';
            $attributes[] = 'aria-expanded="false"';
        }

        if ($isActive) {
            $attributes[] = 'aria-current="page"';
        }

        $html = '<a ' . implode(' ', $attributes) . '>';
        $html .= $this->menuViewModel->getItemTitleWithIcon($item);

        if ($item->hasChildren()) {
            $html .= '<span class="submenu-indicator" aria-hidden="true"></span>';
        }

        $html .= '</a>';

        return $html;
    }

    /**
     * Render navigation children with accessibility
     *
     * @param ItemInterface $item
     * @param int $level
     * @return string
     */
    protected function renderNavigationChildren(ItemInterface $item, int $level): string
    {
        $children = $item->getChildren();

        if (empty($children)) {
            return '';
        }

        $html = '<ul class="submenu level-' . $level . '" role="menu" aria-label="' . $this->escapeHtmlAttr($item->getTitle()) . '">';

        foreach ($children as $child) {
            $html .= $this->renderNavigationItem($child, $level);
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Check if item represents current page
     *
     * @param ItemInterface $item
     * @return bool
     */
    protected function isCurrentPage(ItemInterface $item): bool
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        return $this->menuViewModel->isActive($item, $currentUrl);
    }

    /**
     * Get breadcrumb data
     *
     * @param string $identifier
     * @return array
     */
    public function getBreadcrumbData(string $identifier): array
    {
        if (isset($this->breadcrumbData[$identifier])) {
            return $this->breadcrumbData[$identifier];
        }

        $menuTree = $this->getMenuTree($identifier);
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $breadcrumbs = [];

        // Find active item in tree
        $activeItem = $this->findActiveItem($menuTree, $currentUrl);

        if ($activeItem) {
            $breadcrumbs = $this->menuViewModel->getBreadcrumbTrail($activeItem, $menuTree);
        }

        $this->breadcrumbData[$identifier] = $breadcrumbs;

        return $breadcrumbs;
    }

    /**
     * Find active item in menu tree
     *
     * @param array $items
     * @param string $currentUrl
     * @return ItemInterface|null
     */
    protected function findActiveItem(array $items, string $currentUrl): ?ItemInterface
    {
        foreach ($items as $item) {
            if ($this->menuViewModel->isActive($item, $currentUrl)) {
                return $item;
            }

            if ($item->hasChildren()) {
                $found = $this->findActiveItem($item->getChildren(), $currentUrl);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Render breadcrumb trail
     *
     * @param string $identifier
     * @return string
     */
    public function renderBreadcrumb(string $identifier): string
    {
        $breadcrumbs = $this->getBreadcrumbData($identifier);

        if (empty($breadcrumbs)) {
            return '';
        }

        $html = '<nav class="breadcrumb" aria-label="Breadcrumb">';
        $html .= '<ol class="breadcrumb-list">';

        $count = count($breadcrumbs);
        $index = 0;

        foreach ($breadcrumbs as $item) {
            $index++;
            $isLast = ($index === $count);

            $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">';

            if (!$isLast) {
                $url = $this->menuViewModel->getItemUrl($item);
                $html .= '<a href="' . $this->escapeUrl($url) . '">';
                $html .= $this->escapeHtml($item->getTitle());
                $html .= '</a>';
            } else {
                $html .= '<span>' . $this->escapeHtml($item->getTitle()) . '</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Get navigation CSS classes
     *
     * @return string
     */
    public function getNavigationClasses(): string
    {
        $classes = ['megamenu-navigation'];

        if ($this->shouldShowMobileMenu()) {
            $classes[] = 'mobile-active';
        }

        if ($this->menuHelper->isAnimationEnabled()) {
            $classes[] = 'animated';
            $classes[] = 'animation-duration-' . $this->menuHelper->getAnimationDuration();
        }

        return implode(' ', $classes);
    }

    /**
     * Get navigation data attributes for JavaScript
     *
     * @return array
     */
    public function getNavigationDataAttributes(): array
    {
        return [
            'data-mobile-enabled' => $this->menuHelper->isMobileEnabled() ? 'true' : 'false',
            'data-mobile-breakpoint' => $this->getMobileBreakpoint(),
            'data-animation-enabled' => $this->menuHelper->isAnimationEnabled() ? 'true' : 'false',
            'data-animation-duration' => $this->menuHelper->getAnimationDuration()
        ];
    }

    /**
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo[] = 'navigation';
        $cacheKeyInfo[] = $this->isMobile() ? 'mobile' : 'desktop';

        return $cacheKeyInfo;
    }
}
