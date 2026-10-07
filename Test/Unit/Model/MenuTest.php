<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    private function menu(array $data = []): Menu
    {
        return new Menu(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $this->resource(),
            null,
            $data
        );
    }

    private function resource(): MenuResource
    {
        $resource = $this->createStub(MenuResource::class);
        $resource->method('getIdFieldName')->willReturn('menu_id');

        return $resource;
    }

    public function testIdentitiesWithoutIdContainOnlyGenericTags(): void
    {
        $this->assertSame([Menu::CACHE_TAG, MenuHelper::CACHE_TAG], $this->menu()->getIdentities());
    }

    public function testIdentitiesIncludeIdSpecificTags(): void
    {
        $menu = $this->menu(['menu_id' => 12]);
        $menu->setId(12);

        $this->assertSame([
            Menu::CACHE_TAG,
            MenuHelper::CACHE_TAG,
            Menu::CACHE_TAG . '_12',
            MenuHelper::CACHE_TAG . '_12',
        ], $menu->getIdentities());
    }

    public function testMenuIdIsCastOrNull(): void
    {
        $this->assertNull($this->menu()->getMenuId());
        $this->assertNull($this->menu(['menu_id' => '0'])->getMenuId());
        $this->assertSame(7, $this->menu(['menu_id' => '7'])->getMenuId());
    }

    public function testStoreIdsFromCommaString(): void
    {
        $this->assertSame(['0', '1', '2'], $this->menu(['store_ids' => '0,1,2'])->getStoreIds());
    }

    public function testStoreIdsFromArrayAndFallback(): void
    {
        $this->assertSame([1, 3], $this->menu(['store_ids' => [1, 3]])->getStoreIds());
        $this->assertSame([], $this->menu()->getStoreIds());
        $this->assertSame([], $this->menu(['store_ids' => 5])->getStoreIds());
    }

    public function testScalarCasts(): void
    {
        $menu = $this->menu(['is_active' => '1', 'sort_order' => '4']);

        $this->assertTrue($menu->getIsActive());
        $this->assertSame(4, $menu->getSortOrder());
        $this->assertFalse($this->menu()->getIsActive());
        $this->assertSame(0, $this->menu()->getSortOrder());
    }

    public function testSettersAreFluentAndRoundTrip(): void
    {
        $menu = $this->menu();
        $result = $menu->setTitle('Main')
            ->setIdentifier('main')
            ->setIsActive(true)
            ->setSortOrder(2)
            ->setStoreIds([0])
            ->setItemsJson('[]')
            ->setMenuType('header');

        $this->assertSame($menu, $result);
        $this->assertSame('Main', $menu->getTitle());
        $this->assertSame('main', $menu->getIdentifier());
        $this->assertSame([0], $menu->getStoreIds());
        $this->assertSame('[]', $menu->getItemsJson());
        $this->assertSame('header', $menu->getMenuType());
    }
}
