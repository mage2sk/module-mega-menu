<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Menu\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class Export implements ButtonProviderInterface
{
    private $request;

    private $urlBuilder;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        $menuId = (int) $this->request->getParam('menu_id');

        if (!$menuId) {
            return [];
        }

        return [
            'label' => __('Export'),
            'class' => 'export action-secondary',
            'on_click' => sprintf("window.location.href = '%s';",
                $this->getExportUrl($menuId)
            ),
            'sort_order' => 26
        ];
    }

    private function getExportUrl(int $menuId): string
    {
        return $this->urlBuilder->getUrl('panth_menu/menu/export', ['menu_id' => $menuId]);
    }
}
