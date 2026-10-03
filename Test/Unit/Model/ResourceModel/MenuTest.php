<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel;

use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuTest extends TestCase
{
    use DbStubTrait;
    use MenuModelTrait;

    private function menuResource(?array $fetchCol = null, $fetchRow = false): MenuResource
    {
        $connection = $this->connection();
        $connection->method('fetchCol')->willReturn($fetchCol ?? []);
        $connection->method('fetchRow')->willReturn($fetchRow);

        return $this->resource(MenuResource::class, $connection, 'panth_megamenu_menu');
    }

    public function testBeforeSaveImplodesArrayStoreIds(): void
    {
        $resource = $this->menuResource();
        $menu = $this->newMenu(['store_ids' => [0, 2]]);
        $scalar = $this->newMenu(['store_ids' => '1']);

        $this->invoke($resource, '_beforeSave', $menu);
        $this->invoke($resource, '_beforeSave', $scalar);

        $this->assertSame('0,2', $menu->getData('store_ids'));
        $this->assertSame('1', $scalar->getData('store_ids'));
    }

    public function testAfterSaveReplacesStoreRelations(): void
    {
        $resource = $this->menuResource();

        $this->invoke($resource, '_afterSave', $this->newMenu(['menu_id' => 5, 'store_ids' => '0,3']));

        $this->assertSame(['delete', ['panth_megamenu_store', ['menu_id = ?' => 5]]], $this->writes[0]);
        $this->assertSame(['insertMultiple', ['panth_megamenu_store', [
            ['menu_id' => 5, 'store_id' => '0'],
            ['menu_id' => 5, 'store_id' => '3'],
        ]]], $this->writes[1]);
    }

    public function testAfterSaveWithoutStoresOnlyDeletes(): void
    {
        $this->invoke($this->menuResource(), '_afterSave', $this->newMenu(['menu_id' => 5]));

        $this->assertCount(1, $this->writes);
        $this->assertSame('delete', $this->writes[0][0]);
    }

    public function testAfterLoadReadsStoreIds(): void
    {
        $menu = $this->newMenu(['menu_id' => 6]);

        $this->invoke($this->menuResource(['0', '1']), '_afterLoad', $menu);

        $this->assertSame(['0', '1'], $menu->getStoreIds());
        $this->assertSame([['menu_id = ?', 6]], $this->whereCalls());
    }

    public function testBeforeDeleteRemovesRelations(): void
    {
        $this->invoke($this->menuResource(), '_beforeDelete', $this->newMenu(['menu_id' => 7]));

        $this->assertSame([['delete', ['panth_megamenu_store', ['menu_id = ?' => 7]]]], $this->writes);
    }

    public function testLoadByIdentifierWithStoreJoinsRelations(): void
    {
        $resource = $this->menuResource(null, ['menu_id' => 1, 'identifier' => 'main']);

        $this->assertSame(['menu_id' => 1, 'identifier' => 'main'], $resource->loadByIdentifier('main', 2));
        $this->assertSame([
            ['main.identifier = ?', 'main'],
            ['main.is_active = ?', 1],
            ['store.store_id IN (?)', [0, 2]],
        ], $this->whereCalls());
        $this->assertContains(['limit', [1]], $this->selectCalls);
    }

    public function testLoadByIdentifierWithoutStoreAndMissingRow(): void
    {
        $this->assertSame([], $this->menuResource()->loadByIdentifier('none'));
        $this->assertCount(2, $this->whereCalls());
        $this->assertNotContains('joinLeft', array_column($this->selectCalls, 0));
    }
}
