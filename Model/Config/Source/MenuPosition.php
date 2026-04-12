<?php
namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MenuPosition implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'left', 'label' => __('Left')],
            ['value' => 'right', 'label' => __('Right')]
        ];
    }
}
