<?php
namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Target implements OptionSourceInterface
{
    const TARGET_SELF = '_self';
    const TARGET_BLANK = '_blank';

    public function toOptionArray()
    {
        return [
            ['value' => self::TARGET_SELF, 'label' => __('Same Window/Tab')],
            ['value' => self::TARGET_BLANK, 'label' => __('New Window/Tab')],
        ];
    }
}
