<?php
/**
 * Configuration ViewModel - Theme-Agnostic Configuration Provider
 *
 * Provides centralized configuration access for both Hyva (Alpine.js) and Luma (KnockoutJS) themes.
 * This ViewModel consolidates all MegaMenu configuration settings in one place.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;

class Config implements ArgumentInterface
{
    /**
     * @var MenuHelper
     */
    private $menuHelper;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Constructor
     *
     * @param MenuHelper $menuHelper
     * @param Json $jsonSerializer
     */
    public function __construct(
        MenuHelper $menuHelper,
        Json $jsonSerializer
    ) {
        $this->menuHelper = $menuHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    // ===== General Configuration =====

    /**
     * Check if MegaMenu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isEnabled($storeId);
    }

    /**
     * Check if mobile menu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileEnabled($storeId);
    }

    /**
     * Get mobile breakpoint in pixels
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMobileBreakpoint(?int $storeId = null): int
    {
        return $this->menuHelper->getMobileBreakpoint($storeId);
    }

    /**
     * Check if sticky menu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isStickyMenuEnabled($storeId);
    }

    /**
     * Get sticky menu scroll offset
     *
     * @param int|null $storeId
     * @return int
     */
    public function getStickyOffset(?int $storeId = null): int
    {
        return $this->menuHelper->getStickyOffset($storeId);
    }

    /**
     * Get maximum menu depth
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxDepth(?int $storeId = null): int
    {
        return $this->menuHelper->getMaxDepth($storeId);
    }

    // ===== Animation & Display Configuration =====

    /**
     * Get animation type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAnimationType(?int $storeId = null): string
    {
        return $this->menuHelper->getAnimationType($storeId);
    }

    /**
     * Get animation duration in milliseconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAnimationDuration(?int $storeId = null): int
    {
        return $this->menuHelper->getAnimationDuration($storeId);
    }

    /**
     * Get hover intent delay in milliseconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getHoverIntentDelay(?int $storeId = null): int
    {
        return $this->menuHelper->getHoverIntentDelay($storeId);
    }

    /**
     * Check if icons should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showIcons(?int $storeId = null): bool
    {
        return $this->menuHelper->showIcons($storeId);
    }

    /**
     * Check if images should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showImages(?int $storeId = null): bool
    {
        return $this->menuHelper->showImages($storeId);
    }

    /**
     * Get hover effect type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHoverEffect(?int $storeId = null): string
    {
        return $this->menuHelper->getHoverEffect($storeId);
    }

    /**
     * Get image size
     *
     * @param int|null $storeId
     * @return string
     */
    public function getImageSize(?int $storeId = null): string
    {
        return $this->menuHelper->getImageSize($storeId);
    }

    /**
     * Get number of columns for dropdown
     *
     * @param int|null $storeId
     * @return int
     */
    public function getColumns(?int $storeId = null): int
    {
        return $this->menuHelper->getColumns($storeId);
    }

    // ===== Performance Configuration =====

    /**
     * Check if cache is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCacheEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isCacheEnabled($storeId);
    }

    /**
     * Get cache lifetime in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCacheLifetime(?int $storeId = null): int
    {
        return $this->menuHelper->getCacheLifetime($storeId);
    }

    /**
     * Check if lazy load is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isLazyLoadEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isLazyLoadEnabled($storeId);
    }

    // ===== Mobile Configuration =====

    /**
     * Get mobile menu position
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMobilePosition(?int $storeId = null): string
    {
        return $this->menuHelper->getMobilePosition($storeId);
    }

    /**
     * Check if mobile overlay is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileOverlayEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileOverlayEnabled($storeId);
    }

    /**
     * Check if mobile swipe is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileSwipeEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileSwipeEnabled($storeId);
    }

    /**
     * Check if mobile accordion is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isMobileAccordionEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isMobileAccordionEnabled($storeId);
    }

    /**
     * Get mobile animation speed in milliseconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMobileAnimationSpeed(?int $storeId = null): int
    {
        return $this->menuHelper->getMobileAnimationSpeed($storeId);
    }

    /**
     * Check if mobile category icons should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showMobileCategoryIcons(?int $storeId = null): bool
    {
        return $this->menuHelper->showMobileCategoryIcons($storeId);
    }

    // ===== Sticky Menu Configuration =====

    /**
     * Check if sticky menu should hide on scroll down
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hideOnScrollDown(?int $storeId = null): bool
    {
        return $this->menuHelper->hideOnScrollDown($storeId);
    }

    /**
     * Check if sticky menu should show on scroll up
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showOnScrollUp(?int $storeId = null): bool
    {
        return $this->menuHelper->showOnScrollUp($storeId);
    }

    /**
     * Check if sticky compact mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStickyCompactMode(?int $storeId = null): bool
    {
        return $this->menuHelper->isStickyCompactMode($storeId);
    }

    /**
     * Get sticky animation speed in milliseconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getStickyAnimationSpeed(?int $storeId = null): int
    {
        return $this->menuHelper->getStickyAnimationSpeed($storeId);
    }

    /**
     * Check if sticky shadow should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showStickyShadow(?int $storeId = null): bool
    {
        return $this->menuHelper->showStickyShadow($storeId);
    }

    // ===== Styling Configuration =====

    /**
     * Get menu background color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMenuBackgroundColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuBackgroundColor($storeId);
    }

    /**
     * Get menu text color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMenuTextColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuTextColor($storeId);
    }

    /**
     * Get menu hover color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMenuHoverColor(?int $storeId = null): string
    {
        return $this->menuHelper->getMenuHoverColor($storeId);
    }

    /**
     * Get dropdown background color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDropdownBackgroundColor(?int $storeId = null): string
    {
        return $this->menuHelper->getDropdownBackgroundColor($storeId);
    }

    /**
     * Get dropdown border color
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDropdownBorderColor(?int $storeId = null): string
    {
        return $this->menuHelper->getDropdownBorderColor($storeId);
    }

    /**
     * Get custom CSS
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomCss(?int $storeId = null): string
    {
        return $this->menuHelper->getCustomCss($storeId);
    }

    /**
     * Get custom JavaScript
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCustomJs(?int $storeId = null): string
    {
        return $this->menuHelper->getCustomJs($storeId);
    }

    // ===== Advanced Configuration =====

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isDebugEnabled($storeId);
    }

    /**
     * Check if custom blocks are enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCustomBlocksEnabled(?int $storeId = null): bool
    {
        return $this->menuHelper->isCustomBlocksEnabled($storeId);
    }

    /**
     * Check if category count should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function showCategoryCount(?int $storeId = null): bool
    {
        return $this->menuHelper->showCategoryCount($storeId);
    }

    // ===== JSON Configuration Export =====

    /**
     * Get all configuration as JSON for JavaScript initialization
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConfigJson(?int $storeId = null): string
    {
        return $this->menuHelper->getConfigJson($storeId);
    }

    /**
     * Get configuration array for template use
     *
     * @param int|null $storeId
     * @return array
     */
    public function getConfigArray(?int $storeId = null): array
    {
        return [
            // General
            'enabled' => $this->isEnabled($storeId),
            'mobileEnabled' => $this->isMobileEnabled($storeId),
            'mobileBreakpoint' => $this->getMobileBreakpoint($storeId),
            'stickyEnabled' => $this->isStickyEnabled($storeId),
            'stickyOffset' => $this->getStickyOffset($storeId),
            'maxDepth' => $this->getMaxDepth($storeId),

            // Animation & Display
            'animationType' => $this->getAnimationType($storeId),
            'animationDuration' => $this->getAnimationDuration($storeId),
            'hoverIntentDelay' => $this->getHoverIntentDelay($storeId),
            'showIcons' => $this->showIcons($storeId),
            'showImages' => $this->showImages($storeId),
            'hoverEffect' => $this->getHoverEffect($storeId),
            'imageSize' => $this->getImageSize($storeId),
            'columns' => $this->getColumns($storeId),

            // Performance
            'cacheEnabled' => $this->isCacheEnabled($storeId),
            'cacheLifetime' => $this->getCacheLifetime($storeId),
            'lazyLoad' => $this->isLazyLoadEnabled($storeId),

            // Mobile
            'mobilePosition' => $this->getMobilePosition($storeId),
            'mobileOverlay' => $this->isMobileOverlayEnabled($storeId),
            'mobileSwipe' => $this->isMobileSwipeEnabled($storeId),
            'mobileAccordion' => $this->isMobileAccordionEnabled($storeId),
            'mobileAnimationSpeed' => $this->getMobileAnimationSpeed($storeId),
            'mobileCategoryIcons' => $this->showMobileCategoryIcons($storeId),

            // Sticky
            'stickyHideOnScrollDown' => $this->hideOnScrollDown($storeId),
            'stickyShowOnScrollUp' => $this->showOnScrollUp($storeId),
            'stickyCompactMode' => $this->isStickyCompactMode($storeId),
            'stickyAnimationSpeed' => $this->getStickyAnimationSpeed($storeId),
            'stickyShowShadow' => $this->showStickyShadow($storeId),

            // Styling
            'menuBackgroundColor' => $this->getMenuBackgroundColor($storeId),
            'menuTextColor' => $this->getMenuTextColor($storeId),
            'menuHoverColor' => $this->getMenuHoverColor($storeId),
            'dropdownBackgroundColor' => $this->getDropdownBackgroundColor($storeId),
            'dropdownBorderColor' => $this->getDropdownBorderColor($storeId),

            // Advanced
            'debugEnabled' => $this->isDebugEnabled($storeId),
            'customBlocksEnabled' => $this->isCustomBlocksEnabled($storeId),
            'showCategoryCount' => $this->showCategoryCount($storeId)
        ];
    }

    /**
     * Get configuration data attributes for HTML elements
     * Useful for passing config to JavaScript/Alpine.js
     *
     * @param int|null $storeId
     * @return string
     */
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

    /**
     * Get Alpine.js x-data initialization object
     *
     * @param int|null $storeId
     * @return string
     */
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

    /**
     * Get KnockoutJS observable configuration
     *
     * @param int|null $storeId
     * @return string
     */
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

    /**
     * Convert camelCase to kebab-case
     *
     * @param string $string
     * @return string
     */
    private function camelCaseToKebab(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }
}
