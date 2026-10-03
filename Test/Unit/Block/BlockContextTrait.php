<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

trait BlockContextTrait
{
    private array $requestParams = [];
    private array $requestPost = [];

    private function blockContext(
        ?LayoutInterface $layout = null,
        ?UrlInterface $url = null,
        ?ScopeConfigInterface $scopeConfig = null
    ): Context {
        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getId')->willReturn(3);
        $design = $this->createStub(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($theme);

        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(fn ($name, $default = null) => $this->requestParams[$name] ?? $default);
        $request->method('getPostValue')->willReturnCallback(
            fn ($name = null, $default = null) => $name === null ? $this->requestPost : ($this->requestPost[$name] ?? $default)
        );

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $context = $this->createStub(Context::class);
        $context->method('getEscaper')->willReturn($this->realEscaper());
        $context->method('getDesignPackage')->willReturn($design);
        $context->method('getRequest')->willReturn($request);
        $context->method('getStoreManager')->willReturn($storeManager);
        $context->method('getLogger')->willReturn($this->createStub(LoggerInterface::class));
        if ($layout !== null) {
            $context->method('getLayout')->willReturn($layout);
        }
        if ($url !== null) {
            $context->method('getUrlBuilder')->willReturn($url);
        }
        if ($scopeConfig !== null) {
            $context->method('getScopeConfig')->willReturn($scopeConfig);
        }

        return $context;
    }
}
