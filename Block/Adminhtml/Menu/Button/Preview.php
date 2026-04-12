<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block\Adminhtml\Menu\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;

class Preview implements ButtonProviderInterface
{
    private UrlInterface $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData(): array
    {
        $flushUrl = $this->urlBuilder->getUrl('adminhtml/cache/flushSystem');

        return [
            'label' => __('Preview'),
            'class' => 'preview action-secondary',
            'on_click' => sprintf(
                'window.panthPreviewMenu(); return false;'
            ),
            'sort_order' => 25,
            'title' => __('Preview unsaved menu changes. If preview shows stale data, click "Flush & Preview" instead.'),
        ];
    }
}
