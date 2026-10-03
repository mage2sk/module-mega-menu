<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayMode implements OptionSourceInterface
{
    const MODE_DROPDOWN = 'dropdown';
    const MODE_MEGA = 'mega';
    const MODE_FLYOUT = 'flyout';

    public function toOptionArray()
    {
        return [
            ['value' => self::MODE_DROPDOWN, 'label' => __('Standard Dropdown')],
            ['value' => self::MODE_MEGA, 'label' => __('Mega Menu')],
            ['value' => self::MODE_FLYOUT, 'label' => __('Flyout Menu')],
        ];
    }
}
