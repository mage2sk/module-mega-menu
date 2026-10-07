<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\MegaMenu\Api\Data\MenuInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterface;
use Panth\MegaMenu\Api\Data\MenuSearchResultsInterfaceFactory;
use Panth\MegaMenu\Api\Data\MenuVersionInterfaceFactory;
use Panth\MegaMenu\Api\MenuVersionRepositoryInterface;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuRepository;
use Panth\MegaMenu\Model\MenuVersion;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MenuRepositoryTest extends TestCase
{
    private MenuResource&MockObject $resource;
    private MenuInterfaceFactory&MockObject $menuFactory;
    private CollectionFactory&MockObject $collectionFactory;
    private MenuSearchResultsInterfaceFactory&MockObject $searchResultsFactory;
    private MenuVersionInterfaceFactory&MockObject $versionFactory;
    private MenuVersionRepositoryInterface&MockObject $versionRepository;
    private VersionResource&MockObject $versionResource;
    private BackendAuthSession&MockObject $authSession;
    private MenuRepository $repository;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(MenuResource::class);
        $this->menuFactory = $this->createMock(MenuInterfaceFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(MenuSearchResultsInterfaceFactory::class);
        $this->versionFactory = $this->createMock(MenuVersionInterfaceFactory::class);
        $this->versionRepository = $this->createMock(MenuVersionRepositoryInterface::class);
        $this->versionResource = $this->createMock(VersionResource::class);
        $this->authSession = $this->createPartialMock(BackendAuthSession::class, ['__call']);

        $this->repository = new MenuRepository(
            $this->resource,
            $this->menuFactory,
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->createStub(CollectionProcessorInterface::class),
            $this->versionFactory,
            $this->versionRepository,
            $this->versionResource,
            $this->authSession,
            $this->createStub(LoggerInterface::class)
        );
    }

    private function menu(array $data = []): Menu
    {
        $resource = $this->createStub(MenuResource::class);
        $resource->method('getIdFieldName')->willReturn('menu_id');

        return new Menu($this->createStub(Context::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    private function version(): MenuVersion
    {
        $resource = $this->createStub(VersionResource::class);
        $resource->method('getIdFieldName')->willReturn('version_id');

        return new MenuVersion($this->createStub(Context::class), $this->createStub(Registry::class), $resource);
    }

    public function testSaveCreatesVersionSnapshot(): void
    {
        $menu = $this->menu([
            'menu_id' => 3,
            'title' => 'Main',
            'identifier' => 'main',
            'items_json' => '[{"title":"A"}]',
            'css_class' => 'nav',
            'container_bg_color' => '#000',
            'item_gap' => '4px',
            'is_active' => 1,
            'store_ids' => [0, 1],
            'version_comment' => 'note',
        ]);
        $version = $this->version();

        $this->resource->expects($this->once())->method('save')->with($menu);
        $this->versionResource->method('getNextVersionNumber')->with(3)->willReturn(6);
        $this->authSession->method('__call')->willReturnCallback(
            fn (string $name) => $name === 'getUser' ? new DataObject(['user_name' => 'jane']) : null
        );
        $this->versionFactory->method('create')->willReturn($version);
        $this->versionRepository->expects($this->once())->method('save')->with($version);

        $this->assertSame($menu, $this->repository->save($menu));
        $this->assertSame(3, $version->getMenuId());
        $this->assertSame(6, $version->getVersionNumber());
        $this->assertSame('Main', $version->getTitle());
        $this->assertSame('main', $version->getIdentifier());
        $this->assertSame('[{"title":"A"}]', $version->getItemsJson());
        $this->assertSame('#000', $version->getContainerBgColor());
        $this->assertSame('4px', $version->getItemGap());
        $this->assertSame('0,1', $version->getStoreIds());
        $this->assertSame('jane', $version->getCreatedBy());
        $this->assertSame('note', $version->getVersionComment());
        $this->assertTrue($version->getIsActive());
    }

    public function testSaveWithoutAdminUserLeavesCreatedByEmpty(): void
    {
        $menu = $this->menu(['menu_id' => 3, 'title' => 'T', 'identifier' => 'i']);
        $version = $this->version();
        $this->versionResource->method('getNextVersionNumber')->willReturn(1);
        $this->authSession->method('__call')->willReturn(null);
        $this->versionFactory->method('create')->willReturn($version);

        $this->repository->save($menu);

        $this->assertNull($version->getCreatedBy());
        $this->assertFalse($version->hasData('version_comment'));
    }

    public function testSaveWrapsResourceFailure(): void
    {
        $this->resource->method('save')->willThrowException(new \RuntimeException('duplicate key'));
        $this->versionRepository->expects($this->never())->method('save');

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not save the menu: duplicate key');
        $this->repository->save($this->menu(['menu_id' => 1]));
    }

    public function testSaveWrapsVersionFailure(): void
    {
        $this->versionResource->method('getNextVersionNumber')->willReturn(1);
        $this->authSession->method('__call')->willReturn(null);
        $this->versionFactory->method('create')->willReturn($this->version());
        $this->versionRepository->method('save')->willThrowException(new \RuntimeException('version table'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('version table');
        $this->repository->save($this->menu(['menu_id' => 1, 'title' => 'T', 'identifier' => 'i']));
    }

    public function testGetByIdLoadsOnceAndCaches(): void
    {
        $menu = $this->menu();
        $this->menuFactory->expects($this->once())->method('create')->willReturn($menu);
        $this->resource->expects($this->once())->method('load')
            ->willReturnCallback(function (Menu $model, int $id) {
                $model->setId($id);
                return $this->resource;
            });

        $this->assertSame($menu, $this->repository->getById(4));
        $this->assertSame($menu, $this->repository->getById(4));
    }

    public function testGetByIdThrowsWhenMissing(): void
    {
        $this->menuFactory->method('create')->willReturn($this->menu());

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Menu with id "99" does not exist.');
        $this->repository->getById(99);
    }

    public function testGetByIdentifierHydratesModel(): void
    {
        $this->resource->method('loadByIdentifier')->with('main', 1)->willReturn(['menu_id' => 2, 'title' => 'Main']);
        $this->menuFactory->method('create')->willReturn($this->menu());

        $menu = $this->repository->getByIdentifier('main', 1);

        $this->assertSame(2, $menu->getMenuId());
        $this->assertSame('Main', $menu->getTitle());
    }

    public function testGetByIdentifierThrowsWhenNotFound(): void
    {
        $this->resource->method('loadByIdentifier')->willReturn([]);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByIdentifier('missing');
    }

    public function testGetListPopulatesSearchResults(): void
    {
        $criteria = $this->createStub(SearchCriteriaInterface::class);
        $items = [$this->menu(['menu_id' => 1])];
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $collection->method('getSize')->willReturn(1);
        $this->collectionFactory->method('create')->willReturn($collection);

        $results = $this->createMock(MenuSearchResultsInterface::class);
        $results->expects($this->once())->method('setSearchCriteria')->with($criteria);
        $results->expects($this->once())->method('setItems')->with($items);
        $results->expects($this->once())->method('setTotalCount')->with(1);
        $this->searchResultsFactory->method('create')->willReturn($results);

        $this->assertSame($results, $this->repository->getList($criteria));
    }

    public function testDeleteAndWrapError(): void
    {
        $menu = $this->menu(['menu_id' => 5]);
        $this->resource->expects($this->exactly(2))->method('delete')
            ->willReturnOnConsecutiveCalls($this->resource, $this->throwException(new \RuntimeException('fk')));

        $this->assertTrue($this->repository->delete($menu));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete the menu: fk');
        $this->repository->delete($menu);
    }

    public function testDeleteByIdThrowsForMissingMenu(): void
    {
        $this->menuFactory->method('create')->willReturn($this->menu());
        $this->resource->expects($this->never())->method('delete');

        $this->expectException(NoSuchEntityException::class);
        $this->repository->deleteById(8);
    }
}
