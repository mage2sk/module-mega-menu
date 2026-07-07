<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Panth\MegaMenu\Helper\MenuRenderer;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;
use Psr\Log\LoggerInterface;

class Menu extends Template implements IdentityInterface
{
    protected $menuRepository;

    protected $itemRepository;

    protected $storeManager;

    protected $menuHelper;

    protected $menuViewModel;

    protected $logger;

    protected $themeHelper;

    protected $menuRenderer;

    protected $menu;

    protected $menuTree;

    protected $_template = 'Panth_MegaMenu::menu.phtml';

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
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->menuRepository = $menuRepository;
        $this->itemRepository = $itemRepository;
        $this->storeManager = $storeManager;
        $this->menuHelper = $menuHelper;
        $this->menuViewModel = $menuViewModel;
        $this->logger = $logger;
        $this->themeHelper = $themeHelper;
        $this->menuRenderer = $menuRenderer;
    }

    public function getMenu(string $identifier): ?MenuInterface
    {
        if ($this->menu !== null && $this->menu->getIdentifier() === $identifier) {
            return $this->menu;
        }

        try {
            $this->menu = $this->menuRepository->getByIdentifier($identifier, null);

            if (!$this->menu->getIsActive()) {
                return null;
            }

            return $this->menu;
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMenuTree($menuIdentifier): array
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        try {
            if (is_string($menuIdentifier)) {
                $menu = $this->getMenu($menuIdentifier);
                if (!$menu) {
                    return [];
                }
            } else {
                $menu = $this->getMenu($menuIdentifier);
                if (!$menu) {
                    return [];
                }
            }

            $itemsJson = $menu->getItemsJson();

            if (empty($itemsJson)) {
                return [];
            }

            $itemsData = json_decode($itemsJson, true);

            if (!is_array($itemsData)) {
                return [];
            }

            $filteredItems = array_filter($itemsData, function($item) {
                return !isset($item['show_on_frontend']) || !empty($item['show_on_frontend']);
            });

            $this->menuTree = $this->buildTree($filteredItems);

            return $this->menuTree;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function buildTree(array $items, $parentId = null): array
    {
        $tree = [];
        $rootItemsFound = 0;

        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] ?? null;
            $itemId = $item['item_id'] ?? null;

            $shouldInclude = false;

            if ($parentId === null) {
                if ($itemParentId === 0 || $itemParentId === '0' || $itemParentId === null || $itemParentId === '') {
                    $shouldInclude = true;
                    $rootItemsFound++;
                }
            } else {
                if ($itemParentId == $parentId) {
                    $shouldInclude = true;
                }
            }

            if (!$shouldInclude) {
                continue;
            }

            if ($itemId) {
                $children = $this->buildTree($items, $itemId);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
            }

            $tree[] = $item;
        }

        return $tree;
    }

    protected function filterInactiveItems(array $items): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (!$item->getIsActive()) {
                continue;
            }

            if ($item->hasChildren()) {
                $children = $this->filterInactiveItems($item->getChildren());
                $item->setChildren($children);
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    public function renderItem(ItemInterface $item, int $level = 0): string
    {
        if (!$item->getIsActive()) {
            return '';
        }

        $html = '<li class="' . $this->escapeHtmlAttr($this->menuViewModel->getItemClass($item)) . '">';

        if ($this->menuViewModel->shouldShowContent($item)) {
            $html .= $this->renderContent($item);
        } else {
            $html .= $this->renderLink($item);
        }

        if ($item->hasChildren()) {
            $html .= $this->renderChildren($item, $level + 1);
        }

        $html .= '</li>';

        return $html;
    }

    protected function renderLink(ItemInterface $item): string
    {
        $url = $this->menuViewModel->getItemUrl($item);
        $title = $this->escapeHtml($item->getTitle());
        $target = $this->menuViewModel->getLinkTarget($item);
        $rel = $this->menuViewModel->getLinkRel($item);

        $attributes = [
            'href="' . $this->escapeUrl($url) . '"',
            'title="' . $title . '"',
            'target="' . $target . '"'
        ];

        if ($rel) {
            $attributes[] = 'rel="' . $this->escapeHtmlAttr($rel) . '"';
        }

        if ($item->hasChildren()) {
            $attributes[] = 'aria-haspopup="true"';
            $attributes[] = 'aria-expanded="false"';
        }

        $html = '<a ' . implode(' ', $attributes) . '>';
        $html .= $this->menuViewModel->getItemTitleWithIcon($item);
        $html .= '</a>';

        return $html;
    }

    protected function renderContent(ItemInterface $item): string
    {
        $content = $this->menuViewModel->processItemContent($item);
        $columnClass = $this->menuViewModel->getColumnWidthClass($item);

        return '<div class="menu-content ' . $columnClass . '">' . $content . '</div>';
    }

    protected function renderChildren(ItemInterface $item, int $level): string
    {
        $children = $item->getChildren();

        if (empty($children)) {
            return '';
        }

        $html = '<ul class="submenu level-' . $level . '">';

        foreach ($children as $child) {
            $html .= $this->renderItem($child, $level);
        }

        $html .= '</ul>';

        return $html;
    }

    public function getMenuHtml(string $identifier, string $cssClass = ''): string
    {
        if (!$this->menuHelper->isEnabled()) {
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

        $classes = ['megamenu', 'menu-' . $identifier];
        if ($cssClass) {
            $classes[] = $cssClass;
        }

        $html = '<nav class="' . $this->escapeHtmlAttr(implode(' ', $classes)) . '" role="navigation">';
        $html .= '<ul class="menu-root level-0">';

        foreach ($menuTree as $item) {
            $html .= $this->renderItem($item, 0);
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    public function getCacheKeyInfo()
    {
        $identifier = $this->getData('menu_identifier');

        return [
            'MEGAMENU_BLOCK',
            $this->storeManager->getStore()->getId(),
            $identifier ?: 'default',
            $this->_design->getDesignTheme()->getId(),
            $this->getCustomerId()
        ];
    }

    public function getIdentities()
    {
        $identities = [];

        if ($this->menu) {
            $identities = array_merge(
                $identities,
                $this->menuHelper->getMenuCacheTags($this->menu->getMenuId())
            );
        }

        return $identities;
    }

    protected function getCacheLifetime()
    {
        if ($this->menuHelper->isCacheEnabled()) {
            return $this->menuHelper->getCacheLifetime();
        }
        return null;
    }

    protected function _beforeToHtml()
    {
        $this->setData('theme_helper', $this->themeHelper);

        $isEnabled = $this->menuHelper->isEnabled();

        if (!$isEnabled) {
            return parent::_beforeToHtml();
        }

        $identifier = $this->getData('menu_identifier');

        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        if ($identifier) {
            $this->menu = $this->getMenu($identifier);
            $this->menuTree = $this->getMenuTree($identifier);
        }

        return parent::_beforeToHtml();
    }

    public function getMenuHelper(): MenuHelper
    {
        return $this->menuHelper;
    }

    public function getMenuViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    public function getViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    public function getThemeHelper(): ThemeHelper
    {
        return $this->themeHelper;
    }

    public function getCurrentMenu(): ?MenuInterface
    {
        return $this->menu;
    }

    public function getCurrentMenuTree(): array
    {
        return $this->menuTree ?? [];
    }

    public function shouldRender(): bool
    {
        if (!$this->menuHelper->isEnabled()) {
            return false;
        }

        $identifier = $this->getData('menu_identifier');

        if (!$identifier) {
            return false;
        }

        $menu = $this->getMenu($identifier);

        return $menu !== null && !empty($this->getMenuTree($identifier));
    }

    public function isEnabled(): bool
    {
        return $this->menuHelper->isEnabled();
    }

    public function getMenuData(): array
    {
        return $this->menuViewModel->getMenuData();
    }

    public function getMenuItems(): array
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        $identifier = $this->getData('menu_identifier');
        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        if (!$identifier) {
            return [];
        }

        $tree = $this->getMenuTree($identifier);
        return $tree;
    }

    public function getMenuDataJson(): string
    {
        return json_encode($this->getMenuData());
    }

    public function getStoreId(): int
    {
        try {
            return (int) $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            return 0;
        }
    }

    public function getCustomerId(): int
    {
        return 0;
    }

    public function isCacheEnabled(): bool
    {
        return $this->menuHelper->isCacheEnabled();
    }

    public function isLazyLoadEnabled(): bool
    {
        return $this->menuHelper->isLazyLoadEnabled();
    }

    public function getMobileBreakpoint(): int
    {
        return $this->menuHelper->getMobileBreakpoint();
    }

    public function isStickyEnabled(): bool
    {
        if ($this->menu && method_exists($this->menu, 'getIsSticky')) {
            $menuSticky = $this->menu->getIsSticky();
            if ($menuSticky !== null) {
                return (bool)$menuSticky;
            }
        }
        return $this->menuHelper->isStickyEnabled();
    }

    public function getStickyOffset(): int
    {
        return $this->menuHelper->getStickyOffset();
    }

    public function getHoverDelay(): int
    {
        return $this->menuHelper->getHoverIntentDelay();
    }

    public function getAnimationSpeed(): int
    {
        return $this->menuHelper->getAnimationDuration();
    }

    public function isCloseOnClick(): bool
    {
        return true;
    }

    public function showIcons(): bool
    {
        return $this->menuHelper->showIcons();
    }

    public function isRtl(): bool
    {
        return false;
    }

    public function getMenuCssClass(): string
    {
        if ($this->menu) {
            return $this->menu->getCssClass() ?? '';
        }

        $identifier = $this->getData('menu_identifier');
        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        if (!$identifier) {
            return '';
        }

        $menu = $this->getMenu($identifier);
        if (!$menu) {
            return '';
        }

        return $menu->getCssClass() ?? '';
    }

    public function getMenuCustomCss(): string
    {
        if ($this->menu) {
            return $this->menu->getCustomCss() ?? '';
        }

        $identifier = $this->getData('menu_identifier');
        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        if (!$identifier) {
            return '';
        }

        $menu = $this->getMenu($identifier);
        if (!$menu) {
            return '';
        }

        return $menu->getCustomCss() ?? '';
    }

    public function getMobileLayout(): string
    {
        if ($this->menu) {
            return $this->menu->getMobileLayout() ?? 'accordion';
        }

        $identifier = $this->getData('menu_identifier');
        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        if (!$identifier) {
            return 'accordion';
        }

        $menu = $this->getMenu($identifier);
        if (!$menu) {
            return 'accordion';
        }

        return $menu->getMobileLayout() ?? 'accordion';
    }

    public function getMenuRenderer(): MenuRenderer
    {
        return $this->menuRenderer;
    }

    public function isDebugEnabled(): bool
    {
        return $this->menuHelper->isDebugEnabled();
    }

    public function getMenuConfig(): array
    {
        $helper = $this->menuHelper;
        $menu = $this->getCurrentMenu();
        return [
            'hoverDelay' => (int)$helper->getHoverIntentDelay(),
            'animationType' => $helper->getAnimationType() ?: 'fade',
            'animationDuration' => (int)($helper->getAnimationDuration() ?: 200),
            'maxDepth' => (int)($helper->getMaxDepth() ?: 5),
            'columns' => (int)($helper->getColumns() ?: 4),
            'showIcons' => (bool)$helper->showIcons(),
            'showImages' => (bool)$helper->showImages(),
            'showCategoryCount' => (bool)$helper->showCategoryCount(),
            'enableCustomBlocks' => (bool)$helper->enableCustomBlocks(),
            'hoverEffect' => $helper->getHoverEffect() ?: 'underline',
            'stickyEnabled' => (bool)($menu && method_exists($menu, 'getIsSticky') && $menu->getIsSticky() !== null
                ? $menu->getIsSticky()
                : $helper->isStickyEnabled()),
            'stickyOffset' => (int)($helper->getStickyOffset() ?: 100),
            'stickyHideOnScrollDown' => (bool)$helper->isStickyHideOnScrollDown(),
            'stickyShowOnScrollUp' => (bool)$helper->isStickyShowOnScrollUp(),
            'stickyCompactMode' => (bool)$helper->isStickyCompactMode(),
            'stickyShadow' => (bool)$helper->isStickyShowShadow(),
            'stickyAnimationSpeed' => (int)($helper->getStickyAnimationSpeed() ?: 300),
            'mobileEnabled' => (bool)$helper->isMobileEnabled(),
            'mobilePosition' => $helper->getMobilePosition() ?: 'left',
            'mobileOverlay' => (bool)$helper->isMobileOverlayEnabled(),
            'mobileAccordion' => (bool)$helper->isAccordionEnabled(),
            'debugMode' => (bool)$helper->isDebugEnabled(),
        ];
    }

    public function getMenuConfigJson(): string
    {
        return json_encode($this->getMenuConfig());
    }
}
