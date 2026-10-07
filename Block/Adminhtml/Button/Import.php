<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Import implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Import'),
            'class' => 'secondary import-menu-button',
            'id' => 'import-menu-button',
            'sort_order' => 20,
            'aclResource' => 'Panth_MegaMenu::menu'
        ];
    }
}
