<?php
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

    const XML_PATH_SHOW_CATEGORY_COUNT = 'panth_megamenu/display/show_category_count';

    const XML_PATH_COLUMNS = 'panth_megamenu/display/columns';
    const XML_PATH_ENABLE_CUSTOM_BLOCKS = 'panth_megamenu/display/enable_custom_blocks';
    const XML_PATH_HOVER_EFFECT = 'panth_megamenu/styling/hover_effect';
    const XML_PATH_IMAGE_SIZE = 'panth_megamenu/styling/image_size';

    const XML_PATH_MOBILE_POSITION = 'panth_megamenu/mobile/position';
    const XML_PATH_MOBILE_OVERLAY = 'panth_megamenu/mobile/overlay_enabled';
    const XML_PATH_MOBILE_SWIPE = 'panth_megamenu/mobile/swipe_enabled';
    const XML_PATH_MOBILE_ACCORDION = 'panth_megamenu/mobile/accordion_enabled';
    const XML_PATH_MOBILE_ANIMATION_SPEED = 'panth_megamenu/mobile/animation_speed';
    const XML_PATH_MOBILE_SHOW_ICONS = 'panth_megamenu/mobile/show_category_icons';

    const XML_PATH_STICKY_OFFSET = 'panth_megamenu/sticky/offset';
    const XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN = 'panth_megamenu/sticky/hide_on_scroll_down';
    const XML_PATH_STICKY_SHOW_ON_SCROLL_UP = 'panth_megamenu/sticky/show_on_scroll_up';
    const XML_PATH_STICKY_COMPACT_MODE = 'panth_megamenu/sticky/compact_mode';
    const XML_PATH_STICKY_ANIMATION_SPEED = 'panth_megamenu/sticky/animation_speed';
    const XML_PATH_STICKY_SHOW_SHADOW = 'panth_megamenu/sticky/show_shadow';

    const CACHE_TAG = 'panth_megamenu';

    private $cacheTypeList;

    private $cacheFrontendPool;

    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMenuIdentifier(?int $storeId = null): ?string
    {
        $identifier = $this->scopeConfig->getValue(
            self::XML_PATH_MENU_IDENTIFIER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $identifier ? trim($identifier) : null;
    }

    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CACHE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCacheLifetime(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3600;
    }

    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMobileBreakpoint(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_BREAKPOINT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 1024;
    }

    public function isStickyMenuEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_MENU,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAnimationType(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'fade';
    }

    public function getAnimationDuration(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_DURATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 200;
    }

    public function getHoverIntentDelay(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_HOVER_INTENT_DELAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 150;
    }

    public function showIcons(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ICONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function showImages(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_IMAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMaxDepth(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_DEPTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 5;
    }

    public function isLazyLoadEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LAZY_LOAD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCustomCss(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CSS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCustomJs(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_JS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

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

    public function flushMenuCache(): void
    {
        $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER);
    }

    public function getMenuCacheTags(int $menuId): array
    {
        return [
            self::CACHE_TAG,
            self::CACHE_TAG . '_' . $menuId
        ];
    }

    public function isCategoryItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_CATEGORY;
    }

    public function isLinkItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_LINK;
    }

    public function isContentItem(ItemInterface $item): bool
    {
        return $item->getItemType() === ItemInterface::TYPE_CONTENT;
    }

    public function hasCategoryLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CATEGORY;
    }

    public function hasCmsPageLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CMS_PAGE;
    }

    public function hasCustomUrlLink(ItemInterface $item): bool
    {
        return $item->getLinkType() === ItemInterface::LINK_CUSTOM_URL;
    }

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

    public function getConfigValue(string $path, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getConfigFlag(string $path, ?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMenuBackgroundColor(?int $storeId = null): string
    {
        return '';
    }

    public function getMenuTextColor(?int $storeId = null): string
    {
        return '';
    }

    public function getMenuHoverColor(?int $storeId = null): string
    {
        return '';
    }

    public function getDropdownBackgroundColor(?int $storeId = null): string
    {
        return '';
    }

    public function getDropdownBorderColor(?int $storeId = null): string
    {
        return '';
    }

    public function showCategoryCount(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_CATEGORY_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getColumns(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_COLUMNS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 4;
    }

    public function isCustomBlocksEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CUSTOM_BLOCKS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getHoverEffect(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_HOVER_EFFECT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'underline';
    }

    public function getImageSize(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'thumbnail';
    }

    public function getMobilePosition(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_POSITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'left';
    }

    public function isMobileOverlayEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_OVERLAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isMobileSwipeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_SWIPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isMobileAccordionEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ACCORDION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMobileAnimationSpeed(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MOBILE_ANIMATION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300;
    }

    public function showMobileCategoryIcons(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_SHOW_ICONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

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

    public function getStickyOffset(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_STICKY_OFFSET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 100;
    }

    public function hideOnScrollDown(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function showOnScrollUp(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_SHOW_ON_SCROLL_UP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isStickyCompactMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_COMPACT_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getStickyAnimationSpeed(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_STICKY_ANIMATION_SPEED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 300;
    }

    public function showStickyShadow(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_SHOW_SHADOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function enableCustomBlocks(?int $storeId = null): bool
    {
        return $this->isCustomBlocksEnabled($storeId);
    }

    public function isStickyEnabled(?int $storeId = null): bool
    {
        return $this->isStickyMenuEnabled($storeId);
    }

    public function isAccordionEnabled(?int $storeId = null): bool
    {
        return $this->isMobileAccordionEnabled($storeId);
    }

    public function isStickyHideOnScrollDown(?int $storeId = null): bool
    {
        return $this->hideOnScrollDown($storeId);
    }

    public function isStickyShowOnScrollUp(?int $storeId = null): bool
    {
        return $this->showOnScrollUp($storeId);
    }

    public function isStickyShowShadow(?int $storeId = null): bool
    {
        return $this->showStickyShadow($storeId);
    }
}
