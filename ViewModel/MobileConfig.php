<?php
/**
 * MegaMenu Mobile Configuration ViewModel
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class MobileConfig implements ArgumentInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Check if mobile mega menu is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configHelper->isMobileEnabled();
    }

    /**
     * Get menu position (left or right)
     *
     * @return string
     */
    public function getPosition(): string
    {
        return $this->configHelper->getMobilePosition();
    }

    /**
     * Check if overlay is enabled
     *
     * @return bool
     */
    public function isOverlayEnabled(): bool
    {
        return $this->configHelper->isMobileOverlayEnabled();
    }

    /**
     * Check if swipe to close is enabled
     *
     * @return bool
     */
    public function isSwipeEnabled(): bool
    {
        return $this->configHelper->isMobileSwipeEnabled();
    }

    /**
     * Check if accordion menu is enabled
     *
     * @return bool
     */
    public function isAccordionEnabled(): bool
    {
        return $this->configHelper->isMobileAccordionEnabled();
    }

    /**
     * Get animation speed in milliseconds
     *
     * @return int
     */
    public function getAnimationSpeed(): int
    {
        return $this->configHelper->getMobileAnimationSpeed();
    }

    /**
     * Check if category icons should be shown
     *
     * @return bool
     */
    public function showCategoryIcons(): bool
    {
        return $this->configHelper->showMobileCategoryIcons();
    }
}
