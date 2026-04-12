<?php
/**
 * Preview Button
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Item\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

/**
 * Preview Item Button
 */
class PreviewButton implements ButtonProviderInterface
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
