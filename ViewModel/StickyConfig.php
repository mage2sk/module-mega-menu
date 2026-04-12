<?php
/**
 * MegaMenu Sticky Configuration ViewModel
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class StickyConfig implements ArgumentInterface
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
     * Check if sticky menu is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configHelper->isStickyMenuEnabled();
    }

    /**
     * Get scroll offset in pixels
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->configHelper->getStickyOffset();
    }

    /**
     * Check if menu should hide on scroll down
     *
     * @return bool
     */
    public function hideOnScrollDown(): bool
    {
        return $this->configHelper->hideOnScrollDown();
    }

    /**
     * Check if menu should show on scroll up
     *
     * @return bool
     */
    public function showOnScrollUp(): bool
    {
        return $this->configHelper->showOnScrollUp();
    }

    /**
     * Check if compact mode is enabled
     *
     * @return bool
     */
    public function isCompactMode(): bool
    {
        return $this->configHelper->isStickyCompactMode();
    }

    /**
     * Get animation speed in milliseconds
     *
     * @return int
     */
    public function getAnimationSpeed(): int
    {
        return $this->configHelper->getStickyAnimationSpeed();
    }

    /**
     * Check if shadow should be shown
     *
     * @return bool
     */
    public function showShadow(): bool
    {
        return $this->configHelper->showStickyShadow();
    }
}
