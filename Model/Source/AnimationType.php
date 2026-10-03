<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AnimationType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'none', 'label' => __('None')],
            ['value' => 'fade', 'label' => __('Fade')],
            ['value' => 'slide', 'label' => __('Slide Down')],
            ['value' => 'slide-up', 'label' => __('Slide Up')],
            ['value' => 'zoom', 'label' => __('Zoom')],
            ['value' => 'bounce', 'label' => __('Bounce')],
            ['value' => 'flip', 'label' => __('Flip')],
            ['value' => 'rotate', 'label' => __('Rotate')],
        ];
    }
}
