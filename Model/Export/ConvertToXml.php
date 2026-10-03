<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\Export;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;

class ConvertToXml
{
    protected $directory;

    protected $metadataProvider;

    protected $excelFactory;

    protected $filter;

    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        MetadataProvider $metadataProvider,
        ExcelFactory $excelFactory
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->metadataProvider = $metadataProvider;
        $this->excelFactory = $excelFactory;
    }

    public function getXmlFile(string $component): array
    {
        $component = $this->filter->getComponent();
        $name = hash('sha256', microtime());
        $file = 'export/' . $component->getName() . $name . '.xml';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $excel = $this->excelFactory->create([
            'iterator' => $this->getDataIterator($component),
            'rowCallback' => [$this, 'getRowCallback'],
        ]);

        $stream->write($excel->convert('single'));
        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }

    protected function getDataIterator($component)
    {
        $dataProvider = $component->getContext()->getDataProvider();
        $fields = $this->metadataProvider->getFields($component);
        $options = $this->metadataProvider->getOptions();

        $i = 1;
        $searchCriteria = $dataProvider->getSearchCriteria()
            ->setCurrentPage($i)
            ->setPageSize(100);

        $totalCount = (int) $dataProvider->getSearchResult()->getTotalCount();

        while ($totalCount > 0) {
            $items = $dataProvider->getSearchResult()->getItems();

            foreach ($items as $item) {
                $this->metadataProvider->convertDate($item, $component->getName());
                yield $this->metadataProvider->getRowData($item, $fields, $options);
            }

            $searchCriteria->setCurrentPage(++$i);
            $totalCount = $totalCount - 100;
        }
    }

    public function getRowCallback(): array
    {
        return [$this->metadataProvider, 'getHeaders'];
    }
}
