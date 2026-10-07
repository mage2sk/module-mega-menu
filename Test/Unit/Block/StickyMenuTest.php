<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Theme\Block\Html\Header\Logo;
use Panth\MegaMenu\Block\StickyMenu;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\ConfigHelperTrait;
use PHPUnit\Framework\TestCase;

class StickyMenuTest extends TestCase
{
    use BlockContextTrait;
    use ConfigHelperTrait;
    use EscaperTrait;

    private function block(?string $storeName): StickyMenu
    {
        $logo = $this->createStub(Logo::class);
        $logo->method('getLogoSrc')->willReturn('https://shop.test/logo.svg');
        $logo->method('getLogoAlt')->willReturn('Shop');
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn($storeName);
        $context = $this->blockContext(null, null, $scope);

        return new StickyMenu($context, $logo, $context->getStoreManager(), $this->dataHelper());
    }

    public function testLogoStoreNameAndCacheKey(): void
    {
        $block = $this->block('Agri Shop');

        $this->assertSame('https://shop.test/logo.svg', $block->getLogoSrc());
        $this->assertSame('Shop', $block->getLogoAlt());
        $this->assertSame('Agri Shop', $block->getStoreName());
        $this->assertSame(['STICKY_MENU', 1, 3], $block->getCacheKeyInfo());
    }

    public function testMissingStoreNameReturnsEmptyString(): void
    {
        $this->assertSame('', $this->block(null)->getStoreName());
    }
}
