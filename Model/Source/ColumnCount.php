<?php
/**
 * Panth MegaMenu Column Count Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ColumnCount implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('1 Column')],
            ['value' => '2', 'label' => __('2 Columns')],
            ['value' => '3', 'label' => __('3 Columns')],
            ['value' => '4', 'label' => __('4 Columns')],
            ['value' => '5', 'label' => __('5 Columns')],
            ['value' => '6', 'label' => __('6 Columns')],
        ];
    }
}
