<?php
/**
 * Hover Effect Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HoverEffect implements OptionSourceInterface
{
    /**
     * Get hover effect options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'none', 'label' => __('None')],
            ['value' => 'underline', 'label' => __('Underline')],
            ['value' => 'fade', 'label' => __('Fade')],
            ['value' => 'highlight', 'label' => __('Highlight')],
            ['value' => 'slide', 'label' => __('Slide')],
            ['value' => 'glow', 'label' => __('Glow')],
            ['value' => 'grow', 'label' => __('Grow')],
        ];
    }

    /**
     * Get options as key-value pairs
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'none' => __('None'),
            'underline' => __('Underline'),
            'fade' => __('Fade'),
            'highlight' => __('Highlight'),
            'slide' => __('Slide'),
            'glow' => __('Glow'),
            'grow' => __('Grow'),
        ];
    }
}
