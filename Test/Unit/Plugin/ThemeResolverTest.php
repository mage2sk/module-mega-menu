<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Plugin;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\DesignInterface;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Panth\MegaMenu\Plugin\ThemeResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ThemeResolverTest extends TestCase
{
    private function resolver(ThemeHelper $helper): ThemeResolver
    {
        return new ThemeResolver($helper, $this->createStub(LoggerInterface::class), $this->createStub(HttpRequest::class));
    }

    public function testAfterGetDesignThemeResolvesOnceAndReturnsResult(): void
    {
        $helper = $this->createMock(ThemeHelper::class);
        $helper->expects($this->once())->method('getCurrentTheme')->willReturn('hyva');
        $resolver = $this->resolver($helper);
        $design = $this->createStub(DesignInterface::class);
        $theme = new \stdClass();

        $this->assertSame($theme, $resolver->afterGetDesignTheme($design, $theme));
        $this->assertSame($theme, $resolver->afterGetDesignTheme($design, $theme));
    }

    public function testHelperFailureDoesNotBreakDesignResolution(): void
    {
        $helper = $this->createStub(ThemeHelper::class);
        $helper->method('getCurrentTheme')->willThrowException(new \RuntimeException('x'));

        $this->assertSame('theme', $this->resolver($helper)->afterGetDesignTheme($this->createStub(DesignInterface::class), 'theme'));
    }

    public function testSetDesignThemeResetsCacheAndAllowsReResolution(): void
    {
        $helper = $this->createMock(ThemeHelper::class);
        $helper->expects($this->once())->method('resetCache');
        $helper->expects($this->exactly(2))->method('getCurrentTheme')->willReturn('luma');
        $resolver = $this->resolver($helper);
        $design = $this->createStub(DesignInterface::class);

        $resolver->afterGetDesignTheme($design, null);
        $result = $resolver->aroundSetDesignTheme($design, function ($theme, $params) {
            $this->assertSame('Magento/luma', $theme);
            $this->assertSame(['area' => 'frontend'], $params);
            return 'set';
        }, 'Magento/luma', ['area' => 'frontend']);
        $resolver->afterGetDesignTheme($design, null);

        $this->assertSame('set', $result);
    }

    public function testBeforeLoadLayoutResolvesOnlyFirstTime(): void
    {
        $helper = $this->createMock(ThemeHelper::class);
        $helper->expects($this->once())->method('getCurrentTheme')->willReturn('hyva');
        $resolver = $this->resolver($helper);

        $this->assertSame(['default'], $resolver->beforeLoadLayout(new \stdClass(), 'default'));
        $this->assertSame([null], $resolver->beforeLoadLayout(new \stdClass()));
    }
}
