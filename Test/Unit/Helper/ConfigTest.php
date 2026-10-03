<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Panth\MegaMenu\Helper\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private function helper(ScopeConfigInterface $scopeConfig): Config
    {
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Config($context);
    }

    public static function flagProvider(): array
    {
        return [
            ['isEnabled', Config::XML_PATH_MEGAMENU_ENABLED],
            ['isDebugEnabled', Config::XML_PATH_DEBUG_ENABLED],
            ['isCacheEnabled', Config::XML_PATH_CACHE_ENABLED],
            ['isStickyMenuEnabled', Config::XML_PATH_STICKY_MENU],
            ['isMobileEnabled', Config::XML_PATH_MOBILE_ENABLED],
        ];
    }

    #[DataProvider('flagProvider')]
    public function testFlagsReadStoreScopedPath(string $method, string $path): void
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->expects($this->once())->method('isSetFlag')
            ->with($path, ScopeInterface::SCOPE_STORE, 3)
            ->willReturn(true);

        $this->assertTrue($this->helper($scope)->$method(3));
    }

    public function testFlagFalseWhenNotSet(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->helper($scope)->isEnabled());
    }

    public function testIntegerValuesAreCast(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnMap([
            [Config::XML_PATH_CACHE_LIFETIME, ScopeInterface::SCOPE_STORE, null, '3600'],
            [Config::XML_PATH_ANIMATION_DURATION, ScopeInterface::SCOPE_STORE, null, null],
            [Config::XML_PATH_ANIMATION_TYPE, ScopeInterface::SCOPE_STORE, null, 'fade'],
        ]);
        $helper = $this->helper($scope);

        $this->assertSame(3600, $helper->getCacheLifetime());
        $this->assertSame(0, $helper->getAnimationDuration());
        $this->assertSame('fade', $helper->getAnimationType());
    }

    public function testAnimationTypeEmptyStringWhenUnset(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn(null);

        $this->assertSame('', $this->helper($scope)->getAnimationType());
    }

    public function testGetConfigValuePassesThroughRawValue(): void
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->expects($this->once())->method('getValue')
            ->with('a/b/c', ScopeInterface::SCOPE_STORE, 2)
            ->willReturn(['x' => 1]);

        $this->assertSame(['x' => 1], $this->helper($scope)->getConfigValue('a/b/c', 2));
    }
}
