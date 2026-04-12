<?php
/**
 * Reset Button
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Reset to Defaults Button
 */
class ResetButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Reset to Defaults'),
            'class' => 'reset',
            'on_click' => 'location.reload();',
            'sort_order' => 20,
        ];
    }
}
