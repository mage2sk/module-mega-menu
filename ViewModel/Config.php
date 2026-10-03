<?php
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;

class Config implements ArgumentInterface
{
    private $menuHelper;

    private $jsonSerializer;

    public function __construct(
        MenuHelper $menuHelper,
        Json $jsonSerializer
    ) {
        $this->menuHelper = $menuHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isEnabled($storeId);
    }

    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileEnabled($storeId);
    }

    public function getMobileBreakpoint(?int $storeId = null): int
    {
        return $this->menuHelper->getMobileBreakpoint($storeId);
    }

    public function isStickyEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isStickyMenuEnabled($storeId);
    }

    public function getStickyOffset(?int $storeId = null): int
    {
        return $this->menuHelper->getStickyOffset($storeId);
    }

    public function getMaxDepth(?int $storeId = null): int
    {
        return $this->menuHelper->getMaxDepth($storeId);
    }

    public function getAnimationType(?int $storeId = null): string
    {
        return $this->menuHelper->getAnimationType($storeId);
    }

    public function getAnimationDuration(?int $storeId = null): int
    {
        return $this->menuHelper->getAnimationDuration($storeId);
    }

    public function getHoverIntentDelay(?int $storeId = null): int
    {
        return $this->menuHelper->getHoverIntentDelay($storeId);
    }

    public function showIcons(?int $storeId = null): bool
    {
        return $this->menuHelper->showIcons($storeId);
    }

    public function showImages(?int $storeId = null): bool
    {
        return $this->menuHelper->showImages($storeId);
    }

    public function getHoverEffect(?int $storeId = null): string
    {
        return $this->menuHelper->getHoverEffect($storeId);
    }

    public function getImageSize(?int $storeId = null): string
    {
        return $this->menuHelper->getImageSize($storeId);
    }

    public function getColumns(?int $storeId = null): int
    {
        return $this->menuHelper->getColumns($storeId);
    }

    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isCacheEnabled($storeId);
    }

    public function getCacheLifetime(?int $storeId = null): int
    {
        return $this->menuHelper->getCacheLifetime($storeId);
    }

    public function isLazyLoadEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isLazyLoadEnabled($storeId);
    }

    public function getMobilePosition(?int $storeId = null): string
    {
        return $this->menuHelper->getMobilePosition($storeId);
    }

    public function isMobileOverlayEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileOverlayEnabled($storeId);
    }

    public function isMobileSwipeEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileSwipeEnabled($storeId);
    }

    public function isMobileAccordionEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileAccordionEnabled($storeId);
    }

    public function getMobileAnimationSpeed(?int $storeId = null): int
    {
        return $this->menuHelper->getMobileAnimationSpeed($storeId);
    }

    public function showMobileCategoryIcons(?int $storeId = null): bool
    {
        return $this->menuHelper->showMobileCategoryIcons($storeId);
    }

    public function hideOnScrollDown(?int $storeId = null): bool
    {
        return $this->menuHelper->hideOnScrollDown($storeId);
    }

    public function showOnScrollUp(?int $storeId = null): bool
    {
        return $this->menuHelper->showOnScrollUp($storeId);
    }

    public function isStickyCompactMode(?int $storeId = null): bool
    {
        return $this->menuHelper->isStickyCompactMode($storeId);
    }

    public function getStickyAnimationSpeed(?int $storeId = null): int
    {
        return $this->menuHelper->getStickyAnimationSpeed($storeId);
    }

    public function showStickyShadow(?int $storeId = null): bool
    {
        return $this->menuHelper->showStickyShadow($storeId);
    }

    public function getMenuBackgroundColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuBackgroundColor($storeId);
    }

    public function getMenuTextColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuTextColor($storeId);
    }

    public function getMenuHoverColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuHoverColor($storeId);
    }

    public function getDropdownBackgroundColor(?int $storeId = null): string
    {
        return $this->menuHelper->getDropdownBackgroundColor($storeId);
    }

    public function getDropdownBorderColor(?int $storeId = null): string
    {
        return $this->menuHelper->getDropdownBorderColor($storeId);
    }

    public function getCustomCss(?int $storeId = null): string
    {
        return $this->menuHelper->getCustomCss($storeId);
    }

    public function getCustomJs(?int $storeId = null): string
    {
        return $this->menuHelper->getCustomJs($storeId);
    }

    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isDebugEnabled($storeId);
    }

    public function isCustomBlocksEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isCustomBlocksEnabled($storeId);
    }

    public function showCategoryCount(?int $storeId = null): bool
    {
        return $this->menuHelper->showCategoryCount($storeId);
    }

    public function getConfigJson(?int $storeId = null): string
    {
        return $this->menuHelper->getConfigJson($storeId);
    }

    public function getConfigArray(?int $storeId = null): array
    {
        return [

            'enabled' => $this->isEnabled($storeId),
            'mobileEnabled' => $this->isMobileEnabled($storeId),
            'mobileBreakpoint' => $this->getMobileBreakpoint($storeId),
            'stickyEnabled' => $this->isStickyEnabled($storeId),
            'stickyOffset' => $this->getStickyOffset($storeId),
            'maxDepth' => $this->getMaxDepth($storeId),

            'animationType' => $this->getAnimationType($storeId),
            'animationDuration' => $this->getAnimationDuration($storeId),
            'hoverIntentDelay' => $this->getHoverIntentDelay($storeId),
            'showIcons' => $this->showIcons($storeId),
            'showImages' => $this->showImages($storeId),
            'hoverEffect' => $this->getHoverEffect($storeId),
            'imageSize' => $this->getImageSize($storeId),
            'columns' => $this->getColumns($storeId),

            'cacheEnabled' => $this->isCacheEnabled($storeId),
            'cacheLifetime' => $this->getCacheLifetime($storeId),
            'lazyLoad' => $this->isLazyLoadEnabled($storeId),

            'mobilePosition' => $this->getMobilePosition($storeId),
            'mobileOverlay' => $this->isMobileOverlayEnabled($storeId),
            'mobileSwipe' => $this->isMobileSwipeEnabled($storeId),
            'mobileAccordion' => $this->isMobileAccordionEnabled($storeId),
            'mobileAnimationSpeed' => $this->getMobileAnimationSpeed($storeId),
            'mobileCategoryIcons' => $this->showMobileCategoryIcons($storeId),

            'stickyHideOnScrollDown' => $this->hideOnScrollDown($storeId),
            'stickyShowOnScrollUp' => $this->showOnScrollUp($storeId),
            'stickyCompactMode' => $this->isStickyCompactMode($storeId),
            'stickyAnimationSpeed' => $this->getStickyAnimationSpeed($storeId),
            'stickyShowShadow' => $this->showStickyShadow($storeId),

            'menuBackgroundColor' => $this->getMenuBackgroundColor($storeId),
            'menuTextColor' => $this->getMenuTextColor($storeId),
            'menuHoverColor' => $this->getMenuHoverColor($storeId),
            'dropdownBackgroundColor' => $this->getDropdownBackgroundColor($storeId),
            'dropdownBorderColor' => $this->getDropdownBorderColor($storeId),

            'debugEnabled' => $this->isDebugEnabled($storeId),
            'customBlocksEnabled' => $this->isCustomBlocksEnabled($storeId),
            'showCategoryCount' => $this->showCategoryCount($storeId)
        ];
    }

    public function getConfigDataAttributes(?int $storeId = null): string
    {
        $config = $this->getConfigArray($storeId);
        $attributes = [];

        foreach ($config as $key => $value) {
            $dataKey = 'data-megamenu-' . $this->camelCaseToKebab($key);
            $dataValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            $attributes[] = sprintf('%s="%s"', $dataKey, htmlspecialchars((string)$dataValue));
        }

        return implode(' ', $attributes);
    }

    public function getAlpineConfig(?int $storeId = null): string
    {
        return $this->jsonSerializer->serialize([
            'config' => $this->getConfigArray($storeId),
            'isOpen' => false,
            'isMobile' => false,
            'isSticky' => false,
            'activeItem' => null,
            'mobileMenuOpen' => false
        ]);
    }

    public function getKnockoutConfig(?int $storeId = null): string
    {
        $config = $this->getConfigArray($storeId);
        $observables = [];

        foreach ($config as $key => $value) {
            $jsValue = is_bool($value) ? ($value ? 'true' : 'false') :
                       (is_string($value) ? "'" . addslashes($value) . "'" : $value);
            $observables[] = sprintf('%s: ko.observable(%s)', $key, $jsValue);
        }

        return '{' . implode(', ', $observables) . '}';
    }

    private function camelCaseToKebab(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }
}
