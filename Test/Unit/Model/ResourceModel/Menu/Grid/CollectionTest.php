<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel\Menu\Grid;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\MegaMenu\Model\ResourceModel\Menu\Grid\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private array $wheres = [];
    private array $conditions = [];

    private function collection(): Collection
    {
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($cond) use ($select) {
            $this->wheres[] = (string) $cond;
            return $select;
        });

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('prepareSqlCondition')->willReturnCallback(function ($field, $condition) {
            $this->conditions[] = [$field, $condition];
            return $field . ' COND';
        });

        $collection = new class extends Collection {
            public function __construct()
            {
            }

            public function getTable($table)
            {
                return 'pfx_' . $table;
            }
        };
        $selectProperty = new \ReflectionProperty(\Magento\Framework\Data\Collection\AbstractDb::class, '_select');
        $selectProperty->setValue($collection, $select);
        $connProperty = new \ReflectionProperty(\Magento\Framework\Data\Collection\AbstractDb::class, '_conn');
        $connProperty->setValue($collection, $connection);

        return $collection;
    }

    public function testItemCountSqlCountsItemRowsAndFallsBackToItemsJson(): void
    {
        $sql = $this->collection()->getItemCountSql();

        $this->assertStringContainsString(
            '(SELECT COUNT(*) FROM pfx_panth_megamenu_item WHERE menu_id = main_table.menu_id)',
            $sql
        );
        $this->assertStringContainsString('JSON_VALID(main_table.items_json)', $sql);
        $this->assertStringContainsString('JSON_LENGTH(main_table.items_json)', $sql);
        $this->assertStringStartsWith('(CASE WHEN', $sql);
    }

    public function testItemCountRangeFilterIsAppliedOnTheExpression(): void
    {
        $collection = $this->collection();

        $result = $collection->addFieldToFilter('item_count', ['from' => '10']);

        $this->assertSame($collection, $result);
        $this->assertCount(1, $this->wheres);
        $this->assertSame($collection->getItemCountSql(), $this->conditions[0][0]);
        $this->assertSame(['from' => '10'], $this->conditions[0][1]);
        $this->assertStringNotContainsString('`item_count`', $this->wheres[0]);
    }
}
