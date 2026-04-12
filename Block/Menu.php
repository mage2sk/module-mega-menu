<?php
/**
 * Menu Block
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
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
    /**
     * @var MenuRepositoryInterface
     */
    protected $menuRepository;

    /**
     * @var ItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MenuHelper
     */
    protected $menuHelper;

    /**
     * @var MenuViewModel
     */
    protected $menuViewModel;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ThemeHelper
     */
    protected $themeHelper;

    /**
     * @var MenuRenderer
     */
    protected $menuRenderer;

    /**
     * @var MenuInterface|null
     */
    protected $menu;

    /**
     * @var array|null
     */
    protected $menuTree;

    /**
     * @var string
     */
    protected $_template = 'Panth_MegaMenu::menu.phtml';

    /**
     * @param Context $context
     * @param MenuRepositoryInterface $menuRepository
     * @param ItemRepositoryInterface $itemRepository
     * @param StoreManagerInterface $storeManager
     * @param MenuHelper $menuHelper
     * @param MenuViewModel $menuViewModel
     * @param LoggerInterface $logger
     * @param ThemeHelper $themeHelper
     * @param MenuRenderer $menuRenderer
     * @param array $data
     */
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

    /**
     * Get menu by identifier
     *
     * @param string $identifier
     * @return MenuInterface|null
     */
    public function getMenu(string $identifier): ?MenuInterface
    {
        if ($this->menu !== null && $this->menu->getIdentifier() === $identifier) {
            return $this->menu;
        }

        try {
            // Pass null for store_id to avoid joining with store table (which may not exist)
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

    /**
     * Get menu tree
     *
     * @param int|string $menuIdentifier Menu ID or identifier
     * @return array
     */
    public function getMenuTree($menuIdentifier): array
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        try {
            // If identifier is string, load menu first
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

            // Get items from JSON field
            $itemsJson = $menu->getItemsJson();

            if (empty($itemsJson)) {
                return [];
            }

            $itemsData = json_decode($itemsJson, true);

            if (!is_array($itemsData)) {
                return [];
            }

            // Filter items by show_on_frontend (default to true if not set)
            $filteredItems = array_filter($itemsData, function($item) {
                // If show_on_frontend is not set, default to true (show the item)
                // Only hide if explicitly set to false/0
                return !isset($item['show_on_frontend']) || !empty($item['show_on_frontend']);
            });

            // Build tree structure from flat array
            $this->menuTree = $this->buildTree($filteredItems);

            return $this->menuTree;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Build tree structure from flat array of items
     *
     * Items are stored FLAT with:
     * - item_id: "cat_24" (unique identifier)
     * - parent_id: "cat_24" or 0 (references another item's item_id)
     *
     * @param array $items Flat array of menu items
     * @param string|int|null $parentId Parent item_id to match
     * @return array Nested tree structure
     */
    protected function buildTree(array $items, $parentId = null): array
    {
        $tree = [];
        $rootItemsFound = 0;

        foreach ($items as $item) {
            // Get this item's parent_id and item_id
            $itemParentId = $item['parent_id'] ?? null;
            $itemId = $item['item_id'] ?? null;

            // Determine if this item belongs at this level
            $shouldInclude = false;

            if ($parentId === null) {
                // Root level: match items with parent_id = 0 (integer or string)
                if ($itemParentId === 0 || $itemParentId === '0' || $itemParentId === null || $itemParentId === '') {
                    $shouldInclude = true;
                    $rootItemsFound++;
                }
            } else {
                // Child level: match items where parent_id equals the given parentId
                // Use loose comparison to handle string/int variations
                if ($itemParentId == $parentId) {
                    $shouldInclude = true;
                }
            }

            if (!$shouldInclude) {
                continue;
            }

            // Recursively build children using this item's item_id
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

    /**
     * Filter inactive items from tree
     *
     * @param array $items
     * @return array
     */
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

    /**
     * Render single menu item
     *
     * @param ItemInterface $item
     * @param int $level
     * @return string
     */
    public function renderItem(ItemInterface $item, int $level = 0): string
    {
        if (!$item->getIsActive()) {
            return '';
        }

        $html = '<li class="' . $this->escapeHtmlAttr($this->menuViewModel->getItemClass($item)) . '">';

        // Render link or content
        if ($this->menuViewModel->shouldShowContent($item)) {
            $html .= $this->renderContent($item);
        } else {
            $html .= $this->renderLink($item);
        }

        // Render children if any
        if ($item->hasChildren()) {
            $html .= $this->renderChildren($item, $level + 1);
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render item link
     *
     * @param ItemInterface $item
     * @return string
     */
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

    /**
     * Render item content
     *
     * @param ItemInterface $item
     * @return string
     */
    protected function renderContent(ItemInterface $item): string
    {
        $content = $this->menuViewModel->processItemContent($item);
        $columnClass = $this->menuViewModel->getColumnWidthClass($item);

        return '<div class="menu-content ' . $columnClass . '">' . $content . '</div>';
    }

    /**
     * Render children items
     *
     * @param ItemInterface $item
     * @param int $level
     * @return string
     */
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

    /**
     * Get full rendered menu HTML
     *
     * @param string $identifier
     * @param string $cssClass
     * @return string
     */
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

    /**
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $identifier = $this->getData('menu_identifier');

        return [
            'MEGAMENU_BLOCK',
            $this->storeManager->getStore()->getId(),
            $identifier ?: 'default',
            $this->_design->getDesignTheme()->getId(),
            $this->getCustomerId() // Include customer ID for personalized caching
        ];
    }

    /**
     * Get identities
     *
     * @return array
     */
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

    /**
     * Get cache lifetime
     *
     * @return int|null
     */
    protected function getCacheLifetime()
    {
        if ($this->menuHelper->isCacheEnabled()) {
            return $this->menuHelper->getCacheLifetime();
        }
        return null;
    }

    /**
     * Before rendering html process
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        // Pass theme helper to template for dual-theme compatibility
        $this->setData('theme_helper', $this->themeHelper);

        // Only load menu if MegaMenu is enabled
        $isEnabled = $this->menuHelper->isEnabled();

        if (!$isEnabled) {
            return parent::_beforeToHtml();
        }

        // Get menu identifier from layout argument or config
        $identifier = $this->getData('menu_identifier');

        // If not set in layout, get from config
        if (!$identifier) {
            $identifier = $this->menuHelper->getMenuIdentifier();
        }

        // Load menu if identifier is set
        if ($identifier) {
            $this->menu = $this->getMenu($identifier);
            $this->menuTree = $this->getMenuTree($identifier);
        }

        return parent::_beforeToHtml();
    }

    /**
     * Get menu helper
     *
     * @return MenuHelper
     */
    public function getMenuHelper(): MenuHelper
    {
        return $this->menuHelper;
    }

    /**
     * Get menu view model
     *
     * @return MenuViewModel
     */
    public function getMenuViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    /**
     * Get view model (alias for getMenuViewModel)
     *
     * @return MenuViewModel
     */
    public function getViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    /**
     * Get theme helper
     *
     * @return ThemeHelper
     */
    public function getThemeHelper(): ThemeHelper
    {
        return $this->themeHelper;
    }

    /**
     * Get current menu
     *
     * @return MenuInterface|null
     */
    public function getCurrentMenu(): ?MenuInterface
    {
        return $this->menu;
    }

    /**
     * Get current menu tree
     *
     * @return array
     */
    public function getCurrentMenuTree(): array
    {
        return $this->menuTree ?? [];
    }

    /**
     * Check if menu should be rendered
     *
     * @return bool
     */
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

    /**
     * Check if MegaMenu is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->menuHelper->isEnabled();
    }

    /**
     * Get menu data for JavaScript
     *
     * @return array
     */
    public function getMenuData(): array
    {
        return $this->menuViewModel->getMenuData();
    }

    /**
     * Get menu items for the current menu
     *
     * @return array
     */
    public function getMenuItems(): array
    {
        // If tree is already loaded in _beforeToHtml, return it
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        // Otherwise try to load from identifier
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

    /**
     * Get menu data as JSON
     *
     * @return string
     */
    public function getMenuDataJson(): string
    {
        return json_encode($this->getMenuData());
    }

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId(): int
    {
        try {
            return (int) $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            return 0;
        }
    }

    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        return 0; // Implement customer session logic if needed
    }

    /**
     * Check if cache is enabled (for templates)
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->menuHelper->isCacheEnabled();
    }

    /**
     * Check if lazy load is enabled
     *
     * @return bool
     */
    public function isLazyLoadEnabled(): bool
    {
        return $this->menuHelper->isLazyLoadEnabled();
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
     * Check if sticky menu is enabled
     * Checks menu entity first, falls back to global config
     *
     * @return bool
     */
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

    /**
     * Get sticky offset
     *
     * @return int
     */
    public function getStickyOffset(): int
    {
        return $this->menuHelper->getStickyOffset();
    }

    /**
     * Get hover delay
     *
     * @return int
     */
    public function getHoverDelay(): int
    {
        return $this->menuHelper->getHoverIntentDelay();
    }

    /**
     * Get animation speed
     *
     * @return int
     */
    public function getAnimationSpeed(): int
    {
        return $this->menuHelper->getAnimationDuration();
    }

    /**
     * Check if close on click is enabled
     *
     * @return bool
     */
    public function isCloseOnClick(): bool
    {
        return true; // Default behavior
    }

    /**
     * Check if icons should be shown
     *
     * @return bool
     */
    public function showIcons(): bool
    {
        return $this->menuHelper->showIcons();
    }

    /**
     * Check if RTL is enabled
     *
     * @return bool
     */
    public function isRtl(): bool
    {
        return false; // Implement RTL detection if needed
    }

    /**
     * Get current menu CSS class
     *
     * @return string
     */
    public function getMenuCssClass(): string
    {
        // If menu is already loaded in _beforeToHtml, use it
        if ($this->menu) {
            return $this->menu->getCssClass() ?? '';
        }

        // Otherwise try to load from identifier
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

    /**
     * Get current menu custom CSS
     *
     * @return string
     */
    public function getMenuCustomCss(): string
    {
        // If menu is already loaded in _beforeToHtml, use it
        if ($this->menu) {
            return $this->menu->getCustomCss() ?? '';
        }

        // Otherwise try to load from identifier
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

    /**
     * Get current menu mobile layout
     *
     * @return string
     */
    public function getMobileLayout(): string
    {
        // If menu is already loaded in _beforeToHtml, use it
        if ($this->menu) {
            return $this->menu->getMobileLayout() ?? 'accordion';
        }

        // Otherwise try to load from identifier
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

    /**
     * Get menu renderer helper
     *
     * @return MenuRenderer
     */
    public function getMenuRenderer(): MenuRenderer
    {
        return $this->menuRenderer;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->menuHelper->isDebugEnabled();
    }

    /**
     * Get complete menu config array for Alpine.js components
     *
     * @return array
     */
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

    /**
     * Get menu config as JSON for embedding in templates
     *
     * @return string
     */
    public function getMenuConfigJson(): string
    {
        return json_encode($this->getMenuConfig());
    }
}
