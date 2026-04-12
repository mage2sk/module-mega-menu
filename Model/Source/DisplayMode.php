<?php
/**
 * Panth MegaMenu Display Mode Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayMode implements OptionSourceInterface
{
    /**
     * Display mode constants
     */
    const MODE_DROPDOWN = 'dropdown';
    const MODE_MEGA = 'mega';
    const MODE_FLYOUT = 'flyout';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::MODE_DROPDOWN, 'label' => __('Standard Dropdown')],
            ['value' => self::MODE_MEGA, 'label' => __('Mega Menu')],
            ['value' => self::MODE_FLYOUT, 'label' => __('Flyout Menu')],
        ];
    }
}
