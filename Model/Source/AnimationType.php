<?php
/**
 * Animation Type Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Animation Type options
 */
class AnimationType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
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
