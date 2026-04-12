<?php
/**
 * Alignment Source Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Alignment options
 */
class Alignment implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'left', 'label' => __('Left')],
            ['value' => 'center', 'label' => __('Center')],
            ['value' => 'right', 'label' => __('Right')],
            ['value' => 'full', 'label' => __('Full Width')],
        ];
    }
}
