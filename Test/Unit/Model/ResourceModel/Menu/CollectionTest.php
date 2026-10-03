<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel\Menu;

use Magento\Store\Model\Store;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    private array $filters = [];

    private function collection(): Collection
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFilter', 'addFieldToFilter'])
            ->getMock();
        $collection->method('addFilter')->willReturnCallback(function ($field, $value, $type) use ($collection) {
            $this->filters[] = [$field, $value, $type];
            return $collection;
        });
        $collection->method('addFieldToFilter')->willReturnCallback(function ($field, $value) use ($collection) {
            $this->filters[] = [$field, $value];
            return $collection;
        });

        return $collection;
    }

    public function testStoreFilterIncludesAdminAndIsAppliedOnce(): void
    {
        $collection = $this->collection();

        $this->assertSame($collection, $collection->addStoreFilter(2));
        $collection->addStoreFilter(5);

        $this->assertSame([['store_id', ['in' => [2, 0]], 'public']], $this->filters);
    }

    public function testStoreFilterAcceptsStoreObjectAndArrays(): void
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(3);

        $this->collection()->addStoreFilter($store, false);
        $this->collection()->addStoreFilter([1, 2]);

        $this->assertSame([
            ['store_id', ['in' => [3]], 'public'],
            ['store_id', ['in' => [1, 2, 0]], 'public'],
        ], $this->filters);
    }

    public function testActiveAndIdentifierFilters(): void
    {
        $collection = $this->collection();

        $this->assertSame($collection, $collection->addActiveFilter());
        $this->assertSame($collection, $collection->addIdentifierFilter('main'));
        $this->assertSame([['is_active', 1], ['identifier', 'main']], $this->filters);
    }
}
