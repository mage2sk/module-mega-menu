<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Block\Block as CmsBlockBlock;
use Magento\Cms\Block\BlockFactory;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuTest extends TestCase
{
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuModelTrait;
    use MenuViewModelTrait;

    public function testGetMenuData(): void
    {
        $menu = $this->newMenu(['menu_id' => 3, 'identifier' => 'main', 'title' => 'Main', 'is_active' => 1, 'store_ids' => '0']);

        $this->assertSame([
            'menu_id' => 3, 'identifier' => 'main', 'title' => 'Main', 'is_active' => true,
            'store_ids' => ['0'], 'created_at' => null, 'updated_at' => null,
        ], $this->menuViewModel()->getMenuData($menu));
    }

    public function testGetMenuUsesConfiguredDefaultAndCaches(): void
    {
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->once())->method('getByIdentifier')->with('top', 1)
            ->willReturn($this->newMenu(['menu_id' => 1, 'identifier' => 'top']));
        $vm = $this->menuViewModel(['panth_megamenu/general/default_menu_identifier' => 'top'], ['menuRepository' => $repository]);

        $first = $vm->getMenu();
        $this->assertSame('top', $first->getIdentifier());
        $this->assertSame($first, $vm->getMenu());
        $this->assertSame($first, $vm->getMenu('top'));
    }

    public function testGetMenuFallsBackToPmenuAndHandlesMissing(): void
    {
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->once())->method('getByIdentifier')->with('pmenu', 1)
            ->willThrowException(new NoSuchEntityException(__('missing')));

        $this->assertNull($this->menuViewModel([], ['menuRepository' => $repository])->getMenu());
    }

    public function testGetMenuItemsFiltersAndCaches(): void
    {
        $menuRepository = $this->createStub(MenuRepositoryInterface::class);
        $menuRepository->method('getByIdentifier')->willReturn($this->newMenu(['menu_id' => 4, 'identifier' => 'm', 'is_active' => 1]));
        $itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $visible = $this->item(['item_id' => 1, 'is_active' => 1, 'level' => 0]);
        $itemRepository->expects($this->once())->method('getMenuTree')->with(4)->willReturn([
            $visible,
            $this->item(['item_id' => 2, 'is_active' => 0]),
            $this->item(['item_id' => 3, 'is_active' => 1, 'level' => 9]),
        ]);
        $vm = $this->menuViewModel([], ['menuRepository' => $menuRepository, 'itemRepository' => $itemRepository]);

        $this->assertSame([$visible], $vm->getMenuItems('m'));
        $this->assertSame([$visible], $vm->getMenuItems('m'));
    }

    public function testGetMenuItemsEmptyForInactiveOrMissingMenu(): void
    {
        $menuRepository = $this->createStub(MenuRepositoryInterface::class);
        $menuRepository->method('getByIdentifier')->willReturn($this->newMenu(['menu_id' => 4, 'identifier' => 'm', 'is_active' => 0]));

        $this->assertSame([], $this->menuViewModel([], ['menuRepository' => $menuRepository])->getMenuItems('m'));

        $missing = $this->createStub(MenuRepositoryInterface::class);
        $missing->method('getByIdentifier')->willThrowException(new NoSuchEntityException(__('no')));
        $this->assertSame('{}', $this->menuViewModel([], ['menuRepository' => $missing])->getMenuJson('none'));
    }

    public function testGetMenuJsonSerialisesTree(): void
    {
        $menuRepository = $this->createStub(MenuRepositoryInterface::class);
        $menuRepository->method('getByIdentifier')->willReturn($this->newMenu(['menu_id' => 4, 'identifier' => 'm', 'title' => 'M', 'is_active' => 1]));
        $child = $this->item(['item_id' => 2, 'parent_id' => 1, 'title' => 'Child', 'is_active' => 1, 'level' => 1, 'url' => '/c']);
        $root = $this->item(['item_id' => 1, 'title' => 'Root', 'is_active' => 1, 'level' => 0, 'url' => '/r', 'columns' => 2], [$child]);
        $itemRepository = $this->createStub(ItemRepositoryInterface::class);
        $itemRepository->method('getMenuTree')->willReturn([$root]);

        $json = json_decode($this->menuViewModel([], ['menuRepository' => $menuRepository, 'itemRepository' => $itemRepository])->getMenuJson('m'), true);

        $this->assertSame('M', $json['menu']['title']);
        $this->assertSame('/r', $json['items'][0]['url']);
        $this->assertTrue($json['items'][0]['has_children']);
        $this->assertSame(2, $json['items'][0]['columns']);
        $this->assertSame('Child', $json['items'][0]['children'][0]['title']);
        $this->assertSame(1, $json['items'][0]['children'][0]['parent_id']);
    }

    public function testItemUrlBasics(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('#', $vm->getItemUrl(['is_active' => 0, 'url' => '/x']));
        $this->assertSame('/shop', $vm->getItemUrl(['url' => '/shop']));
        $this->assertSame('#', $vm->getItemUrl(['url' => 'javascript:alert(1)']));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'link']));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'cms_block', 'url' => '#']));
        $this->assertSame('/obj', $vm->getItemUrl($this->item(['url' => '/obj', 'is_active' => 1])));
    }

    public function testCategoryUrlResolvedAndCached(): void
    {
        $category = $this->createStub(Category::class);
        $category->method('getIsActive')->willReturn(true);
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects($this->once())->method('get')->with(12, 1)->willReturn($category);
        $helper = $this->createStub(CategoryHelper::class);
        $helper->method('getCategoryUrl')->willReturn('https://shop.test/men.html');
        $vm = $this->menuViewModel([], ['categoryRepository' => $repository, 'categoryHelper' => $helper]);

        $this->assertSame('https://shop.test/men.html', $vm->getItemUrl(['item_type' => 'category', 'category_id' => '12']));
        $this->assertSame('https://shop.test/men.html', $vm->getItemUrl(['item_type' => 'category', 'category_id' => 12]));
    }

    public function testInactiveOrMissingCategory(): void
    {
        $inactive = $this->createStub(Category::class);
        $inactive->method('getIsActive')->willReturn(false);
        $repository = $this->createStub(CategoryRepositoryInterface::class);
        $repository->method('get')->willReturnCallback(function (int $id) use ($inactive) {
            if ($id === 1) {
                return $inactive;
            }
            throw new NoSuchEntityException(__('no'));
        });
        $vm = $this->menuViewModel([], ['categoryRepository' => $repository]);

        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'category', 'category_id' => 1]));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'category', 'category_id' => 2]));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'category']));
    }

    public function testCmsPageAndProductUrls(): void
    {
        $pageHelper = $this->createMock(PageHelper::class);
        $pageHelper->expects($this->exactly(2))->method('getPageUrl')
            ->willReturnCallback(fn ($id) => $id === 'about' ? 'https://shop.test/about' : null);
        $visible = $this->createStub(Product::class);
        $visible->method('isVisibleInSiteVisibility')->willReturn(true);
        $visible->method('getProductUrl')->willReturn('https://shop.test/bag.html');
        $hidden = $this->createStub(Product::class);
        $hidden->method('isVisibleInSiteVisibility')->willReturn(false);
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('getById')->willReturnCallback(function (int $id) use ($visible, $hidden) {
            return match ($id) {
                1 => $visible,
                2 => $hidden,
                default => throw new NoSuchEntityException(__('no')),
            };
        });
        $vm = $this->menuViewModel([], ['pageHelper' => $pageHelper, 'productRepository' => $products]);

        $this->assertSame('https://shop.test/about', $vm->getItemUrl(['item_type' => 'cms_page', 'cms_page_id' => 'about']));
        $this->assertSame('https://shop.test/about', $vm->getItemUrl(['item_type' => 'cms_page', 'cms_page_id' => 'about']));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'cms_page', 'cms_page_id' => 'gone']));
        $this->assertSame('https://shop.test/bag.html', $vm->getItemUrl(['item_type' => 'product', 'product_id' => 1]));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'product', 'product_id' => 2]));
        $this->assertSame('#', $vm->getItemUrl(['item_type' => 'product', 'product_id' => 3]));
    }

    public function testChildrenForArraysAndModels(): void
    {
        $vm = $this->menuViewModel();
        $this->assertFalse($vm->hasChildren(['children' => []]));
        $this->assertTrue($vm->hasChildren(['children' => [['title' => 'a']]]));
        $this->assertSame([], $vm->getChildren(['children' => 'bad']));
        $this->assertSame([['title' => 'a']], $vm->getChildren(['children' => [['title' => 'a']]]));

        $visible = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1]);
        $parent = $this->item(['item_id' => 1, 'is_active' => 1], [$visible, $this->item(['is_active' => 0])]);
        $this->assertTrue($vm->hasChildren($parent));
        $this->assertSame([$visible], $vm->getChildren($parent));

        $onlyHidden = $this->item(['item_id' => 1], [$this->item(['is_active' => 0])]);
        $this->assertFalse($vm->hasChildren($onlyHidden));
        $this->assertSame([], $vm->getChildren($this->item([])));
    }

    public function testContentRendering(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('', $vm->renderItemContent(['content' => '']));
        $this->assertSame('[f]<p>x</p>', $vm->renderItemContent(['content' => '<p>x</p>']));
        $this->assertSame('[f]custom', $vm->processItemContent(['custom_content' => 'custom', 'content' => 'other']));
        $this->assertSame('[f]other', $vm->processItemContent(['content' => 'other']));
        $this->assertSame('', $vm->processItemContent([]));
    }

    public function testProcessItemContentPrefersCmsBlock(): void
    {
        $block = $this->createStub(CmsBlockBlock::class);
        $block->method('toHtml')->willReturnOnConsecutiveCalls('<div>block</div>', '');
        $factory = $this->createStub(BlockFactory::class);
        $factory->method('create')->willReturn($block);
        $vm = $this->menuViewModel([], ['cmsBlockFactory' => $factory]);

        $this->assertSame('<div>block</div>', $vm->processItemContent(['cms_block' => 'promo', 'content' => 'fallback']));
        $this->assertSame('[f]fallback', $vm->processItemContent(['cms_block' => 'promo', 'content' => 'fallback']));
    }

    public function testImageUrl(): void
    {
        $category = $this->createStub(Category::class);
        $category->method('getImageUrl')->willReturn('https://shop.test/media/cat.jpg');
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects($this->once())->method('get')->willReturn($category);
        $vm = $this->menuViewModel([], ['categoryRepository' => $repository]);

        $this->assertSame('/img/a.png', $vm->getImageUrl($this->item(['content' => '<p><img class="x" src="/img/a.png"></p>'])));
        $linked = $this->item(['link_type' => 'category', 'link_value' => '7']);
        $this->assertSame('https://shop.test/media/cat.jpg', $vm->getImageUrl($linked));
        $this->assertSame('https://shop.test/media/cat.jpg', $vm->getImageUrl($linked));
        $this->assertSame('', $vm->getImageUrl($this->item(['content' => 'no image'])));
    }

    public static function visibilityProvider(): array
    {
        return [
            'default visible' => [[], true],
            'inactive' => [['is_active' => 0], false],
            'too deep' => [['level' => 5], false],
            'just within depth' => [['level' => 4], true],
            'store match' => [['store_ids' => '2,1'], true],
            'all stores' => [['store_ids' => [0]], true],
            'other store' => [['store_ids' => '3'], false],
            'group match' => [['customer_group_ids' => '1,2'], true],
            'group mismatch' => [['customer_group_ids' => [3]], false],
            'hidden on all devices' => [['show_on_desktop' => '0', 'show_on_tablet' => false, 'show_on_mobile' => 0], false],
            'mobile only' => [['show_on_desktop' => '0', 'show_on_tablet' => '0', 'show_on_mobile' => '1'], true],
            'starts later' => [['start_date' => '2026-06-01'], false],
            'started' => [['start_date' => '2026-01-01'], true],
            'ended' => [['end_date' => '2026-03-01'], false],
            'ends today' => [['end_date' => '2026-03-15'], true],
            'end date with time' => [['end_date' => '2026-12-31 10:00:00'], true],
            'ends later today' => [['end_date' => '2026-03-15T13:30'], true],
            'ended earlier today' => [['end_date' => '2026-03-15 11:59'], false],
            'ends exactly now' => [['end_date' => '2026-03-15 12:00:00'], true],
            'starts later today' => [['start_date' => '2026-03-15T12:30'], false],
            'started earlier today' => [['start_date' => '2026-03-15 11:00:00'], true],
            'unparseable end date' => [['end_date' => 'not a date'], true],
        ];
    }

    public function testScheduleUsesStoreTimezone(): void
    {
        $this->now = strtotime('2026-03-15 12:00:00 UTC');
        $tz = ['general/locale/timezone' => 'America/New_York'];

        $this->assertTrue($this->menuViewModel($tz)->isItemVisible(['end_date' => '2026-03-15 09:00']));
        $this->assertFalse($this->menuViewModel($tz)->isItemVisible(['end_date' => '2026-03-15 07:59']));
        $this->assertFalse($this->menuViewModel($tz)->isItemVisible(['start_date' => '2026-03-15T08:30']));
        $this->assertTrue($this->menuViewModel($tz)->isItemVisible(['start_date' => '2026-03-15T07:30']));
        $this->assertFalse($this->menuViewModel()->isItemVisible(['end_date' => '2026-03-15 09:00']));
    }

    #[DataProvider('visibilityProvider')]
    public function testItemVisibility(array $item, bool $expected): void
    {
        $this->now = strtotime('2026-03-15 12:00:00 UTC');
        $this->groupId = 1;

        $this->assertSame($expected, $this->menuViewModel()->isItemVisible($item));
    }

    public function testMaxDepthConfigured(): void
    {
        $vm = $this->menuViewModel([Data::XML_PATH_MAX_DEPTH => '2']);

        $this->assertTrue($vm->isItemVisible(['level' => 1]));
        $this->assertFalse($vm->isItemVisible(['level' => 2]));
    }

    public function testIsActiveComparesNormalisedUrls(): void
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getCurrentUrl')->willReturn('https://shop.test/men/');
        $vm = $this->menuViewModel([], ['urlBuilder' => $url]);

        $this->assertTrue($vm->isActive($this->item(['is_active' => 1, 'url' => 'https://shop.test/men'])));
        $this->assertFalse($vm->isActive($this->item(['is_active' => 1, 'url' => 'https://shop.test/women'])));
        $this->assertFalse($vm->isActive($this->item(['is_active' => 0, 'url' => 'https://shop.test/men'])));
        $this->assertFalse($vm->isActive($this->item(['is_active' => 1]), 'https://shop.test/'));
        $this->assertTrue($vm->isActive($this->item(['is_active' => 1, 'url' => '/a/']), '/a'));
    }

    public function testItemClassAppendsAdditional(): void
    {
        $item = $this->item(['item_type' => 'link', 'level' => 1, 'is_active' => 1]);

        $this->assertSame('menu-item menu-item-link level-1 extra', $this->menuViewModel()->getItemClass($item, 'extra'));
    }

    public function testLinkAttributesAndLayoutHelpers(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('_blank', $vm->getLinkTarget(['open_new_tab' => 1]));
        $this->assertSame('_self', $vm->getLinkTarget([]));
        $this->assertSame('noopener noreferrer', $vm->getLinkRel(['open_new_tab' => true]));
        $this->assertSame('', $vm->getLinkRel($this->item([])));
        $this->assertSame('col-span-4', $vm->getColumnWidthClass(['columns' => 3]));
        $this->assertSame('col-span-12', $vm->getColumnWidthClass(['columns' => 0]));
        $this->assertSame('col-span-12', $vm->getColumnWidthClass(['columns' => 5]));
        $this->assertSame('col-span-1', $vm->getColumnWidthClass($this->item(['columns' => 12])));
        $this->assertTrue($vm->shouldShowContent(['item_type' => 'content', 'content' => 'x']));
        $this->assertFalse($vm->shouldShowContent(['item_type' => 'content']));
        $this->assertFalse($vm->shouldShowContent(['item_type' => 'link', 'content' => 'x']));
        $this->assertSame(2, $vm->getItemDepth(['level' => 2]));
        $this->assertTrue($vm->isTopLevel(['level' => 0, 'parent_id' => 5]));
        $this->assertTrue($vm->isTopLevel(['level' => 3]));
        $this->assertFalse($vm->isTopLevel(['level' => 1, 'parent_id' => 5]));
    }

    public function testItemDepthCastsStringLevel(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame(2, $vm->getItemDepth(['level' => '2']));
        $this->assertSame(0, $vm->getItemDepth(['level' => null]));
    }

    public function testArrayItemsSupportActiveClassAndBreadcrumbs(): void
    {
        $vm = $this->menuViewModel();
        $child = ['item_id' => 2, 'parent_id' => 1, 'title' => 'Kids', 'url' => '/kids', 'level' => 1];
        $root = ['item_id' => 1, 'title' => 'Shop', 'url' => '/shop', 'item_type' => 'link', 'children' => [$child]];

        $this->assertTrue($vm->isActive($child, '/kids/'));
        $this->assertFalse($vm->isActive(['url' => '/kids', 'is_active' => 0], '/kids'));
        $this->assertSame('menu-item menu-item-link level-0 has-children extra', $vm->getItemClass($root, 'extra'));
        $this->assertSame([$root, $child], $vm->getBreadcrumbTrail($child, [$root]));
        $this->assertSame([$root], $vm->getBreadcrumbTrail($root, [$root]));
    }

    public function testBreadcrumbTrailWalksUpParents(): void
    {
        $grand = $this->item(['item_id' => 3, 'parent_id' => 2]);
        $parent = $this->item(['item_id' => 2, 'parent_id' => 1], [$grand]);
        $root = $this->item(['item_id' => 1], [$parent]);

        $trail = $this->menuViewModel()->getBreadcrumbTrail($grand, [$root]);

        $this->assertSame([1, 2, 3], array_map(fn ($i) => $i->getItemId(), $trail));
        $orphan = $this->item(['item_id' => 9, 'parent_id' => 8]);
        $this->assertSame([$orphan], $this->menuViewModel()->getBreadcrumbTrail($orphan, [$root]));
    }

    public function testTitleAndBadgeHtml(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('Tom &amp; Jerry', $vm->getItemTitleWithIcon(['title' => 'Tom & Jerry']));
        $this->assertSame(
            '<i class="fa-star megamenu-icon" aria-hidden="true"></i> Star',
            $vm->getItemTitleWithIcon(['title' => 'Star', 'icon_class' => 'fa-star'])
        );
        $this->assertStringStartsWith('<i class="fa-a megamenu-icon"', $vm->getItemTitleWithIcon(['title' => 'x', 'icon' => 'fa-a', 'icon_class' => 'fa-b']));
        $this->assertSame('', $vm->getBadgeHtml(['badge' => 'none']));
        $this->assertSame('', $vm->getBadgeHtml([]));
        $this->assertSame('<span class="pmenu-badge badge-new">NEW</span>', $vm->getBadgeHtml(['badge' => 'new']));
        $this->assertSame('<span class="pmenu-badge badge-hot">&lt;b&gt;</span>', $vm->getBadgeHtml(['badge' => 'hot', 'badge_text' => '<b>']));
    }

    public function testInlineStyles(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('', $vm->getItemInlineStyles([]));
        $this->assertSame(
            'background-color:#000;color:#fff;font-family:Arial;font-size:12px;font-weight:700;text-transform:uppercase;'
            . 'padding:1px;margin:2px;gap:3px;border-radius:4px;box-shadow:none;text-shadow:1px;opacity:0.5',
            $vm->getItemInlineStyles([
                'bg_color' => '#000', 'text_color' => '#fff', 'font_family' => 'Arial', 'font_size' => '12px',
                'font_weight' => '700', 'text_transform' => 'uppercase', 'padding' => '1px', 'margin' => '2px',
                'gap' => '3px', 'border_radius' => '4px', 'box_shadow' => 'none', 'text_shadow' => '1px', 'opacity' => '0.5',
            ])
        );
        $this->assertSame('', $vm->getItemInlineStyles([
            'font_family' => 'default', 'font_weight' => 'default', 'text_transform' => 'none', 'opacity' => '1',
        ]));
    }

    public function testDataAttributesAndAnimation(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame(['track' => 'x'], $vm->getCustomDataAttributes(['custom_data_attributes' => '{"track":"x"}']));
        $this->assertSame([], $vm->getCustomDataAttributes(['custom_data_attributes' => '{bad']));
        $this->assertSame([], $vm->getCustomDataAttributes(['custom_data_attributes' => '"str"']));
        $this->assertSame([], $vm->getCustomDataAttributes([]));
        $this->assertSame(
            ['data-hover-bg-color' => '#111', 'data-hover-text-color' => '#222', 'data-hover-effect' => 'glow'],
            $vm->getHoverDataAttributes(['hover_bg_color' => '#111', 'hover_text_color' => '#222', 'hover_effect' => 'glow'])
        );
        $this->assertSame([], $vm->getHoverDataAttributes(['hover_effect' => 'default']));
        $this->assertSame('animate__animated animate__fadeIn', $vm->getAnimationClass(['animation' => 'fadeIn']));
        $this->assertSame('', $vm->getAnimationClass(['animation' => 'none']));
    }

    public function testDeviceClassesAndAccessibility(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame('', $vm->getDeviceVisibilityClasses([]));
        $this->assertSame('hide-desktop hide-mobile', $vm->getDeviceVisibilityClasses(['show_on_desktop' => '0', 'show_on_mobile' => false]));
        $this->assertTrue($vm->isVisibleOnCurrentDevice(['show_on_tablet' => '0']));
        $this->assertSame('tip', $vm->getTooltipText(['tooltip_text' => 'tip']));
        $this->assertSame('', $vm->getCustomClickAction([]));
        $this->assertSame('Shop', $vm->getAriaLabel(['title' => 'Shop']));
        $this->assertSame('Go shopping', $vm->getAriaLabel(['title' => 'Shop', 'aria_label' => 'Go shopping']));
        $this->assertSame('menuitem', $vm->getAriaRole(['aria_role' => 'default']));
        $this->assertSame('link', $vm->getAriaRole(['aria_role' => 'link']));
        $this->assertSame('auto', $vm->getColumnWidth([]));
        $this->assertSame('25%', $vm->getColumnWidth(['column_width' => '25%']));
        $this->assertSame($vm->getMenuRenderer(), $vm->getMenuRenderer());
    }
}
