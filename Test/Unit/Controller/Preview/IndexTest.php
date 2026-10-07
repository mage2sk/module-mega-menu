<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Preview;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Panth\MegaMenu\Controller\Adminhtml\Menu\PreviewToken;
use Panth\MegaMenu\Controller\Preview\Index;
use Panth\MegaMenu\Model\PreviewAccess;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class IndexTest extends TestCase
{
    private array $params = [];
    private array $headers = [];
    private ?string $title = null;
    private array $forwarded = [];
    private CacheInterface&MockObject $cache;
    private Page $page;
    private Forward $forward;
    private PreviewAccess $access;
    private bool $pageFails = false;

    private function controller(array $cacheData = []): Index
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(fn ($name, $default = null) => $this->params[$name] ?? $default);

        $title = $this->createStub(Title::class);
        $title->method('set')->willReturnCallback(function ($value): void {
            $this->title = (string) $value;
        });
        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($title);
        $this->page = $this->createStub(Page::class);
        $this->page->method('getConfig')->willReturn($config);
        $this->page->method('setHeader')->willReturnCallback(function ($name, $value) {
            $this->headers[$name] = $value;
            return $this->page;
        });
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturnCallback(function () {
            if ($this->pageFails) {
                $this->pageFails = false;
                throw new \RuntimeException('layout');
            }
            return $this->page;
        });

        $this->forward = $this->createStub(Forward::class);
        foreach (['setController', 'setModule', 'forward'] as $method) {
            $this->forward->method($method)->willReturnCallback(function ($value) use ($method) {
                $this->forwarded[$method] = $value;
                return $this->forward;
            });
        }
        $forwardFactory = $this->createStub(ForwardFactory::class);
        $forwardFactory->method('create')->willReturn($this->forward);

        $this->cache = $this->createMock(CacheInterface::class);
        $this->cache->method('load')->willReturnCallback(fn ($key) => $cacheData[$key] ?? false);

        $deployment = $this->createStub(DeploymentConfig::class);
        $deployment->method('get')->willReturn('crypt-key');
        $this->access = new PreviewAccess($deployment);

        return new Index(
            $pageFactory,
            $request,
            $this->createStub(LoggerInterface::class),
            $forwardFactory,
            $this->createStub(SessionManagerInterface::class),
            $this->cache,
            $this->access
        );
    }

    public function testValidSignedTokenRendersNoCachePreview(): void
    {
        $controller = $this->controller();
        $this->params = [PreviewAccess::TOKEN_PARAM => $this->access->createToken()];

        $this->assertSame($this->page, $controller->execute());
        $this->assertSame('Menu Preview', $this->title);
        $this->assertSame('noindex, nofollow', $this->headers['X-Robots-Tag']);
        $this->assertStringContainsString('no-store', $this->headers['Cache-Control']);
        $this->assertStringStartsWith('PREVIEW_NOCACHE_', $this->headers['X-Magento-Tags']);
    }

    public function testNoTokenForwardsTo404(): void
    {
        $result = $this->controller()->execute();

        $this->assertSame($this->forward, $result);
        $this->assertSame(['setController' => 'index', 'setModule' => 'cms', 'forward' => 'noroute'], $this->forwarded);
    }

    public function testOneTimeCacheTokenIsConsumed(): void
    {
        $key = PreviewToken::CACHE_PREFIX . 'abc';
        $controller = $this->controller([$key => '5']);
        $this->cache->expects($this->once())->method('remove')->with($key);
        $this->params = ['menu_id' => '5', 'token' => 'abc'];

        $this->assertSame($this->page, $controller->execute());
    }

    public function testCacheTokenForDifferentMenuIsRejected(): void
    {
        $controller = $this->controller([PreviewToken::CACHE_PREFIX . 'abc' => '6']);
        $this->cache->expects($this->never())->method('remove');
        $this->params = ['menu_id' => '5', 'token' => 'abc'];

        $this->assertSame($this->forward, $controller->execute());
    }

    public function testCacheTokenWithInlineItemsIsRejected(): void
    {
        $controller = $this->controller([PreviewToken::CACHE_PREFIX . 'abc' => '5']);
        $this->cache->expects($this->never())->method('load');
        $this->params = ['menu_id' => '5', 'token' => 'abc', 'items_json' => '[]'];

        $this->assertSame($this->forward, $controller->execute());
    }

    public function testUnknownCacheToken(): void
    {
        $controller = $this->controller();
        $this->params = ['menu_id' => '5', 'token' => 'zzz'];

        $this->assertSame($this->forward, $controller->execute());
    }

    public function testRenderFailureShowsErrorPage(): void
    {
        $controller = $this->controller();
        $this->params = [PreviewAccess::TOKEN_PARAM => $this->access->createToken()];
        $this->pageFails = true;

        $this->assertSame($this->page, $controller->execute());
        $this->assertSame('Preview Error', $this->title);
    }

    public function testCsrfValidation(): void
    {
        $controller = $this->controller();

        $this->assertNull($controller->validateForCsrf($this->createStub(RequestInterface::class)));

        $get = $this->createStub(Http::class);
        $get->method('isPost')->willReturn(false);
        $this->assertNull($controller->validateForCsrf($get));

        $token = $this->access->createToken();
        $post = $this->createStub(Http::class);
        $post->method('isPost')->willReturn(true);
        $post->method('getParam')->willReturn($token);
        $this->assertTrue($controller->validateForCsrf($post));

        $badPost = $this->createStub(Http::class);
        $badPost->method('isPost')->willReturn(true);
        $badPost->method('getParam')->willReturn('1.2.3');
        $this->assertFalse($controller->validateForCsrf($badPost));

        $this->assertNull($controller->createCsrfValidationException($post));
    }
}
