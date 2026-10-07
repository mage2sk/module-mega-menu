<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Flag;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;
use Magento\Framework\HTTP\Client\Curl;
use Panth\MegaMenu\Helper\Webhook;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WebhookTest extends TestCase
{
    use FlagTestTrait;

    private Curl&MockObject $curl;
    private FlagResource&MockObject $flagResource;
    private array $flags = [];

    private function webhook(?string $url, ?string $lastUpdate = null): Webhook
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnMap([['panth_megamenu/advanced/webhook_url', 'default', null, $url]]);
        $request = $this->createStub(Http::class);
        $request->method('getServer')->willReturn('shop.test');
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scope);
        $context->method('getRequest')->willReturn($request);

        $this->curl = $this->createMock(Curl::class);
        $this->flagResource = $this->createMock(FlagResource::class);
        $this->flagResource->method('load')->willReturnCallback(function (Flag $flag) use ($lastUpdate) {
            if ($lastUpdate !== null) {
                $flag->setData('last_update', $lastUpdate);
            }
            return $this->flagResource;
        });
        $factory = $this->createStub(FlagFactory::class);
        $factory->method('create')->willReturnCallback(function (array $args) {
            $flag = $this->newFlag($args['data']);
            $this->flags[] = $flag;
            return $flag;
        });
        $metadata = $this->createStub(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');

        return new Webhook($context, $this->curl, $factory, $this->flagResource, $metadata);
    }

    public function testPostsPayloadAndMarksSentOnSuccess(): void
    {
        $webhook = $this->webhook('https://hooks.test/x');
        $payload = null;
        $this->curl->expects($this->once())->method('post')
            ->willReturnCallback(function (string $url, string $body) use (&$payload): void {
                $this->assertSame('https://hooks.test/x', $url);
                $payload = json_decode($body, true);
            });
        $this->curl->method('getStatus')->willReturn(204);
        $this->flagResource->expects($this->once())->method('save');

        $this->assertTrue($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save', 'extra' => 1]));
        $this->assertSame('mm', $payload['module']);
        $this->assertSame(1, $payload['extra']);
        $this->assertSame('shop.test', $payload['domain']);
        $this->assertSame('2.4.8', $payload['magento_version']);
        $saved = end($this->flags);
        $this->assertSame('panth_megamenu_webhook_mm_save', $saved->getFlagCode());
        $this->assertSame(1, $saved->getFlagData());
    }

    public function testNonSuccessStatusDoesNotMarkSent(): void
    {
        $webhook = $this->webhook('https://hooks.test/x');
        $this->curl->method('getStatus')->willReturn(500);
        $this->flagResource->expects($this->never())->method('save');

        $this->assertFalse($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save']));
    }

    public function testNoUrlConfiguredSkipsRequest(): void
    {
        $webhook = $this->webhook(null);
        $this->curl->expects($this->never())->method('post');

        $this->assertFalse($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save']));
    }

    public function testRecentlySentNotificationIsThrottled(): void
    {
        $webhook = $this->webhook('https://hooks.test/x', date('Y-m-d H:i:s', time() - 3600));
        $this->curl->expects($this->never())->method('post');

        $this->assertFalse($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save']));
    }

    public function testOldNotificationIsResent(): void
    {
        $webhook = $this->webhook('https://hooks.test/x', date('Y-m-d H:i:s', time() - Webhook::WEBHOOK_INTERVAL - 10));
        $this->curl->expects($this->once())->method('post');
        $this->curl->method('getStatus')->willReturn(200);

        $this->assertTrue($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save']));
    }

    public function testTransportExceptionIsSwallowed(): void
    {
        $webhook = $this->webhook('https://hooks.test/x');
        $this->curl->method('post')->willThrowException(new \Exception('timeout'));

        $this->assertFalse($webhook->sendConfigChangeNotification(['module' => 'mm', 'action' => 'save']));
    }
}
