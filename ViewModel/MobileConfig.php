<?php
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class MobileConfig implements ArgumentInterface
{
    private $configHelper;

    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function isEnabled(): bool
    {
        return $this->configHelper->isMobileEnabled();
    }

    public function getPosition(): string
    {
        return $this->configHelper->getMobilePosition();
    }

    public function isOverlayEnabled(): bool
    {
        return $this->configHelper->isMobileOverlayEnabled();
    }

    public function isSwipeEnabled(): bool
    {
        return $this->configHelper->isMobileSwipeEnabled();
    }

    public function isAccordionEnabled(): bool
    {
        return $this->configHelper->isMobileAccordionEnabled();
    }

    public function getAnimationSpeed(): int
    {
        return $this->configHelper->getMobileAnimationSpeed();
    }

    public function showCategoryIcons(): bool
    {
        return $this->configHelper->showMobileCategoryIcons();
    }
}
