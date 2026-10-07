<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    private function item(array $data = []): Item
    {
        return new Item(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $this->resource(),
            null,
            $data
        );
    }

    private function resource(): ItemResource
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');

        return $resource;
    }

    public function testIdentitiesUseId(): void
    {
        $item = $this->item();
        $item->setId(5);

        $this->assertSame([Item::CACHE_TAG . '_5'], $item->getIdentities());
    }

    public function testParentIdDistinguishesNullAndZero(): void
    {
        $this->assertNull($this->item()->getParentId());
        $this->assertSame(0, $this->item(['parent_id' => '0'])->getParentId());
        $this->assertSame(3, $this->item(['parent_id' => '3'])->getParentId());
    }

    public function testItemAndMenuIdTreatZeroAsNull(): void
    {
        $item = $this->item(['item_id' => '0', 'menu_id' => '9']);

        $this->assertNull($item->getItemId());
        $this->assertSame(9, $item->getMenuId());
    }

    public function testChildrenAreStoredOutsideData(): void
    {
        $item = $this->item();
        $this->assertFalse($item->hasChildren());
        $this->assertSame([], $item->getChildren());

        $child = $this->item(['title' => 'Child']);
        $this->assertSame($item, $item->setChildren([$child]));
        $this->assertTrue($item->hasChildren());
        $this->assertSame([$child], $item->getChildren());
        $this->assertNull($item->getData('children'));
    }

    public function testNumericAndBooleanCasts(): void
    {
        $item = $this->item([
            'columns' => '3',
            'position' => '2',
            'level' => '1',
            'submenu_columns' => '4',
            'is_active' => '1',
            'open_new_tab' => '0',
            'show_on_frontend' => 1,
            'show_children' => '',
        ]);

        $this->assertSame(3, $item->getColumns());
        $this->assertSame(2, $item->getPosition());
        $this->assertSame(1, $item->getLevel());
        $this->assertSame(4, $item->getSubmenuColumns());
        $this->assertTrue($item->getIsActive());
        $this->assertFalse($item->getOpenNewTab());
        $this->assertTrue($item->getShowOnFrontend());
        $this->assertFalse($item->getShowChildren());
    }

    public function testFluentSetters(): void
    {
        $item = $this->item();
        $item->setTitle('Shop')
            ->setItemType('category')
            ->setUrl('/shop')
            ->setTarget('_blank')
            ->setParentId(null)
            ->setPosition(5)
            ->setHoverEffect('glow');

        $this->assertSame('Shop', $item->getTitle());
        $this->assertSame('category', $item->getItemType());
        $this->assertSame('/shop', $item->getUrl());
        $this->assertSame('_blank', $item->getTarget());
        $this->assertNull($item->getParentId());
        $this->assertSame(5, $item->getPosition());
        $this->assertSame('glow', $item->getHoverEffect());
    }
}
