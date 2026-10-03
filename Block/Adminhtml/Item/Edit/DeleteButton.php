<?php
namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData()
    {
        $data = [];
        $itemId = $this->context->getRequest()->getParam('item_id');

        if ($itemId) {
            $data = [
                'label' => __('Delete Item'),
                'class' => 'delete',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to delete this menu item?'),
                    $this->getDeleteUrl()
                ),
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    public function getDeleteUrl()
    {
        $itemId = $this->context->getRequest()->getParam('item_id');
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['item_id' => $itemId]);
    }
}
