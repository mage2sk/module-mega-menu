<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private function helper(?ScopeConfigInterface $scope = null, ?TypeListInterface $typeList = null, ?Pool $pool = null): Data
    {
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scope ?? $this->createStub(ScopeConfigInterface::class));

        return new Data(
            $context,
            $typeList ?? $this->createStub(TypeListInterface::class),
            $pool ?? $this->createStub(Pool::class)
        );
    }

    private function scopeWithValues(array $values): ScopeConfigInterface
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(fn (string $path) => $values[$path] ?? null);
        $scope->method('isSetFlag')->willReturnCallback(fn (string $path) => !empty($values[$path]));

        return $scope;
    }

    private function item(array $data, array $children = []): Item
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');
        $item = new Item($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
        $item->setChildren($children);

        return $item;
    }

    public static function defaultsProvider(): array
    {
        return [
            ['getCacheLifetime', 3600],
            ['getMobileBreakpoint', 1024],
            ['getAnimationType', 'fade'],
            ['getAnimationDuration', 200],
            ['getHoverIntentDelay', 150],
            ['getMaxDepth', 5],
            ['getColumns', 4],
            ['getHoverEffect', 'underline'],
            ['getImageSize', 'thumbnail'],
            ['getMobilePosition', 'left'],
            ['getMobileAnimationSpeed', 300],
            ['getStickyOffset', 100],
            ['getStickyAnimationSpeed', 300],
            ['getCustomCss', ''],
            ['getCustomJs', ''],
        ];
    }

    #[DataProvider('defaultsProvider')]
    public function testDefaultsWhenUnset(string $method, int|string $expected): void
    {
        $this->assertSame($expected, $this->helper($this->scopeWithValues([]))->$method());
    }

    public function testConfiguredValuesOverrideDefaults(): void
    {
        $helper = $this->helper($this->scopeWithValues([
            Data::XML_PATH_CACHE_LIFETIME => '60',
            Data::XML_PATH_MOBILE_BREAKPOINT => '768',
            Data::XML_PATH_ANIMATION_TYPE => 'slide',
            Data::XML_PATH_MAX_DEPTH => '2',
            Data::XML_PATH_HOVER_EFFECT => 'glow',
            Data::XML_PATH_CUSTOM_CSS => '.a{}',
        ]));

        $this->assertSame(60, $helper->getCacheLifetime());
        $this->assertSame(768, $helper->getMobileBreakpoint());
        $this->assertSame('slide', $helper->getAnimationType());
        $this->assertSame(2, $helper->getMaxDepth());
        $this->assertSame('glow', $helper->getHoverEffect());
        $this->assertSame('.a{}', $helper->getCustomCss());
    }

    public function testAnimationIsEnabledUnlessTypeIsNone(): void
    {
        $this->assertTrue($this->helper($this->scopeWithValues([]))->isAnimationEnabled());
        $this->assertTrue($this->helper($this->scopeWithValues([Data::XML_PATH_ANIMATION_TYPE => 'slide']))->isAnimationEnabled());
        $this->assertFalse($this->helper($this->scopeWithValues([Data::XML_PATH_ANIMATION_TYPE => 'none']))->isAnimationEnabled());
    }

    public function testMenuIdentifierIsTrimmedOrNull(): void
    {
        $this->assertSame('main', $this->helper($this->scopeWithValues([Data::XML_PATH_MENU_IDENTIFIER => '  main ']))->getMenuIdentifier());
        $this->assertNull($this->helper($this->scopeWithValues([]))->getMenuIdentifier());
    }

    public function testFlagAliasesDelegate(): void
    {
        $helper = $this->helper($this->scopeWithValues([
            Data::XML_PATH_STICKY_MENU => 1,
            Data::XML_PATH_MOBILE_ACCORDION => 1,
            Data::XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN => 1,
            Data::XML_PATH_ENABLE_CUSTOM_BLOCKS => 1,
        ]));

        $this->assertTrue($helper->isStickyEnabled());
        $this->assertTrue($helper->isAccordionEnabled());
        $this->assertTrue($helper->isStickyHideOnScrollDown());
        $this->assertTrue($helper->enableCustomBlocks());
        $this->assertFalse($helper->isStickyShowOnScrollUp());
        $this->assertFalse($helper->isStickyShowShadow());
    }

    public function testGetConfigFlagAndValueUseStoreScope(): void
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->expects($this->once())->method('isSetFlag')->with('x/y/z', ScopeInterface::SCOPE_STORE, 4)->willReturn(true);
        $scope->expects($this->once())->method('getValue')->with('x/y/z', ScopeInterface::SCOPE_STORE, 4)->willReturn('v');
        $helper = $this->helper($scope);

        $this->assertTrue($helper->getConfigFlag('x/y/z', 4));
        $this->assertSame('v', $helper->getConfigValue('x/y/z', 4));
    }

    public function testColorGettersReturnEmptyStrings(): void
    {
        $helper = $this->helper();

        $this->assertSame('', $helper->getMenuBackgroundColor());
        $this->assertSame('', $helper->getMenuTextColor());
        $this->assertSame('', $helper->getMenuHoverColor());
        $this->assertSame('', $helper->getDropdownBackgroundColor());
        $this->assertSame('', $helper->getDropdownBorderColor());
    }

    public function testConfigJsonContainsAllKeysWithDefaults(): void
    {
        $json = json_decode($this->helper($this->scopeWithValues([Data::XML_PATH_ENABLED => 1]))->getConfigJson(), true);

        $this->assertCount(30, $json);
        $this->assertTrue($json['enabled']);
        $this->assertSame(1024, $json['mobileBreakpoint']);
        $this->assertSame('fade', $json['animationType']);
        $this->assertSame(100, $json['stickyOffset']);
        $this->assertFalse($json['stickyShadow']);
        $this->assertFalse($json['enableCustomBlocks']);
    }

    public function testMenuCacheTags(): void
    {
        $this->assertSame(['panth_megamenu', 'panth_megamenu_9'], $this->helper()->getMenuCacheTags(9));
    }

    public function testFlushMenuCacheCleansFullPageAndBlockCaches(): void
    {
        $cleaned = [];
        $typeList = $this->createMock(TypeListInterface::class);
        $typeList->expects($this->exactly(2))->method('cleanType')
            ->willReturnCallback(function (string $type) use (&$cleaned): void {
                $cleaned[] = $type;
            });

        $this->helper(null, $typeList)->flushMenuCache();

        $this->assertSame(['full_page', 'block_html'], $cleaned);
    }

    public function testCleanMenuCacheCleansEveryFrontendByTag(): void
    {
        $frontend = $this->createMock(FrontendInterface::class);
        $frontend->expects($this->once())->method('clean')
            ->with('matchingTag', ['panth_megamenu', 'panth_megamenu_3']);
        $pool = $this->createStub(Pool::class);
        $pool->method('valid')->willReturnOnConsecutiveCalls(true, false);
        $pool->method('current')->willReturn($frontend);

        $this->helper(null, null, $pool)->cleanMenuCache(3);
    }

    public function testCleanMenuCacheWithoutIdUsesGenericTag(): void
    {
        $frontend = $this->createMock(FrontendInterface::class);
        $frontend->expects($this->once())->method('clean')->with('matchingTag', ['panth_megamenu']);
        $pool = $this->createStub(Pool::class);
        $pool->method('valid')->willReturnOnConsecutiveCalls(true, false);
        $pool->method('current')->willReturn($frontend);

        $this->helper(null, null, $pool)->cleanMenuCache();
    }

    public function testItemTypePredicates(): void
    {
        $helper = $this->helper();
        $category = $this->item(['item_type' => 'category', 'link_type' => 'category']);
        $link = $this->item(['item_type' => 'link', 'link_type' => 'cms_page']);
        $content = $this->item(['item_type' => 'content', 'link_type' => 'custom_url']);

        $this->assertTrue($helper->isCategoryItem($category));
        $this->assertFalse($helper->isCategoryItem($link));
        $this->assertTrue($helper->isLinkItem($link));
        $this->assertTrue($helper->isContentItem($content));
        $this->assertTrue($helper->hasCategoryLink($category));
        $this->assertTrue($helper->hasCmsPageLink($link));
        $this->assertTrue($helper->hasCustomUrlLink($content));
        $this->assertFalse($helper->hasCustomUrlLink($category));
    }

    public function testItemClassesForActiveLeaf(): void
    {
        $item = $this->item(['item_type' => 'link', 'level' => 0, 'is_active' => 1]);

        $this->assertSame('menu-item menu-item-link level-0', $this->helper()->getItemClasses($item));
    }

    public function testItemClassesForArrayItems(): void
    {
        $this->assertSame(
            'menu-item menu-item-category level-2 has-children disabled promo',
            $this->helper()->getItemClasses([
                'item_type' => 'category', 'level' => '2', 'is_active' => 0, 'css_class' => 'promo',
                'children' => [['title' => 'c']],
            ])
        );
        $this->assertSame('menu-item menu-item- level-0', $this->helper()->getItemClasses(['title' => 'x']));
    }

    public function testItemClassesForDisabledParentWithCustomClass(): void
    {
        $child = $this->item(['title' => 'c']);
        $item = $this->item(['item_type' => 'category', 'level' => 2, 'is_active' => 0, 'css_class' => 'promo'], [$child]);

        $this->assertSame(
            'menu-item menu-item-category level-2 has-children disabled promo',
            $this->helper()->getItemClasses($item)
        );
    }
}
