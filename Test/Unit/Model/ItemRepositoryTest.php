<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\MegaMenu\Api\Data\ItemInterfaceFactory;
use Panth\MegaMenu\Api\Data\ItemSearchResultsInterface;
use Panth\MegaMenu\Api\Data\ItemSearchResultsInterfaceFactory;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ItemRepository;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\Model\ResourceModel\Item\Collection;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ItemRepositoryTest extends TestCase
{
    private ItemResource&MockObject $resource;
    private ItemInterfaceFactory&MockObject $itemFactory;
    private CollectionFactory&MockObject $collectionFactory;
    private ItemSearchResultsInterfaceFactory&MockObject $searchResultsFactory;
    private ItemRepository $repository;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(ItemResource::class);
        $this->itemFactory = $this->createMock(ItemInterfaceFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(ItemSearchResultsInterfaceFactory::class);

        $this->repository = new ItemRepository(
            $this->resource,
            $this->itemFactory,
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->createStub(CollectionProcessorInterface::class)
        );
    }

    private function item(array $data = []): Item
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');

        return new Item($this->createStub(Context::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    private function loadable(): void
    {
        $this->itemFactory->method('create')->willReturnCallback(fn () => $this->item());
        $this->resource->method('load')->willReturnCallback(function (Item $item, int $id) {
            if ($id < 100) {
                $item->setId($id);
            }
            return $this->resource;
        });
    }

    public function testSaveNewItemAppendsAtEndOfSiblings(): void
    {
        $item = $this->item(['menu_id' => 2, 'parent_id' => 7]);
        $this->resource->expects($this->once())->method('getMaxPosition')->with(2, 7)->willReturn(4);
        $this->resource->expects($this->once())->method('save')->with($item);

        $this->repository->save($item);

        $this->assertSame(5, $item->getPosition());
    }

    public function testSaveKeepsExplicitPosition(): void
    {
        $item = $this->item(['menu_id' => 2, 'position' => 3]);
        $this->resource->expects($this->never())->method('getMaxPosition');

        $this->assertSame($item, $this->repository->save($item));
        $this->assertSame(3, $item->getPosition());
    }

    public function testSaveExistingItemSkipsPositionLookup(): void
    {
        $item = $this->item(['item_id' => 10, 'menu_id' => 2]);
        $this->resource->expects($this->never())->method('getMaxPosition');

        $this->repository->save($item);
        $this->assertSame(0, $item->getPosition());
    }

    public function testSaveWrapsErrors(): void
    {
        $this->resource->method('save')->willThrowException(new \RuntimeException('boom'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save the item: boom');
        $this->repository->save($this->item(['item_id' => 1]));
    }

    public function testGetByIdCachesAndThrowsForMissing(): void
    {
        $this->loadable();

        $first = $this->repository->getById(5);
        $this->assertSame(5, $first->getItemId());
        $this->assertSame($first, $this->repository->getById(5));

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Item with id "150" does not exist.');
        $this->repository->getById(150);
    }

    public function testGetListPopulatesResults(): void
    {
        $criteria = $this->createStub(SearchCriteriaInterface::class);
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn([]);
        $collection->method('getSize')->willReturn(0);
        $this->collectionFactory->method('create')->willReturn($collection);
        $results = $this->createMock(ItemSearchResultsInterface::class);
        $results->expects($this->once())->method('setTotalCount')->with(0);
        $results->expects($this->once())->method('setItems')->with([]);
        $this->searchResultsFactory->method('create')->willReturn($results);

        $this->assertSame($results, $this->repository->getList($criteria));
    }

    public function testDeleteWrapsErrors(): void
    {
        $this->resource->method('delete')->willThrowException(new \RuntimeException('locked'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete the item: locked');
        $this->repository->delete($this->item(['item_id' => 1]));
    }

    public function testDeleteByIdDeletesLoadedItem(): void
    {
        $this->loadable();
        $this->resource->expects($this->once())->method('delete')
            ->with($this->callback(fn (Item $i) => $i->getItemId() === 3));

        $this->assertTrue($this->repository->deleteById(3));
    }

    public function testGetMenuTreeDelegatesToCollection(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('loadMenuTree')->with(4, 2, true)->willReturn(['tree']);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame(['tree'], $this->repository->getMenuTree(4, 2));
    }

    public function testMoveItemToRootSkipsCycleCheck(): void
    {
        $this->loadable();
        $this->resource->expects($this->never())->method('getChildrenIds');
        $this->resource->expects($this->once())->method('moveItem')->with(5, null, 2);

        $this->assertTrue($this->repository->moveItem(5, null, 2));
    }

    public function testMoveItemUnderValidParent(): void
    {
        $this->loadable();
        $this->resource->method('getChildrenIds')->with(5, true)->willReturn([6, 7]);
        $this->resource->expects($this->once())->method('moveItem')->with(5, 9, 0);

        $this->assertTrue($this->repository->moveItem(5, 9, 0));
    }

    public function testMoveItemUnderOwnDescendantIsRejected(): void
    {
        $this->loadable();
        $this->resource->method('getChildrenIds')->willReturn([6, 7]);
        $this->resource->expects($this->never())->method('moveItem');

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Cannot move item under itself or its children.');
        $this->repository->moveItem(5, 7, 0);
    }

    public function testMoveMissingItemIsWrapped(): void
    {
        $this->loadable();

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not move the item: Item with id "200" does not exist.');
        $this->repository->moveItem(200, null, 0);
    }
}
