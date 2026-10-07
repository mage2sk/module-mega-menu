<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MobileMenuType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'slide', 'label' => __('Slide Out')],
            ['value' => 'overlay', 'label' => __('Full Screen Overlay')],
            ['value' => 'dropdown', 'label' => __('Dropdown')],
            ['value' => 'accordion', 'label' => __('Accordion')],
        ];
    }
}
