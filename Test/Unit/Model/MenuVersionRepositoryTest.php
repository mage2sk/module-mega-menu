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
use Panth\MegaMenu\Api\Data\MenuVersionInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterface;
use Panth\MegaMenu\Api\Data\MenuVersionSearchResultsInterfaceFactory;
use Panth\MegaMenu\Model\MenuVersion;
use Panth\MegaMenu\Model\MenuVersionRepository;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\Collection;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuVersionRepositoryTest extends TestCase
{
    private VersionResource&MockObject $resource;
    private MenuVersionInterfaceFactory&MockObject $factory;
    private CollectionFactory&MockObject $collectionFactory;
    private MenuVersionSearchResultsInterfaceFactory&MockObject $searchResultsFactory;
    private MenuVersionRepository $repository;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(VersionResource::class);
        $this->factory = $this->createMock(MenuVersionInterfaceFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(MenuVersionSearchResultsInterfaceFactory::class);
        $this->repository = new MenuVersionRepository(
            $this->resource,
            $this->factory,
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->createStub(CollectionProcessorInterface::class)
        );
    }

    private function version(array $data = []): MenuVersion
    {
        $resource = $this->createStub(VersionResource::class);
        $resource->method('getIdFieldName')->willReturn('version_id');

        return new MenuVersion($this->createStub(Context::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    public function testSaveDelegatesAndWraps(): void
    {
        $version = $this->version(['version_id' => 1]);
        $this->resource->expects($this->exactly(2))->method('save')
            ->willReturnOnConsecutiveCalls($this->resource, $this->throwException(new \RuntimeException('x')));

        $this->assertSame($version, $this->repository->save($version));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save the menu version: x');
        $this->repository->save($version);
    }

    public function testGetByIdCachesAndThrows(): void
    {
        $this->factory->method('create')->willReturnCallback(fn () => $this->version());
        $this->resource->expects($this->exactly(2))->method('load')
            ->willReturnCallback(function (MenuVersion $v, int $id) {
                if ($id === 2) {
                    $v->setId(2);
                }
                return $this->resource;
            });

        $found = $this->repository->getById(2);
        $this->assertSame(2, $found->getVersionId());
        $this->assertSame($found, $this->repository->getById(2));

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Menu version with id "3" does not exist.');
        $this->repository->getById(3);
    }

    public function testGetByMenuIdFiltersAndOrders(): void
    {
        $items = [$this->version(['version_id' => 9])];
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addMenuFilter')->with(4)->willReturnSelf();
        $collection->expects($this->once())->method('orderByVersionDesc')->willReturnSelf();
        $collection->method('getItems')->willReturn($items);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame($items, $this->repository->getByMenuId(4));
    }

    public function testGetList(): void
    {
        $criteria = $this->createStub(SearchCriteriaInterface::class);
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn([]);
        $collection->method('getSize')->willReturn(12);
        $this->collectionFactory->method('create')->willReturn($collection);
        $results = $this->createMock(MenuVersionSearchResultsInterface::class);
        $results->expects($this->once())->method('setSearchCriteria')->with($criteria);
        $results->expects($this->once())->method('setTotalCount')->with(12);
        $this->searchResultsFactory->method('create')->willReturn($results);

        $this->assertSame($results, $this->repository->getList($criteria));
    }

    public function testDeleteWrapsErrors(): void
    {
        $this->resource->method('delete')->willThrowException(new \RuntimeException('fk'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete the menu version: fk');
        $this->repository->delete($this->version(['version_id' => 1]));
    }

    public function testDeleteById(): void
    {
        $this->factory->method('create')->willReturnCallback(fn () => $this->version());
        $this->resource->method('load')->willReturnCallback(function (MenuVersion $v, int $id) {
            $v->setId($id);
            return $this->resource;
        });
        $this->resource->expects($this->once())->method('delete');

        $this->assertTrue($this->repository->deleteById(6));
    }
}
