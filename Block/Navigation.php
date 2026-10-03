<?php
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
    protected $request;

    protected $breadcrumbData = [];

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

    public function isMobile(): bool
    {
        $userAgent = $this->request->getHeader('User-Agent');

        if (!$userAgent) {
            return false;
        }

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

    public function shouldShowMobileMenu(): bool
    {
        return $this->menuHelper->isMobileEnabled() && $this->isMobile();
    }

    public function getMobileBreakpoint(): int
    {
        return $this->menuHelper->getMobileBreakpoint();
    }

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

        if ($this->menuHelper->isMobileEnabled()) {
            $html .= $this->renderMobileToggle();
        }

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

    protected function renderMobileToggle(): string
    {
        $html = '<button type="button" class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">';
        $html .= '<span class="menu-toggle-icon"></span>';
        $html .= '<span class="menu-toggle-text">' . __('Menu') . '</span>';
        $html .= '</button>';

        return $html;
    }

    protected function renderNavigationItem($item, int $level = 0): string
    {
        if (!is_array($item) && !$item instanceof ItemInterface) {
            return '';
        }

        if (!$this->getItemField($item, 'is_active', 'getIsActive', true)) {
            return '';
        }

        $isActive = $this->isCurrentPage($item);
        $classes = $this->menuViewModel->getItemClass($item);

        if ($isActive) {
            $classes .= ' active current';
        }

        $html = '<li class="' . $this->escapeHtmlAttr($classes) . '" role="none">';

        if ($this->menuViewModel->shouldShowContent($item)) {
            $html .= $this->renderContent($item);
        } else {
            $html .= $this->renderNavigationLink($item, $isActive);
        }

        if (!empty($this->getItemChildren($item))) {
            $html .= $this->renderNavigationChildren($item, $level + 1);
        }

        $html .= '</li>';

        return $html;
    }

    protected function renderNavigationLink($item, bool $isActive = false): string
    {
        $url = $this->menuViewModel->getItemUrl($item);
        $title = $this->escapeHtml((string) $this->getItemField($item, 'title', 'getTitle', ''));
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

        if (!empty($this->getItemChildren($item))) {
            $attributes[] = 'aria-haspopup="true"';
            $attributes[] = 'aria-expanded="false"';
        }

        if ($isActive) {
            $attributes[] = 'aria-current="page"';
        }

        $html = '<a ' . implode(' ', $attributes) . '>';
        $html .= $this->menuViewModel->getItemTitleWithIcon($item);

        if (!empty($this->getItemChildren($item))) {
            $html .= '<span class="submenu-indicator" aria-hidden="true"></span>';
        }

        $html .= '</a>';

        return $html;
    }

    protected function renderNavigationChildren($item, int $level): string
    {
        $children = $this->getItemChildren($item);

        if (empty($children)) {
            return '';
        }

        $label = (string) $this->getItemField($item, 'title', 'getTitle', '');
        $html = '<ul class="submenu level-' . $level . '" role="menu" aria-label="'
            . $this->escapeHtmlAttr($label) . '">';

        foreach ($children as $child) {
            $html .= $this->renderNavigationItem($child, $level);
        }

        $html .= '</ul>';

        return $html;
    }

    protected function isCurrentPage($item): bool
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        return $this->menuViewModel->isActive($item, $currentUrl);
    }

    public function getBreadcrumbData(string $identifier): array
    {
        if (isset($this->breadcrumbData[$identifier])) {
            return $this->breadcrumbData[$identifier];
        }

        $menuTree = $this->getMenuTree($identifier);
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $breadcrumbs = [];

        $activeItem = $this->findActiveItem($menuTree, $currentUrl);

        if ($activeItem) {
            $breadcrumbs = $this->menuViewModel->getBreadcrumbTrail($activeItem, $menuTree);
        }

        $this->breadcrumbData[$identifier] = $breadcrumbs;

        return $breadcrumbs;
    }

    protected function findActiveItem(array $items, string $currentUrl)
    {
        foreach ($items as $item) {
            if (!is_array($item) && !$item instanceof ItemInterface) {
                continue;
            }

            if ($this->menuViewModel->isActive($item, $currentUrl)) {
                return $item;
            }

            if (!empty($this->getItemChildren($item))) {
                $found = $this->findActiveItem($this->getItemChildren($item), $currentUrl);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

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
            $itemTitle = (string) $this->getItemField($item, 'title', 'getTitle', '');
            $isLast = ($index === $count);

            $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">';

            if (!$isLast) {
                $url = $this->menuViewModel->getItemUrl($item);
                $html .= '<a href="' . $this->escapeUrl($url) . '">';
                $html .= $this->escapeHtml($itemTitle);
                $html .= '</a>';
            } else {
                $html .= '<span>' . $this->escapeHtml($itemTitle) . '</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

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

    public function getNavigationDataAttributes(): array
    {
        return [
            'data-mobile-enabled' => $this->menuHelper->isMobileEnabled() ? 'true' : 'false',
            'data-mobile-breakpoint' => $this->getMobileBreakpoint(),
            'data-animation-enabled' => $this->menuHelper->isAnimationEnabled() ? 'true' : 'false',
            'data-animation-duration' => $this->menuHelper->getAnimationDuration()
        ];
    }

    protected function getItemField($item, string $key, string $getter, $default = null)
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }

        return $item->$getter();
    }

    protected function getItemChildren($item): array
    {
        if (is_array($item)) {
            return is_array($item['children'] ?? null) ? $item['children'] : [];
        }

        return $item->hasChildren() ? (array) $item->getChildren() : [];
    }

    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo[] = 'navigation';
        $cacheKeyInfo[] = $this->isMobile() ? 'mobile' : 'desktop';

        return $cacheKeyInfo;
    }
}
