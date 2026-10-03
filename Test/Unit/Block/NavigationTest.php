<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Block\Navigation;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\ConfigHelperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\MenuViewModelTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NavigationTest extends TestCase
{
    use BlockContextTrait;
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuModelTrait;
    use MenuViewModelTrait;

    private ?string $userAgent = null;
    private string $currentUrl = 'https://shop.test/';

    private function block(array $menus = [], array $config = [Data::XML_PATH_ENABLED => 1], array $data = []): Navigation
    {
        $menuRepository = $this->createStub(MenuRepositoryInterface::class);
        $menuRepository->method('getByIdentifier')->willReturnCallback(function (string $identifier) use ($menus) {
            if (!isset($menus[$identifier])) {
                throw new NoSuchEntityException(__('missing'));
            }
            return $this->newMenu($menus[$identifier]);
        });
        $request = $this->createStub(Http::class);
        $request->method('getHeader')->willReturnCallback(fn () => $this->userAgent ?? false);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getCurrentUrl')->willReturnCallback(fn () => $this->currentUrl);
        $viewModel = $this->menuViewModel($config);

        return new Navigation(
            $this->blockContext(null, $url),
            $menuRepository,
            $this->createStub(ItemRepositoryInterface::class),
            $this->blockContext()->getStoreManager(),
            $this->dataHelper($config),
            $viewModel,
            $this->createStub(LoggerInterface::class),
            $this->createStub(ThemeHelper::class),
            $viewModel->getMenuRenderer(),
            $request,
            $data
        );
    }

    public static function userAgentProvider(): array
    {
        return [
            'none' => [null, false],
            'desktop chrome' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120', false],
            'iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)', true],
            'android' => ['Mozilla/5.0 (Linux; android 14)', true],
            'ipad' => ['Mozilla/5.0 (iPad; CPU OS 16)', true],
            'windows phone' => ['Windows Phone 10.0', true],
        ];
    }

    #[DataProvider('userAgentProvider')]
    public function testIsMobile(?string $agent, bool $expected): void
    {
        $this->userAgent = $agent;

        $this->assertSame($expected, $this->block()->isMobile());
    }

    public function testShouldShowMobileMenuRequiresConfigAndDevice(): void
    {
        $this->userAgent = 'iPhone';
        $this->assertFalse($this->block()->shouldShowMobileMenu());
        $this->assertTrue($this->block([], [Data::XML_PATH_MOBILE_ENABLED => 1])->shouldShowMobileMenu());

        $this->userAgent = 'Desktop';
        $this->assertFalse($this->block([], [Data::XML_PATH_MOBILE_ENABLED => 1])->shouldShowMobileMenu());
    }

    public function testCacheKeyAddsDeviceFlavour(): void
    {
        $this->userAgent = 'Android';
        $key = $this->block([], [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main'])->getCacheKeyInfo();

        $this->assertSame(['navigation', 'mobile'], array_slice($key, -2));
        $this->assertSame('Panth_MegaMenu::navigation.phtml', $key[5]);
        $this->assertSame(1024, $this->block()->getMobileBreakpoint());
    }

    public function testNavigationHtmlGuards(): void
    {
        $menus = ['main' => ['menu_id' => 1, 'identifier' => 'main', 'is_active' => 1, 'items_json' => '[]']];

        $this->assertSame('', $this->block($menus)->getNavigationHtml('main'));
        $this->assertSame('', $this->block($menus, [], ['menu_identifier' => 'main'])->getNavigationHtml('main'));
        $this->assertSame('', $this->block([], [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main'])->getNavigationHtml('main'));
    }

    private function arrayTreeMenus(): array
    {
        return ['main' => ['menu_id' => 1, 'identifier' => 'main', 'is_active' => 1, 'items_json' => json_encode([
            ['item_id' => 1, 'title' => 'Shop', 'url' => 'https://shop.test/shop', 'item_type' => 'link'],
            ['item_id' => 2, 'parent_id' => 1, 'level' => 1, 'title' => 'Kids', 'url' => 'https://shop.test/kids'],
            ['item_id' => 3, 'title' => 'Hidden', 'url' => 'https://shop.test/x', 'is_active' => 0],
        ])]];
    }

    public function testNavigationHtmlRendersArrayTreeItems(): void
    {
        $this->currentUrl = 'https://shop.test/kids';
        $block = $this->block($this->arrayTreeMenus(), [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main']);

        $html = $block->getNavigationHtml('main');

        $this->assertStringContainsString('<ul class="menu-root level-0" role="menubar">', $html);
        $this->assertStringContainsString('role="menuitem" aria-haspopup="true" aria-expanded="false">Shop<span class="submenu-indicator"', $html);
        $this->assertStringContainsString('<ul class="submenu level-1" role="menu" aria-label="Shop">', $html);
        $this->assertStringContainsString('menu-item&#x20;menu-item-link&#x20;level-0&#x20;has-children" role="none">', $html);
        $this->assertStringContainsString('aria-current="page">Kids</a>', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function testBreadcrumbDataWorksForArrayTree(): void
    {
        $this->currentUrl = 'https://shop.test/kids';
        $block = $this->block($this->arrayTreeMenus(), [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main']);

        $this->assertSame(['Shop', 'Kids'], array_column($block->getBreadcrumbData('main'), 'title'));
        $html = $block->renderBreadcrumb('main');
        $this->assertStringContainsString('<a href="https://shop.test/shop">Shop</a>', $html);
        $this->assertStringContainsString('<span>Kids</span>', $html);
    }

    public function testBreadcrumbEmptyWithoutTree(): void
    {
        $block = $this->block();

        $this->assertSame([], $block->getBreadcrumbData('main'));
        $this->assertSame('', $block->renderBreadcrumb('main'));
    }

    public function testRenderNavigationItemMarksCurrentPage(): void
    {
        $this->currentUrl = 'https://shop.test/men';
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'title' => 'Shirts', 'url' => 'https://shop.test/men', 'item_type' => 'link']);
        $root = $this->item(['item_id' => 1, 'is_active' => 1, 'level' => 0, 'title' => 'Men', 'url' => 'https://shop.test/m', 'item_type' => 'link'], [$child]);
        $method = new \ReflectionMethod(Navigation::class, 'renderNavigationItem');

        $html = $method->invoke($this->block(), $root, 0);

        $this->assertStringContainsString('role="menuitem" aria-haspopup="true" aria-expanded="false">Men<span class="submenu-indicator"', $html);
        $this->assertStringContainsString('<ul class="submenu level-1" role="menu" aria-label="Men">', $html);
        $this->assertStringContainsString('level-1&#x20;active&#x20;current" role="none">', $html);
        $this->assertStringContainsString('aria-current="page">Shirts</a>', $html);
        $this->assertSame('', $method->invoke($this->block(), $this->item(['is_active' => 0]), 0));
    }

    public function testFindActiveItemSearchesChildren(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'url' => '/b']);
        $root = $this->item(['item_id' => 1, 'is_active' => 1, 'url' => '/a'], [$child]);
        $method = new \ReflectionMethod(Navigation::class, 'findActiveItem');

        $this->assertSame($child, $method->invoke($this->block(), [$root], '/b'));
        $this->assertNull($method->invoke($this->block(), [$root], '/c'));
    }

    public function testMobileToggleMarkup(): void
    {
        $html = (new \ReflectionMethod(Navigation::class, 'renderMobileToggle'))->invoke($this->block());

        $this->assertStringContainsString('class="menu-toggle"', $html);
        $this->assertStringContainsString('<span class="menu-toggle-text">Menu</span>', $html);
    }

    public function testNavigationClassesReflectAnimationSetting(): void
    {
        $this->assertSame('megamenu-navigation animated animation-duration-200', $this->block()->getNavigationClasses());
        $this->assertSame(
            'megamenu-navigation',
            $this->block([], [Data::XML_PATH_ENABLED => 1, Data::XML_PATH_ANIMATION_TYPE => 'none'])->getNavigationClasses()
        );
    }

    public function testNavigationDataAttributesReflectAnimationSetting(): void
    {
        $this->assertSame('true', $this->block()->getNavigationDataAttributes()['data-animation-enabled']);
        $attributes = $this->block([], [Data::XML_PATH_ENABLED => 1, Data::XML_PATH_ANIMATION_TYPE => 'none'])
            ->getNavigationDataAttributes();
        $this->assertSame('false', $attributes['data-animation-enabled']);
        $this->assertSame(200, $attributes['data-animation-duration']);
    }
}
