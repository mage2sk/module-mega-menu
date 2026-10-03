<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Block\Menu;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\ConfigHelperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\MenuViewModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MenuTest extends TestCase
{
    use BlockContextTrait;
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuModelTrait;
    use MenuViewModelTrait;

    private MenuRepositoryInterface&MockObject $menuRepository;

    private function block(array $menus = [], array $config = [Data::XML_PATH_ENABLED => 1], array $data = []): Menu
    {
        $this->menuRepository = $this->createMock(MenuRepositoryInterface::class);
        $this->menuRepository->method('getByIdentifier')->willReturnCallback(function (string $identifier) use ($menus) {
            if (!isset($menus[$identifier])) {
                throw new NoSuchEntityException(__('missing'));
            }
            return $this->newMenu($menus[$identifier]);
        });
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $viewModel = $this->menuViewModel($config);

        return new Menu(
            $this->blockContext(),
            $this->menuRepository,
            $this->createStub(ItemRepositoryInterface::class),
            $storeManager,
            $this->dataHelper($config),
            $viewModel,
            $this->createStub(LoggerInterface::class),
            $this->createStub(ThemeHelper::class),
            $viewModel->getMenuRenderer(),
            $data
        );
    }

    private function mainMenu(array $items, array $extra = []): array
    {
        return ['main' => $extra + ['menu_id' => 4, 'identifier' => 'main', 'is_active' => 1, 'items_json' => json_encode($items)]];
    }

    public function testGetMenuReturnsActiveMenuAndNullForMissing(): void
    {
        $block = $this->block($this->mainMenu([]));

        $this->assertSame(4, $block->getMenu('main')->getMenuId());
        $this->assertNull($block->getMenu('other'));
    }

    public function testInactiveMenuIsNeverReturnedOrCached(): void
    {
        $block = $this->block([
            'off' => ['menu_id' => 5, 'identifier' => 'off', 'is_active' => 0],
            'on' => ['menu_id' => 6, 'identifier' => 'on', 'is_active' => 1],
        ]);

        $this->assertNull($block->getMenu('off'));
        $this->assertNull($block->getMenu('off'));
        $this->assertNull($block->getCurrentMenu());
        $this->assertSame(6, $block->getMenu('on')->getMenuId());
        $this->assertNull($block->getMenu('off'));
        $this->assertSame(6, $block->getCurrentMenu()->getMenuId());
    }

    public function testMenuTreeBuildsHierarchyFiltersAndSanitizes(): void
    {
        $block = $this->block($this->mainMenu([
            ['item_id' => 1, 'title' => 'Root', 'parent_id' => null, 'url' => 'javascript:x'],
            ['item_id' => 0, 'temp_id' => 'tmp_2', 'title' => 'Second root', 'parent_id' => '0'],
            ['item_id' => 3, 'title' => 'Child', 'parent_id' => 1, 'url' => '/child'],
            ['item_id' => 4, 'title' => 'Temp child', 'parent_id' => 'tmp_2'],
            ['item_id' => 5, 'title' => 'Hidden', 'parent_id' => null, 'show_on_frontend' => 0],
            'garbage',
            ['item_id' => 6, 'title' => 'Orphan', 'parent_id' => 99],
        ]));

        $tree = $block->getMenuTree('main');

        $this->assertSame(['Root', 'Second root'], array_column($tree, 'title'));
        $this->assertSame('#', $tree[0]['url']);
        $this->assertSame('Child', $tree[0]['children'][0]['title']);
        $this->assertSame('/child', $tree[0]['children'][0]['url']);
        $this->assertSame('Temp child', $tree[1]['children'][0]['title']);
        $this->assertSame($tree, $block->getMenuTree('ignored'));
    }

    public function testMenuTreeEmptyCases(): void
    {
        $this->assertSame([], $this->block()->getMenuTree('main'));
        $this->assertSame([], $this->block(['main' => ['menu_id' => 4, 'identifier' => 'main', 'is_active' => 1]])->getMenuTree('main'));
        $this->assertSame([], $this->block(['main' => ['menu_id' => 4, 'identifier' => 'main', 'is_active' => 1, 'items_json' => '{bad']])->getMenuTree('main'));
    }

    public function testBuildTreeStopsAtDepthLimitOnCycles(): void
    {
        $method = new \ReflectionMethod(Menu::class, 'buildTree');
        $tree = $method->invoke($this->block(), [
            ['item_id' => 1, 'parent_id' => 2],
            ['item_id' => 2, 'parent_id' => 1],
        ], 1);

        $depth = 0;
        $node = $tree;
        while (!empty($node)) {
            $depth++;
            $node = $node[0]['children'] ?? [];
        }
        $this->assertSame(65, $depth);
    }

    public function testRenderItemForModels(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'title' => 'Child', 'url' => '/c', 'item_type' => 'link']);
        $root = $this->item(['item_id' => 1, 'is_active' => 1, 'level' => 0, 'title' => 'A&B', 'url' => '/r', 'item_type' => 'link', 'open_new_tab' => 1], [$child]);
        $block = $this->block();

        $html = $block->renderItem($root);

        $this->assertStringStartsWith('<li class="menu-item&#x20;menu-item-link&#x20;level-0&#x20;has-children"><a href="/r" title="A&amp;B" target="_blank" rel="noopener&#x20;noreferrer" aria-haspopup="true" aria-expanded="false">A&amp;B</a>', $html);
        $this->assertStringContainsString('<ul class="submenu level-1"><li class="menu-item&#x20;menu-item-link&#x20;level-1"><a href="/c"', $html);
        $this->assertSame('', $block->renderItem($this->item(['is_active' => 0])));
    }

    public function testRenderContentItem(): void
    {
        $html = $this->block()->renderItem($this->item(['is_active' => 1, 'item_type' => 'content', 'content' => 'Deal', 'columns' => 2]));

        $this->assertStringContainsString('<div class="menu-content col-span-6">[f]Deal</div>', $html);
    }

    public function testGetMenuHtmlGuards(): void
    {
        $this->assertSame('', $this->block($this->mainMenu([['item_id' => 1]]), [])->getMenuHtml('main'));
        $this->assertSame('', $this->block()->getMenuHtml('main'));
        $this->assertSame('', $this->block($this->mainMenu([]))->getMenuHtml('main'));
    }

    public function testGetMenuHtmlRendersArrayTreeItems(): void
    {
        $block = $this->block($this->mainMenu([['item_id' => 1, 'title' => 'Alpha Item', 'is_active' => 1]]));

        $html = $block->getMenuHtml('main', 'extra');

        $this->assertStringStartsWith('<nav class="megamenu&#x20;menu-main&#x20;extra" role="navigation"><div id="panthMenuContent"', $html);
        $this->assertStringContainsString('Alpha Item', $html);
        $this->assertStringEndsWith('</nav>', $html);
    }

    public function testGetMenuDataUsesConfiguredMenu(): void
    {
        $this->assertSame([], $this->block()->getMenuData());

        $data = $this->block($this->mainMenu([]), [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main'])->getMenuData();

        $this->assertSame(4, $data['menu_id']);
        $this->assertSame('main', $data['identifier']);
    }

    public function testCacheKeyAndIdentities(): void
    {
        $block = $this->block($this->mainMenu([['item_id' => 1]]), [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main']);

        $key = $block->getCacheKeyInfo();
        $this->assertSame('MEGAMENU_BLOCK', $key[0]);
        $this->assertSame(1, $key[1]);
        $this->assertSame('main', $key[2]);
        $this->assertSame(3, $key[3]);
        $this->assertSame(0, $key[4]);
        $this->assertSame('Panth_MegaMenu::menu.phtml', $key[5]);
        $this->assertSame(['panth_megamenu'], $block->getIdentities());

        $block->getMenu('main');
        $this->assertSame(['panth_megamenu', 'panth_megamenu_4'], $block->getIdentities());
    }

    public function testCacheLifetimeDependsOnConfig(): void
    {
        $method = new \ReflectionMethod(Menu::class, 'getCacheLifetime');

        $this->assertNull($method->invoke($this->block()));
        $this->assertSame(600, $method->invoke($this->block([], [Data::XML_PATH_CACHE_ENABLED => 1, Data::XML_PATH_CACHE_LIFETIME => '600'])));
    }

    public function testShouldRenderAndMenuItems(): void
    {
        $menus = $this->mainMenu([['item_id' => 1, 'title' => 'A']]);

        $this->assertFalse($this->block($menus, [])->shouldRender());
        $this->assertFalse($this->block($menus)->shouldRender());
        $this->assertTrue($this->block($menus, [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main'])->shouldRender());
        $this->assertSame([], $this->block($menus)->getMenuItems());

        $fromConfig = $this->block($menus, [Data::XML_PATH_ENABLED => 1, Data::XML_PATH_MENU_IDENTIFIER => 'main']);
        $this->assertSame('A', $fromConfig->getMenuItems()[0]['title']);
    }

    public function testBeforeToHtmlLoadsConfiguredMenu(): void
    {
        $block = $this->block(
            $this->mainMenu([['item_id' => 1, 'title' => 'A']], ['css_class' => 'top', 'custom_css' => '.x{}', 'mobile_layout' => 'drill']),
            [Data::XML_PATH_ENABLED => 1, Data::XML_PATH_MENU_IDENTIFIER => 'main']
        );
        $this->assertSame([], $block->getCurrentMenuTree());

        (new \ReflectionMethod(Menu::class, '_beforeToHtml'))->invoke($block);

        $this->assertSame(4, $block->getCurrentMenu()->getMenuId());
        $this->assertCount(1, $block->getCurrentMenuTree());
        $this->assertSame('top', $block->getMenuCssClass());
        $this->assertSame('.x{}', $block->getMenuCustomCss());
        $this->assertSame('drill', $block->getMobileLayout());
        $this->assertInstanceOf(ThemeHelper::class, $block->getData('theme_helper'));
    }

    public function testMenuStylingFallbacksWithoutMenu(): void
    {
        $block = $this->block();

        $this->assertSame('', $block->getMenuCssClass());
        $this->assertSame('', $block->getMenuCustomCss());
        $this->assertSame('accordion', $block->getMobileLayout());

        $lookup = $this->block(['main' => ['menu_id' => 4, 'identifier' => 'main', 'is_active' => 1, 'css_class' => 'c']], [Data::XML_PATH_ENABLED => 1], ['menu_identifier' => 'main']);
        $this->assertSame('c', $lookup->getMenuCssClass());
        $this->assertSame('accordion', $lookup->getMobileLayout());
    }

    public function testToHtmlEmptyWhenDisabled(): void
    {
        $this->assertSame('', $this->block([], [])->toHtml());
    }

    public function testSimpleAccessorsAndConfig(): void
    {
        $block = $this->block([], [Data::XML_PATH_ENABLED => 1, Data::XML_PATH_STICKY_MENU => 1, Data::XML_PATH_HOVER_INTENT_DELAY => '90']);

        $this->assertTrue($block->isEnabled());
        $this->assertSame(1, $block->getStoreId());
        $this->assertSame(0, $block->getCustomerId());
        $this->assertTrue($block->isStickyEnabled());
        $this->assertSame(90, $block->getHoverDelay());
        $this->assertSame(200, $block->getAnimationSpeed());
        $this->assertSame(1024, $block->getMobileBreakpoint());
        $this->assertTrue($block->isCloseOnClick());
        $this->assertFalse($block->isRtl());
        $this->assertSame($block->getMenuViewModel(), $block->getViewModel());
        $this->assertSame($block->getMenuHelper(), $block->getMenuHelper());

        $config = $block->getMenuConfig();
        $this->assertSame(90, $config['hoverDelay']);
        $this->assertTrue($config['stickyEnabled']);
        $this->assertSame('left', $config['mobilePosition']);
        $this->assertSame(5, $config['maxDepth']);
        $this->assertSame($config, json_decode($block->getMenuConfigJson(), true));
    }
}
