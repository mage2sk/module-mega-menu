<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Ui\Component\Listing;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\MegaMenu\Ui\Component\Listing\LikeFulltextFilter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LikeFulltextFilterTest extends TestCase
{
    private array $where = [];

    private function collection(): AbstractDb
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($c) => '`' . $c . '`');
        $connection->method('quoteInto')->willReturnCallback(fn ($text, $value) => str_replace('?', "'" . $value . "'", $text));
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($condition) use ($select) {
            $this->where[] = $condition;
            return $select;
        });
        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);

        return $collection;
    }

    public function testKeywordMatchesTitleOrIdentifier(): void
    {
        $filter = new LikeFulltextFilter(['main_table.title', 'main_table.identifier', 5]);

        $filter->apply($this->collection(), new Filter(['value' => '  sample_menu% ']));

        $this->assertSame(
            ["`main_table.title` LIKE '%sample\\_menu\\%%' OR `main_table.identifier` LIKE '%sample\\_menu\\%%'"],
            $this->where
        );
    }

    public function testBlankKeywordOrNoColumnsDoNothing(): void
    {
        (new LikeFulltextFilter(['main_table.title']))->apply($this->collection(), new Filter(['value' => '  ']));
        (new LikeFulltextFilter(['main_table.title']))->apply($this->collection(), new Filter(['value' => ['x']]));
        (new LikeFulltextFilter([]))->apply($this->collection(), new Filter(['value' => 'menu']));
        (new LikeFulltextFilter(['main_table.title']))->apply($this->createStub(Collection::class), new Filter(['value' => 'menu']));

        $this->assertSame([], $this->where);
    }
}
