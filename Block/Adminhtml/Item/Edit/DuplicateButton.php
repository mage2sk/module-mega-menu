<?php
/**
 * Duplicate Button
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

/**
 * Duplicate Item Button
 */
class DuplicateButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $itemId = $this->context->getRequest()->getParam('item_id');

        if (!$itemId) {
            return [];
        }

        return [
            'label' => __('Duplicate'),
            'class' => 'duplicate',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to duplicate this item?'),
                $this->context->getUrlBuilder()->getUrl('*/*/duplicate', ['item_id' => $itemId])
            ),
            'sort_order' => 30,
        ];
    }
}
