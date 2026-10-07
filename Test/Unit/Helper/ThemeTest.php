<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\Theme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ThemeTest extends TestCase
{
    private function context(): Context
    {
        $context = $this->createStub(Context::class);
        $context->method('getLogger')->willReturn($this->createStub(LoggerInterface::class));

        return $context;
    }

    private function helper(bool $hyvaModule, string|int|null $configuredTheme, ?string $themePath = null, ?\Throwable $moduleError = null): Theme
    {
        $modules = $this->createStub(ModuleManager::class);
        if ($moduleError) {
            $modules->method('isEnabled')->willThrowException($moduleError);
        } else {
            $modules->method('isEnabled')->willReturn($hyvaModule);
        }
        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getThemePath')->willReturn($themePath);
        $design = $this->createStub(DesignInterface::class);
        $design->method('getConfigurationDesignTheme')->willReturn($configuredTheme);
        $design->method('getDesignTheme')->willReturn($theme);

        return new Theme(
            $this->context(),
            $design,
            $this->createStub(StoreManagerInterface::class),
            $modules
        );
    }

    public static function detectionProvider(): array
    {
        return [
            'hyva module with hyva theme' => [true, 'Hyva/default', null, Theme::THEME_HYVA],
            'hyva module with luma theme path' => [true, 'Magento/luma', null, Theme::THEME_LUMA],
            'hyva module with blank theme' => [true, 'Magento/blank', null, Theme::THEME_LUMA],
            'no module but hyva path' => [false, 'Vendor/hyva-child', null, Theme::THEME_HYVA],
            'no module custom theme' => [false, 'Vendor/custom', null, Theme::THEME_LUMA],
            'numeric theme id resolves path' => [false, 5, 'Hyva/reset', Theme::THEME_HYVA],
            'numeric theme id with null path' => [true, '7', null, Theme::THEME_HYVA],
        ];
    }

    #[DataProvider('detectionProvider')]
    public function testThemeDetection(bool $module, string|int $configured, ?string $path, string $expected): void
    {
        $this->assertSame($expected, $this->helper($module, $configured, $path)->getCurrentTheme());
    }

    public function testModuleManagerErrorYieldsUnknown(): void
    {
        $helper = $this->helper(false, 'x', null, new \RuntimeException('fail'));

        $this->assertSame(Theme::THEME_UNKNOWN, $helper->getCurrentTheme());
        $this->assertFalse($helper->isHyva());
        $this->assertFalse($helper->isLuma());
    }

    public function testResultIsCachedUntilReset(): void
    {
        $modules = $this->createMock(ModuleManager::class);
        $modules->expects($this->exactly(2))->method('isEnabled')->willReturn(true);
        $design = $this->createStub(DesignInterface::class);
        $design->method('getConfigurationDesignTheme')->willReturn('Hyva/default');
        $helper = new Theme($this->context(), $design, $this->createStub(StoreManagerInterface::class), $modules);

        $helper->getCurrentTheme();
        $helper->isHyva();
        $helper->resetCache();
        $this->assertSame(Theme::THEME_HYVA, $helper->getCurrentTheme());
    }

    public function testHyvaHelpers(): void
    {
        $helper = $this->helper(true, 'Hyva/default');

        $this->assertTrue($helper->useAlpineJs());
        $this->assertFalse($helper->useKnockoutJs());
        $this->assertSame('hyva.phtml', $helper->getTemplateForTheme('hyva.phtml', 'luma.phtml'));
        $this->assertSame('hyva', $helper->getThemeClassSuffix());
    }

    public function testLumaThemeConfig(): void
    {
        $config = $this->helper(false, 'Magento/luma')->getThemeConfig();

        $this->assertSame([
            'theme_type' => 'luma',
            'is_hyva' => false,
            'is_luma' => true,
            'use_alpine' => false,
            'use_knockout' => true,
            'css_class_suffix' => 'luma',
            'theme_path' => 'Magento/luma',
        ], $config);
    }

    public function testDesignErrorFallsBackToEmptyPath(): void
    {
        $design = $this->createStub(DesignInterface::class);
        $design->method('getConfigurationDesignTheme')->willThrowException(new \RuntimeException('no store'));
        $modules = $this->createStub(ModuleManager::class);
        $modules->method('isEnabled')->willReturn(false);
        $helper = new Theme($this->context(), $design, $this->createStub(StoreManagerInterface::class), $modules);

        $this->assertSame(Theme::THEME_LUMA, $helper->getCurrentTheme());
        $this->assertSame('', $helper->getThemeConfig()['theme_path']);
    }
}
