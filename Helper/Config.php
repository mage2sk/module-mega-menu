<?php
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

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MEGAMENU_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
        );
    }

    public function getAnimationType(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAnimationDuration(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_ANIMATION_DURATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isStickyMenuEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STICKY_MENU,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isMobileEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MOBILE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getConfigValue(string $path, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
