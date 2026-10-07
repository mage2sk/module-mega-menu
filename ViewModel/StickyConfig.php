<?php
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class StickyConfig implements ArgumentInterface
{
    private $configHelper;

    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function isEnabled(): bool
    {
        return $this->configHelper->isStickyMenuEnabled();
    }

    public function getOffset(): int
    {
        return $this->configHelper->getStickyOffset();
    }

    public function hideOnScrollDown(): bool
    {
        return $this->configHelper->hideOnScrollDown();
    }

    public function showOnScrollUp(): bool
    {
        return $this->configHelper->showOnScrollUp();
    }

    public function isCompactMode(): bool
    {
        return $this->configHelper->isStickyCompactMode();
    }

    public function getAnimationSpeed(): int
    {
        return $this->configHelper->getStickyAnimationSpeed();
    }

    public function showShadow(): bool
    {
        return $this->configHelper->showStickyShadow();
    }
}
