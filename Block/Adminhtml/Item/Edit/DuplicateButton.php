<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DuplicateButton implements ButtonProviderInterface
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

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
