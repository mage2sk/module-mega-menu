<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Menu;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ImportButton implements ButtonProviderInterface
{
    public function getButtonData()
    {
        return [
            'label' => __('Import'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
