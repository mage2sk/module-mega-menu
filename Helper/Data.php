<?php
/**
 * MegaMenu Helper Data
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Store\Model\ScopeInterface;
use Panth\MegaMenu\Api\Data\ItemInterface;

class Data extends AbstractHelper
{
    /**
     * Configuration paths
     */
    const XML_PATH_ENABLED = 'panth_megamenu/general/enabled';
    const XML_PATH_MENU_IDENTIFIER = 'panth_megamenu/general/menu_identifier';
    const XML_PATH_CACHE_ENABLED = 'panth_megamenu/performance/cache_enabled';
    const XML_PATH_CACHE_LIFETIME = 'panth_megamenu/performance/cache_lifetime';
    const XML_PATH_MOBILE_ENABLED = 'panth_megamenu/mobile/mobile_enabled';
    const XML_PATH_MOBILE_BREAKPOINT = 'panth_megamenu/general/mobile_breakpoint';
    const XML_PATH_STICKY_MENU = 'panth_megamenu/general/sticky_menu';
    const XML_PATH_ANIMATION_TYPE = 'panth_megamenu/display/animation_type';
    const XML_PATH_ANIMATION_DURATION = 'panth_megamenu/display/animation_duration';
    const XML_PATH_HOVER_INTENT_DELAY = 'panth_megamenu/display/hover_intent_delay';
    const XML_PATH_SHOW_ICONS = 'panth_megamenu/display/show_icons';
    const XML_PATH_SHOW_IMAGES = 'panth_megamenu/display/show_images';
    const XML_PATH_MAX_DEPTH = 'panth_megamenu/display/max_depth';
    const XML_PATH_LAZY_LOAD = 'panth_megamenu/performance/lazy_load';
    const XML_PATH_DEBUG_MODE = 'panth_megamenu/advanced/enable_debug';
    const XML_PATH_CUSTOM_CSS = 'panth_megamenu/styling/custom_css';
    const XML_PATH_CUSTOM_JS = 'panth_megamenu/advanced/custom_js';
    // Color constants removed — colors are now in theme-config.json under header.menu

    // MegaMenuBasic paths (category-based menu)
    const XML_PATH_SHOW_CATEGORY_COUNT = 'panth_megamenu/display/show_category_count';

    // MegaMenuPro paths (advanced features)
    const XML_PATH_COLUMNS = 'panth_megamenu/display/columns';
    const XML_PATH_ENABLE_CUSTOM_BLOCKS = 'panth_megamenu/display/enable_custom_blocks';
    const XML_PATH_HOVER_EFFECT = 'panth_megamenu/styling/hover_effect';
    const XML_PATH_IMAGE_SIZE = 'panth_megamenu/styling/image_size';

    // MobileMegaMenu paths
    const XML_PATH_MOBILE_POSITION = 'panth_megamenu/mobile/position';
    const XML_PATH_MOBILE_OVERLAY = 'panth_megamenu/mobile/overlay_enabled';
    const XML_PATH_MOBILE_SWIPE = 'panth_megamenu/mobile/swipe_enabled';
    const XML_PATH_MOBILE_ACCORDION = 'panth_megamenu/mobile/accordion_enabled';
    const XML_PATH_MOBILE_ANIMATION_SPEED = 'panth_megamenu/mobile/animation_speed';
    const XML_PATH_MOBILE_SHOW_ICONS = 'panth_megamenu/mobile/show_category_icons';

    // StickyMenu paths
    const XML_PATH_STICKY_OFFSET = 'panth_megamenu/sticky/offset';
    const XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN = 'panth_megamenu/sticky/hide_on_scroll_down';
    const XML_PATH_STICKY_SHOW_ON_SCROLL_UP = 'panth_megamenu/sticky/show_on_scroll_up';
    const XML_PATH_STICKY_COMPACT_MODE = 'panth_megamenu/sticky/compact_mode';
    const XML_PATH_STICKY_ANIMATION_SPEED = 'panth_megamenu/sticky/animation_speed';
    const XML_PATH_STICKY_SHOW_SHADOW = 'panth_megamenu/sticky/show_shadow';

    /**
     * Cache tags
     */
    const CACHE_TAG = 'panth_megamenu';

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var Pool
     */
    private $cacheFrontendPool;

    /**
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get menu identifier from configuration
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getMenuIdentifier(?int $storeId = null): ?string
    {
        $identifier = $this->scopeConfig->getValue(
            self::XML_PATH_MENU_IDENTIFIER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $identifier ? trim($identifier) : null;
    }

    /**
     * Check if cache is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CACHE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cache lifetime
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCacheLifetime(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3600;
    }

    /**
     * Check if mobile menu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get mobile breakpoint
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMobileBreakpoint(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_BREAKPOINT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 1024;
    }

    /**
     * Check if sticky menu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyMenuEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_MENU,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get animation type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAnimationType(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'fade';
    }

    /**
     * Get animation duration
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAnimationDuration(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_DURATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 200;
    }

    /**
     * Get hover intent delay
     *
     * @param int|null $storeId
     * @return int
     */
    public function getHoverIntentDelay(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_HOVER_INTENT_DELAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 150;
    }

    /**
     * Check if icons should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showIcons(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ICONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if images should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showImages(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_IMAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get maximum menu depth
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxDepth(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_DEPTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 5;
    }

    /**
     * Check if lazy load is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isLazyLoadEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LAZY_LOAD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom CSS
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomCss(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get custom JavaScript
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomJs(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_JS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Clean menu cache
     *
     * @param int|null $menuId
     * @return void
     */
    public function cleanMenuCache(?int $menuId = null): void
    {
        $tags = [self::CACHE_TAG];

        if ($menuId !== null) {
            $tags[] = self::CACHE_TAG . '_' . $menuId;
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
        }
    }

    /**
     * Flush all menu cache
     *
     * @return void
     */
    public function flushMenuCache(): void
    {
        $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER);
    }

    /**
     * Get cache tags for menu
     *
     * @param int $menuId
     * @return array
     */
    public function getMenuCacheTags(int $menuId): array
    {
        return [
            self::CACHE_TAG,
            self::CACHE_TAG . '_' . $menuId
        ];
    }

    /**
     * Check if item is category type
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function isCategoryItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_CATEGORY;
    }

    /**
     * Check if item is link type
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function isLinkItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_LINK;
    }

    /**
     * Check if item is content type
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function isContentItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_CONTENT;
    }

    /**
     * Check if item has category link
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function hasCategoryLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CATEGORY;
    }

    /**
     * Check if item has CMS page link
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function hasCmsPageLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CMS_PAGE;
    }

    /**
     * Check if item has custom URL link
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function hasCustomUrlLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CUSTOM_URL;
    }

    /**
     * Get item CSS classes
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getItemClasses(ItemInterface $item): string
    {
        $classes = ['menu-item'];
        $classes[] = 'menu-item-' . $item->getItemType();
        $classes[] = 'level-' . $item->getLevel();

        if ($item->hasChildren()) {
            $classes[] = 'has-children';
        }

        if (!$item->getIsActive()) {
            $classes[] = 'disabled';
        }

        if ($item->getCssClass()) {
            $classes[] = $item->getCssClass();
        }

        return implode(' ', $classes);
    }

    /**
     * Get configuration value
     *
     * @param string $path
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigValue(string $path, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get configuration flag
     *
     * @param string $path
     * @param int|null $storeId
     * @return bool
     */
    public function getConfigFlag(string $path, ?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // ===== Deprecated color methods (colors now in theme-config.json) =====
    // Kept as stubs for backward compatibility with ViewModel/Config and Luma templates.

    /**
     * @deprecated Colors are now in theme-config.json. Returns empty string.
     */
    public function getMenuBackgroundColor(?int $storeId = null): string
    {
        return '';
    }

    /**
     * @deprecated Colors are now in theme-config.json. Returns empty string.
     */
    public function getMenuTextColor(?int $storeId = null): string
    {
        return '';
    }

    /**
     * @deprecated Colors are now in theme-config.json. Returns empty string.
     */
    public function getMenuHoverColor(?int $storeId = null): string
    {
        return '';
    }

    /**
     * @deprecated Colors are now in theme-config.json. Returns empty string.
     */
    public function getDropdownBackgroundColor(?int $storeId = null): string
    {
        return '';
    }

    /**
     * @deprecated Colors are now in theme-config.json. Returns empty string.
     */
    public function getDropdownBorderColor(?int $storeId = null): string
    {
        return '';
    }

    // ===== MegaMenuBasic methods =====

    /**
     * Check if category count should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showCategoryCount(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_CATEGORY_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // ===== MegaMenuPro methods =====

    /**
     * Get number of columns for dropdown
     *
     * @param int|null $storeId
     * @return int
     */
    public function getColumns(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_COLUMNS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 4;
    }

    /**
     * Check if custom blocks are enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCustomBlocksEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CUSTOM_BLOCKS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get hover effect type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHoverEffect(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_HOVER_EFFECT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'underline';
    }

    /**
     * Get image size
     *
     * @param int|null $storeId
     * @return string
     */
    public function getImageSize(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'thumbnail';
    }

    // ===== MobileMegaMenu methods =====

    /**
     * Get mobile menu position
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMobilePosition(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'left';
    }

    /**
     * Check if mobile overlay is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileOverlayEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_OVERLAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if mobile swipe is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileSwipeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_SWIPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if mobile accordion is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileAccordionEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ACCORDION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get mobile animation speed
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMobileAnimationSpeed(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_ANIMATION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300;
    }

    /**
     * Check if mobile category icons should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showMobileCategoryIcons(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_SHOW_ICONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get configuration as JSON
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConfigJson(?int $storeId = null): string
    {
        return json_encode([
            'enabled' => $this->isEnabled($storeId),
            'mobileEnabled' => $this->isMobileEnabled($storeId),
            'mobileBreakpoint' => $this->getMobileBreakpoint($storeId),
            'stickyMenu' => $this->isStickyMenuEnabled($storeId),
            'animationType' => $this->getAnimationType($storeId),
            'animationDuration' => $this->getAnimationDuration($storeId),
            'hoverIntentDelay' => $this->getHoverIntentDelay($storeId),
            'showIcons' => $this->showIcons($storeId),
            'showImages' => $this->showImages($storeId),
            'maxDepth' => $this->getMaxDepth($storeId),
            'showCategoryCount' => $this->showCategoryCount($storeId),
            'columns' => $this->getColumns($storeId),
            'customBlocks' => $this->isCustomBlocksEnabled($storeId),
            'hoverEffect' => $this->getHoverEffect($storeId),
            'imageSize' => $this->getImageSize($storeId),
            'mobilePosition' => $this->getMobilePosition($storeId),
            'mobileOverlay' => $this->isMobileOverlayEnabled($storeId),
            'mobileSwipe' => $this->isMobileSwipeEnabled($storeId),
            'mobileAccordion' => $this->isMobileAccordionEnabled($storeId),
            'mobileAnimationSpeed' => $this->getMobileAnimationSpeed($storeId),
            'mobileCategoryIcons' => $this->showMobileCategoryIcons($storeId),
            'stickyOffset' => $this->getStickyOffset($storeId),
            'stickyHideOnScrollDown' => $this->isStickyHideOnScrollDown($storeId),
            'stickyShowOnScrollUp' => $this->isStickyShowOnScrollUp($storeId),
            'stickyCompactMode' => $this->isStickyCompactMode($storeId),
            'stickyAnimationSpeed' => $this->getStickyAnimationSpeed($storeId),
            'stickyShadow' => $this->isStickyShowShadow($storeId),
            'debugMode' => $this->isDebugEnabled($storeId),
            'lazyLoad' => $this->isLazyLoadEnabled($storeId),
            'enableCustomBlocks' => $this->enableCustomBlocks($storeId)
        ]);
    }

    // ===== StickyMenu methods =====

    /**
     * Get sticky menu scroll offset
     *
     * @param int|null $storeId
     * @return int
     */
    public function getStickyOffset(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_STICKY_OFFSET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 100;
    }

    /**
     * Check if sticky menu should hide on scroll down
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hideOnScrollDown(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if sticky menu should show on scroll up
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showOnScrollUp(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_SHOW_ON_SCROLL_UP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if sticky menu compact mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyCompactMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_COMPACT_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get sticky menu animation speed
     *
     * @param int|null $storeId
     * @return int
     */
    public function getStickyAnimationSpeed(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_STICKY_ANIMATION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300;
    }

    /**
     * Check if sticky menu shadow should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showStickyShadow(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_SHOW_SHADOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    // ===== Alias methods for template/block compatibility =====

    /**
     * Alias for isCustomBlocksEnabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function enableCustomBlocks(?int $storeId = null): bool
    {
        return $this->isCustomBlocksEnabled($storeId);
    }

    /**
     * Alias for isStickyMenuEnabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyEnabled(?int $storeId = null): bool
    {
        return $this->isStickyMenuEnabled($storeId);
    }

    /**
     * Alias for isMobileAccordionEnabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAccordionEnabled(?int $storeId = null): bool
    {
        return $this->isMobileAccordionEnabled($storeId);
    }

    /**
     * Alias for hideOnScrollDown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyHideOnScrollDown(?int $storeId = null): bool
    {
        return $this->hideOnScrollDown($storeId);
    }

    /**
     * Alias for showOnScrollUp
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyShowOnScrollUp(?int $storeId = null): bool
    {
        return $this->showOnScrollUp($storeId);
    }

    /**
     * Alias for showStickyShadow
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyShowShadow(?int $storeId = null): bool
    {
        return $this->showStickyShadow($storeId);
    }

}
