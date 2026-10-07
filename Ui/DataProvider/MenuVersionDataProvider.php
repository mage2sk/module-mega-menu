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

            $collection->setOrder('version_number', 'DESC');

            $this->prepareUpdateUrl();

            $items = $collection->getItems();
            $data = [];

            foreach ($items as $item) {
                $itemData = $item->getData();
                $itemData['item_count'] = $this->countItems($itemData['items_json'] ?? null);

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

    private function countItems($itemsJson): int
    {
        if (!is_string($itemsJson) || $itemsJson === '') {
            return 0;
        }

        $decoded = json_decode($itemsJson, true);

        return is_array($decoded) ? count(array_filter($decoded, 'is_array')) : 0;
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
