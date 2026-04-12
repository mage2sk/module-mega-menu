<?php
/**
 * Panth MegaMenu - Configuration Helper
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_MEGAMENU_ENABLED = 'panth_megamenu/general/enabled';
    const XML_PATH_DEBUG_ENABLED = 'panth_megamenu/advanced/enable_debug';
    const XML_PATH_CACHE_ENABLED = 'panth_megamenu/performance/cache_enabled';
    const XML_PATH_CACHE_LIFETIME = 'panth_megamenu/performance/cache_lifetime';
    const XML_PATH_ANIMATION_TYPE = 'panth_megamenu/display/animation_type';
    const XML_PATH_ANIMATION_DURATION = 'panth_megamenu/display/animation_duration';
    const XML_PATH_STICKY_MENU = 'panth_megamenu/general/sticky_menu';
    const XML_PATH_MOBILE_ENABLED = 'panth_megamenu/general/mobile_enabled';

    /**
     * Check if MegaMenu is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MEGAMENU_ENABLED,
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
            self::XML_PATH_DEBUG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
        );
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
        );
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
     * Get config value
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
}
