<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel;

use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ItemTest extends TestCase
{
    use DbStubTrait;

    private function itemResource(array $fetch = []): ItemResource
    {
        $connection = $this->connection();
        foreach (['fetchRow', 'fetchOne', 'fetchCol', 'fetchAll'] as $method) {
            $values = $fetch[$method] ?? [false];
            $connection->method($method)->willReturnOnConsecutiveCalls(...$values);
        }

        return $this->resource(ItemResource::class, $connection, 'panth_megamenu_item');
    }

    private function item(array $data): Item
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');

        return new Item($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    public function testRootItemGetsLevelZeroAndOwnPath(): void
    {
        $item = $this->item(['item_id' => 7]);

        $this->invoke($this->itemResource(), '_beforeSave', $item);

        $this->assertSame(0, $item->getLevel());
        $this->assertSame('7', $item->getPath());
    }

    public function testChildOfExistingParentInheritsPath(): void
    {
        $item = $this->item(['item_id' => 9, 'parent_id' => 3]);

        $this->invoke($this->itemResource(['fetchRow' => [['path' => '1/3', 'level' => '1']]]), '_beforeSave', $item);

        $this->assertSame(2, $item->getLevel());
        $this->assertSame('1/3/9', $item->getPath());
        $this->assertSame([['item_id = ?', 3]], $this->whereCalls());
    }

    public function testNewChildRemembersParentPath(): void
    {
        $item = $this->item(['parent_id' => 3]);

        $this->invoke($this->itemResource(['fetchRow' => [['path' => '1/3', 'level' => 1]]]), '_beforeSave', $item);

        $this->assertSame('1/3', $item->getData('_parent_path'));
        $this->assertNull($item->getPath());
    }

    public function testMissingParentPromotesToRoot(): void
    {
        $item = $this->item(['item_id' => 9, 'parent_id' => 3]);

        $this->invoke($this->itemResource(), '_beforeSave', $item);

        $this->assertNull($item->getParentId());
        $this->assertSame(0, $item->getLevel());
        $this->assertNull($item->getPath());
    }

    public function testAfterSaveWritesPathForNewItem(): void
    {
        $item = $this->item(['item_id' => 12, '_parent_path' => '1/3']);

        $this->invoke($this->itemResource(['fetchAll' => [[]]]), '_afterSave', $item);

        $this->assertSame(['update', ['panth_megamenu_item', ['path' => '1/3/12'], ['item_id = ?' => 12]]], $this->writes[0]);
        $this->assertSame('1/3/12', $item->getPath());
    }

    public function testUpdatingChildrenPathsRecursesIntoGrandchildren(): void
    {
        $item = $this->item(['item_id' => 3, 'path' => '3', 'level' => 0]);
        $resource = $this->itemResource(['fetchAll' => [[['item_id' => 8]], [['item_id' => 11]], []]]);

        $this->invoke($resource, 'updateChildrenPaths', $item);

        $this->assertSame(['update', ['panth_megamenu_item', ['path' => '3/8', 'level' => 1], ['item_id = ?' => 8]]], $this->writes[0]);
        $this->assertSame(['update', ['panth_megamenu_item', ['path' => '3/8/11', 'level' => 2], ['item_id = ?' => 11]]], $this->writes[1]);
        $this->assertSame([['parent_id = ?', 3], ['parent_id = ?', 8], ['parent_id = ?', 11]], $this->whereCalls());
    }

    public function testUpdatingChildrenPathsWithoutChildrenIsNoop(): void
    {
        $this->invoke($this->itemResource(['fetchAll' => [[]]]), 'updateChildrenPaths', $this->item(['item_id' => 3]));

        $this->assertSame([], $this->writes);
    }

    public function testBeforeDeleteRemovesDescendantsByPath(): void
    {
        $this->invoke($this->itemResource(), '_beforeDelete', $this->item(['item_id' => 3, 'path' => '1/3']));

        $this->assertSame(['delete', ['panth_megamenu_item', ['path LIKE ?' => '1/3/%', 'item_id != ?' => 3]]], $this->writes[0]);
    }

    public function testReindexPositionsRenumbersSiblings(): void
    {
        $this->itemResource(['fetchCol' => [[5, 9, 2]]])->reindexPositions(1, 4);

        $this->assertSame([['menu_id = ?', 1], ['parent_id = ?', 4]], $this->whereCalls());
        $this->assertSame([0, 1, 2], array_map(fn ($w) => $w[1][1]['position'], $this->writes));
        $this->assertSame([5, 9, 2], array_map(fn ($w) => $w[1][2]['item_id = ?'], $this->writes));
    }

    public function testReindexRootLevel(): void
    {
        $this->itemResource(['fetchCol' => [[]]])->reindexPositions(1);

        $this->assertSame([['menu_id = ?', 1], ['parent_id IS NULL']], $this->whereCalls());
    }

    public function testMoveItemIgnoresUnknownItem(): void
    {
        $this->itemResource()->moveItem(5, 2, 0);

        $this->assertSame([], $this->writes);
    }

    public function testMoveItemUpdatesPathLevelAndReindexesBothParents(): void
    {
        $resource = $this->itemResource([
            'fetchRow' => [['item_id' => '5', 'parent_id' => '3', 'menu_id' => '1'], ['path' => '2', 'level' => '0']],
            'fetchAll' => [[]],
            'fetchCol' => [['7'], ['5']],
        ]);

        $resource->moveItem(5, 2, 0);

        $this->assertSame(['update', ['panth_megamenu_item', ['parent_id' => 2, 'position' => 0], ['item_id = ?' => 5]]], $this->writes[0]);
        $this->assertSame(['update', ['panth_megamenu_item', ['path' => '2/5', 'level' => 1], ['item_id = ?' => 5]]], $this->writes[1]);
        $this->assertSame(['update', ['panth_megamenu_item', ['position' => 0], ['item_id = ?' => '7']]], $this->writes[2]);
        $this->assertSame(['update', ['panth_megamenu_item', ['position' => 0], ['item_id = ?' => '5']]], $this->writes[3]);
        $this->assertCount(4, $this->writes);
    }

    public function testMaxPosition(): void
    {
        $this->assertSame(4, $this->itemResource(['fetchOne' => ['4']])->getMaxPosition(1, 2));
        $this->assertSame(-1, $this->itemResource(['fetchOne' => [false]])->getMaxPosition(1));
        $this->assertSame(0, $this->itemResource(['fetchOne' => [null]])->getMaxPosition(1));
    }

    public function testChildrenIds(): void
    {
        $this->assertSame(['4', '5'], $this->itemResource(['fetchCol' => [['4', '5']]])->getChildrenIds(3));
        $this->assertSame(['parent_id = ?', 3], $this->whereCalls()[0]);

        $this->selectCalls = [];
        $this->assertSame(['8'], $this->itemResource(['fetchOne' => ['1/3'], 'fetchCol' => [['8']]])->getChildrenIds(3, true));
        $this->assertSame(['path LIKE ?', '1/3/%'], $this->whereCalls()[1]);

        $this->assertSame([], $this->itemResource(['fetchOne' => [false]])->getChildrenIds(3, true));
    }
}
