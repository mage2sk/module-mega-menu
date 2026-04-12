<?php
/**
 * Panth MegaMenu Menu Type Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MenuType implements OptionSourceInterface
{
    /**
     * Menu type constants
     */
    const TYPE_HEADER = 'header';
    const TYPE_FOOTER = 'footer';
    const TYPE_SIDEBAR = 'sidebar';
    const TYPE_MOBILE = 'mobile';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_HEADER, 'label' => __('Header Menu')],
            ['value' => self::TYPE_FOOTER, 'label' => __('Footer Menu')],
            ['value' => self::TYPE_SIDEBAR, 'label' => __('Sidebar Menu')],
            ['value' => self::TYPE_MOBILE, 'label' => __('Mobile Menu')],
        ];
    }
}
