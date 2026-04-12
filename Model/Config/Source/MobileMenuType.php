<?php
/**
 * Mobile Menu Type Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MobileMenuType implements OptionSourceInterface
{
    /**
     * Get mobile menu type options
     *
     * @return array
     */
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
