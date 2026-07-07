<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class PreviewButton implements ButtonProviderInterface
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
            'label' => __('Preview'),
            'class' => 'preview',
            'on_click' => sprintf(
                "window.open('%s', '_blank');",
                $this->context->getUrlBuilder()->getUrl('*/*/preview', ['item_id' => $itemId])
            ),
            'sort_order' => 35,
        ];
    }
}
