<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;
use Panth\MegaMenu\Model\Export\ConvertToCsv;
use Panth\MegaMenu\Model\Export\ConvertToXml;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConvertersTest extends TestCase
{
    private array $csvRows = [];
    private array $pages = [];
    private string $written = '';
    private ?string $openedFile = null;
    private array $createdDirs = [];

    private function filesystem(): Filesystem
    {
        $stream = $this->createStub(FileWriteInterface::class);
        $stream->method('writeCsv')->willReturnCallback(function (array $row) {
            $this->csvRows[] = $row;
            return 1;
        });
        $stream->method('write')->willReturnCallback(function (string $data) {
            $this->written .= $data;
            return strlen($data);
        });
        $directory = $this->createStub(WriteInterface::class);
        $directory->method('create')->willReturnCallback(function (string $dir) {
            $this->createdDirs[] = $dir;
            return true;
        });
        $directory->method('openFile')->willReturnCallback(function (string $file) use ($stream) {
            $this->openedFile = $file;
            return $stream;
        });
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($directory);

        return $filesystem;
    }

    private function filter(int $total, array $items): Filter
    {
        $criteria = $this->createStub(SearchCriteriaInterface::class);
        $criteria->method('setCurrentPage')->willReturnCallback(function (int $page) use ($criteria) {
            $this->pages[] = $page;
            return $criteria;
        });
        $criteria->method('setPageSize')->willReturnSelf();
        $result = $this->createStub(SearchResultInterface::class);
        $result->method('getTotalCount')->willReturn($total);
        $result->method('getItems')->willReturn($items);
        $dataProvider = $this->createStub(DataProviderInterface::class);
        $dataProvider->method('getSearchCriteria')->willReturn($criteria);
        $dataProvider->method('getSearchResult')->willReturn($result);
        $context = $this->createStub(ContextInterface::class);
        $context->method('getDataProvider')->willReturn($dataProvider);
        $component = $this->createStub(UiComponentInterface::class);
        $component->method('getName')->willReturn('panth_menu_listing');
        $component->method('getContext')->willReturn($context);
        $filter = $this->createStub(Filter::class);
        $filter->method('getComponent')->willReturn($component);

        return $filter;
    }

    private function metadata(): MetadataProvider
    {
        $metadata = $this->createStub(MetadataProvider::class);
        $metadata->method('getFields')->willReturn(['menu_id', 'title']);
        $metadata->method('getOptions')->willReturn([]);
        $metadata->method('getHeaders')->willReturn(['ID', 'Title']);
        $metadata->method('getRowData')->willReturnCallback(
            fn (DocumentInterface $doc) => [$doc->getId(), 'row']
        );

        return $metadata;
    }

    private function document(int $id): DocumentInterface
    {
        $doc = $this->createStub(DocumentInterface::class);
        $doc->method('getId')->willReturn($id);

        return $doc;
    }

    public function testCsvExportWritesHeaderAndPagedRows(): void
    {
        $converter = new ConvertToCsv($this->filesystem(), $this->filter(150, [$this->document(1), $this->document(2)]), $this->metadata());

        $result = $converter->getCsvFile('ignored');

        $this->assertSame('filename', $result['type']);
        $this->assertTrue($result['rm']);
        $this->assertMatchesRegularExpression('#^export/panth_menu_listing[0-9a-f]{64}\.csv$#', $result['value']);
        $this->assertSame($result['value'], $this->openedFile);
        $this->assertSame(['export'], $this->createdDirs);
        $this->assertSame([['ID', 'Title'], [1, 'row'], [2, 'row'], [1, 'row'], [2, 'row']], $this->csvRows);
        $this->assertSame([1, 2, 3], $this->pages);
    }

    public function testCsvExportWithNoRowsOnlyWritesHeader(): void
    {
        (new ConvertToCsv($this->filesystem(), $this->filter(0, []), $this->metadata()))->getCsvFile('x');

        $this->assertSame([['ID', 'Title']], $this->csvRows);
    }

    public function testXmlExportStreamsRowsThroughExcelConverter(): void
    {
        $captured = null;
        $excel = $this->createStub(Excel::class);
        $excel->method('convert')->willReturn('<xml/>');
        $excelFactory = $this->createStub(ExcelFactory::class);
        $excelFactory->method('create')->willReturnCallback(function (array $args) use (&$captured, $excel) {
            $captured = $args;
            return $excel;
        });
        $metadata = $this->metadata();
        $converter = new ConvertToXml($this->filesystem(), $this->filter(1, [$this->document(5)]), $metadata, $excelFactory);

        $result = $converter->getXmlFile('ignored');

        $this->assertMatchesRegularExpression('#^export/panth_menu_listing[0-9a-f]{64}\.xml$#', $result['value']);
        $this->assertSame('<xml/>', $this->written);
        $this->assertSame([[5, 'row']], iterator_to_array($captured['iterator'], false));
        $this->assertSame([$converter, 'getRowCallback'], $captured['rowCallback']);
        $this->assertSame([$metadata, 'getHeaders'], $converter->getRowCallback());
    }
}
