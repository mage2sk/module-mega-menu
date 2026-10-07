<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\Collection;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\CollectionFactory;
use Panth\MegaMenu\Ui\DataProvider\MenuVersionDataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuVersionDataProviderTest extends TestCase
{
    private array $filters = [];
    private array $orders = [];
    private array $columns = [];

    private function provider(?string $menuId, array $items = [], bool $fail = false): MenuVersionDataProvider
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($name) => $name === 'menu_id' ? $menuId : null);

        $select = $this->createStub(Select::class);
        $select->method('columns')->willReturnCallback(function ($cols) use ($select) {
            $this->columns = $cols;
            return $select;
        });
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(function ($field, $value) use ($collection) {
            $this->filters[] = [$field, $value];
            return $collection;
        });
        $collection->method('setOrder')->willReturnCallback(function ($field, $dir) use ($collection) {
            $this->orders[] = [$field, $dir];
            return $collection;
        });
        $collection->method('getSelect')->willReturn($select);
        if ($fail) {
            $collection->method('getItems')->willThrowException(new \RuntimeException('sql error'));
        } else {
            $collection->method('getItems')->willReturn($items);
        }
        $collection->method('getSize')->willReturn(count($items));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return new MenuVersionDataProvider(
            'versions',
            'version_id',
            'id',
            $this->createStub(ReportingInterface::class),
            $this->createStub(SearchCriteriaBuilder::class),
            $request,
            $this->createStub(FilterBuilder::class),
            $factory
        );
    }

    public function testWithoutMenuIdReturnsNothing(): void
    {
        $this->assertSame(['totalRecords' => 0, 'items' => []], $this->provider(null)->getData());
        $this->assertSame([], $this->filters);
    }

    public function testVersionsForMenuAreSortedAndAnnotated(): void
    {
        $data = $this->provider('4', [
            new DataObject(['version_id' => 2, 'version_comment' => 'Restored']),
            new DataObject(['version_id' => 1]),
        ])->getData();

        $this->assertSame([['menu_id', '4']], $this->filters);
        $this->assertSame([['version_number', 'DESC']], $this->orders);
        $this->assertSame([], $this->columns);
        $this->assertSame(2, $data['totalRecords']);
        $this->assertSame(0, $data['items'][0]['item_count']);
        $this->assertSame('Restored', $data['items'][0]['version_comment_full']);
        $this->assertArrayNotHasKey('version_comment_full', $data['items'][1]);
    }

    public function testItemCountMatchesStoredItems(): void
    {
        $json = json_encode([
            ['id' => 1, 'item_id' => 1, 'title' => 'A', 'children_ids' => ['id' => 9]],
            ['id' => 2, 'item_id' => 2, 'parent_id' => 1, 'title' => 'B'],
            ['temp_id' => 'tmp_3', 'title' => 'C'],
        ]);
        $data = $this->provider('4', [
            new DataObject(['version_id' => 3, 'items_json' => $json]),
            new DataObject(['version_id' => 2, 'items_json' => '[]']),
            new DataObject(['version_id' => 1, 'items_json' => '{broken']),
        ])->getData();

        $this->assertSame([3, 0, 0], array_column($data['items'], 'item_count'));
    }

    public function testErrorsAreReturnedInPayload(): void
    {
        $this->assertSame(
            ['totalRecords' => 0, 'items' => [], 'error' => 'sql error'],
            $this->provider('4', [], true)->getData()
        );
    }
}
