<?php
/**
 * Mobile Menu Position Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MobilePosition implements OptionSourceInterface
{
    /**
     * Get mobile menu position options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'left', 'label' => __('Left')],
            ['value' => 'right', 'label' => __('Right')],
        ];
    }
}
