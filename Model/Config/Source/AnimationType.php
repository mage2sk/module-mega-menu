<?php
/**
 * Animation Type Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AnimationType implements OptionSourceInterface
{
    /**
     * Get animation type options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'none', 'label' => __('None')],
            ['value' => 'fade', 'label' => __('Fade')],
            ['value' => 'slide', 'label' => __('Slide')],
            ['value' => 'grow', 'label' => __('Grow')],
        ];
    }
}
