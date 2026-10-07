<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel\Item;

use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\Model\ResourceModel\Item\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private function item(array $data): Item
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');

        return new Item($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    private function collection(array $items): Collection
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();
        $collection->method('getItems')->willReturn($items);

        return $collection;
    }

    private function items(): array
    {
        return [
            $this->item(['item_id' => 1, 'title' => 'Root']),
            $this->item(['item_id' => 2, 'parent_id' => 1, 'title' => 'Child']),
            $this->item(['item_id' => 3, 'parent_id' => 2, 'title' => 'Grandchild']),
            $this->item(['item_id' => 4, 'title' => 'Second root']),
            $this->item(['item_id' => 5, 'parent_id' => 99, 'title' => 'Orphan']),
        ];
    }

    public function testToTreeNestsChildrenAndDropsOrphans(): void
    {
        $tree = $this->collection($this->items())->toTree();

        $this->assertSame(['Root', 'Second root'], array_map(fn ($i) => $i->getTitle(), $tree));
        $child = $tree[0]->getChildren()[0];
        $this->assertSame('Child', $child->getTitle());
        $this->assertSame('Grandchild', $child->getChildren()[0]->getTitle());
        $this->assertFalse($tree[1]->hasChildren());
    }

    public function testToTreeFromSubtree(): void
    {
        $tree = $this->collection($this->items())->toTree(2);

        $this->assertSame(['Grandchild'], array_map(fn ($i) => $i->getTitle(), $tree));
    }

    public function testHierarchicalArray(): void
    {
        $array = $this->collection($this->items())->toHierarchicalArray();

        $this->assertSame('Root', $array[0]['title']);
        $this->assertSame('Child', $array[0]['children'][0]['title']);
        $this->assertSame('Grandchild', $array[0]['children'][0]['children'][0]['title']);
        $this->assertArrayNotHasKey('children', $array[1]);
    }
}
