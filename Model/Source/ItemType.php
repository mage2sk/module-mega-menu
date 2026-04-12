<?php
/**
 * Panth MegaMenu Item Type Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ItemType implements OptionSourceInterface
{
    /**
     * Item type constants
     */
    const TYPE_CATEGORY = 'category';
    const TYPE_CMS_PAGE = 'cms_page';
    const TYPE_CUSTOM_URL = 'custom_url';
    const TYPE_PRODUCT = 'product';
    const TYPE_HTML = 'html';
    const TYPE_WIDGET = 'widget';
    const TYPE_SEPARATOR = 'separator';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_CATEGORY, 'label' => __('Category')],
            ['value' => self::TYPE_CMS_PAGE, 'label' => __('CMS Page')],
            ['value' => self::TYPE_CUSTOM_URL, 'label' => __('Custom URL')],
            ['value' => self::TYPE_PRODUCT, 'label' => __('Product')],
            ['value' => self::TYPE_HTML, 'label' => __('HTML Content')],
            ['value' => self::TYPE_WIDGET, 'label' => __('Widget')],
            ['value' => self::TYPE_SEPARATOR, 'label' => __('Separator')],
        ];
    }
}
