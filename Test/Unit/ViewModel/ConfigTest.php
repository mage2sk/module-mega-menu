<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\ViewModel\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    use ConfigHelperTrait;

    private function viewModel(array $values = []): Config
    {
        return new Config($this->dataHelper($values), new Json());
    }

    public function testConfigArrayReflectsHelperDefaults(): void
    {
        $config = $this->viewModel([Data::XML_PATH_ENABLED => 1, Data::XML_PATH_STICKY_MENU => 1])->getConfigArray();

        $this->assertCount(36, $config);
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['stickyEnabled']);
        $this->assertFalse($config['mobileEnabled']);
        $this->assertSame(1024, $config['mobileBreakpoint']);
        $this->assertSame(100, $config['stickyOffset']);
        $this->assertSame('fade', $config['animationType']);
        $this->assertSame('underline', $config['hoverEffect']);
        $this->assertSame(3600, $config['cacheLifetime']);
        $this->assertSame('', $config['menuBackgroundColor']);
    }

    public function testDataAttributesUseKebabCaseAndEscapeValues(): void
    {
        $attributes = $this->viewModel([
            Data::XML_PATH_ENABLED => 1,
            Data::XML_PATH_HOVER_EFFECT => 'a"b',
        ])->getConfigDataAttributes();

        $this->assertStringContainsString('data-megamenu-enabled="true"', $attributes);
        $this->assertStringContainsString('data-megamenu-mobile-enabled="false"', $attributes);
        $this->assertStringContainsString('data-megamenu-mobile-breakpoint="1024"', $attributes);
        $this->assertStringContainsString('data-megamenu-hover-effect="a&quot;b"', $attributes);
        $this->assertStringContainsString('data-megamenu-sticky-hide-on-scroll-down="false"', $attributes);
        $this->assertSame(36, substr_count($attributes, 'data-megamenu-'));
    }

    public function testAlpineConfigIsJsonWithState(): void
    {
        $decoded = json_decode($this->viewModel()->getAlpineConfig(), true);

        $this->assertFalse($decoded['isOpen']);
        $this->assertNull($decoded['activeItem']);
        $this->assertFalse($decoded['mobileMenuOpen']);
        $this->assertSame(5, $decoded['config']['maxDepth']);
    }

    public function testKnockoutConfigRendersObservables(): void
    {
        $ko = $this->viewModel([Data::XML_PATH_ANIMATION_TYPE => "it's"])->getKnockoutConfig();

        $this->assertStringStartsWith('{enabled: ko.observable(false), ', $ko);
        $this->assertStringContainsString("animationType: ko.observable('it\\'s')", $ko);
        $this->assertStringContainsString('mobileBreakpoint: ko.observable(1024)', $ko);
        $this->assertStringEndsWith('showCategoryCount: ko.observable(false)}', $ko);
    }

    public function testDelegatingGetters(): void
    {
        $vm = $this->viewModel([
            Data::XML_PATH_CUSTOM_CSS => '.x{}',
            Data::XML_PATH_CUSTOM_JS => 'js()',
            Data::XML_PATH_DEBUG_MODE => 1,
            Data::XML_PATH_MOBILE_POSITION => 'right',
            Data::XML_PATH_COLUMNS => '6',
            Data::XML_PATH_IMAGE_SIZE => 'large',
        ]);

        $this->assertSame('.x{}', $vm->getCustomCss());
        $this->assertSame('js()', $vm->getCustomJs());
        $this->assertTrue($vm->isDebugEnabled());
        $this->assertSame('right', $vm->getMobilePosition());
        $this->assertSame(6, $vm->getColumns());
        $this->assertSame('large', $vm->getImageSize());
        $this->assertSame(json_decode($this->dataHelper()->getConfigJson(), true), json_decode($this->viewModel()->getConfigJson(), true));
    }
}
