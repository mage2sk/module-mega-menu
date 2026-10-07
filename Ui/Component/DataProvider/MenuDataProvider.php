<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;

class MenuDataProvider extends DataProvider
{
    private CollectionFactory $collectionFactory;

    private ResourceConnection $resourceConnection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getData(): array
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }

        $items = [];
        $connection = $this->resourceConnection->getConnection();
        $itemTable = $this->resourceConnection->getTableName('panth_megamenu_item');

        foreach ($this->getCollection() as $menu) {
            $menuData = $menu->getData();

            $select = $connection->select()
                ->from($itemTable, ['COUNT(*)'])
                ->where('menu_id = ?', $menu->getId());

            $itemCount = (int)$connection->fetchOne($select);
            $menuData['item_count'] = $itemCount;

            $items[] = $menuData;
        }

        $data = [
            'items' => $items,
            'totalRecords' => $this->getCollection()->getSize(),
        ];

        return $data;
    }

    public function getCollection()
    {
        if (!isset($this->_collection)) {
            $this->_collection = $this->collectionFactory->create();
        }

        return $this->_collection;
    }
}
