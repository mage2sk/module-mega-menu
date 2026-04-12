<?php
/**
 * Panth MegaMenu Link Target Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Target implements OptionSourceInterface
{
    /**
     * Link target constants
     */
    const TARGET_SELF = '_self';
    const TARGET_BLANK = '_blank';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TARGET_SELF, 'label' => __('Same Window/Tab')],
            ['value' => self::TARGET_BLANK, 'label' => __('New Window/Tab')],
        ];
    }
}
