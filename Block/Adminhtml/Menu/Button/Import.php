<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Menu\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Import implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Import'),
            'class' => 'secondary import-menu-button-form',
            'id' => 'import-menu-button-form',
            'sort_order' => 27
        ];
    }
}
