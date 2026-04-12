<?php
/**
 * Font Weight Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Font Weight options
 */
class FontWeight implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('-- Default --')],
            ['value' => '100', 'label' => __('100 - Thin')],
            ['value' => '200', 'label' => __('200 - Extra Light')],
            ['value' => '300', 'label' => __('300 - Light')],
            ['value' => '400', 'label' => __('400 - Normal')],
            ['value' => '500', 'label' => __('500 - Medium')],
            ['value' => '600', 'label' => __('600 - Semi Bold')],
            ['value' => '700', 'label' => __('700 - Bold')],
            ['value' => '800', 'label' => __('800 - Extra Bold')],
            ['value' => '900', 'label' => __('900 - Black')],
        ];
    }
}
