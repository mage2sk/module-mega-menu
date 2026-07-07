<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion\CollectionFactory;

class MenuVersionDataProvider extends DataProvider
{
    protected $collectionFactory;

    protected $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
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
        $this->request = $request;
    }

    public function getData()
    {
        try {
            $collection = $this->collectionFactory->create();

            $menuId = $this->request->getParam('menu_id');
            if (!$menuId) {
                return [
                    'totalRecords' => 0,
                    'items' => []
                ];
            }

            $collection->addFieldToFilter('menu_id', $menuId);

            $collection->getSelect()->columns([
                'item_count' => new \Zend_Db_Expr(
                    "CASE
                        WHEN main_table.items_json IS NULL OR main_table.items_json = '' THEN 0
                        WHEN main_table.items_json = '[]' THEN 0
                        ELSE (LENGTH(main_table.items_json) - LENGTH(REPLACE(main_table.items_json, '\"id\":', '')))
                    END"
                )
            ]);

            $collection->setOrder('version_number', 'DESC');

            $this->prepareUpdateUrl();

            $items = $collection->getItems();
            $data = [];

            foreach ($items as $item) {
                $itemData = $item->getData();

                if (isset($itemData['version_comment'])) {
                    $itemData['version_comment_full'] = $itemData['version_comment'];
                }

                $data[] = $itemData;
            }

            return [
                'totalRecords' => $collection->getSize(),
                'items' => $data
            ];
        } catch (\Exception $e) {
            return [
                'totalRecords' => 0,
                'items' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();

            $menuId = $this->request->getParam('menu_id');
            if ($menuId) {
                $this->collection->addFieldToFilter('menu_id', $menuId);
            }
        }

        return $this->collection;
    }
}
