<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Menu\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class Export implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return array
     */
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

    /**
     * Get export URL
     *
     * @param int $menuId
     * @return string
     */
    private function getExportUrl(int $menuId): string
    {
        return $this->urlBuilder->getUrl('panth_menu/menu/export', ['menu_id' => $menuId]);
    }
}
