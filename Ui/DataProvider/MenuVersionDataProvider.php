<?php
/**
 * Menu Version DataProvider for UI Component
 * Provides version history data for the version listing grid
 */
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
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
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

    /**
     * Get data for the grid
     *
     * @return array
     */
    public function getData()
    {
        try {
            $collection = $this->collectionFactory->create();

            // Filter by menu_id if provided in request
            $menuId = $this->request->getParam('menu_id');
            if (!$menuId) {
                // Return empty result if no menu_id provided
                return [
                    'totalRecords' => 0,
                    'items' => []
                ];
            }

            $collection->addFieldToFilter('menu_id', $menuId);

            // Add item count calculation from items_json
            // Note: created_by already stores username directly, no join needed
            $collection->getSelect()->columns([
                'item_count' => new \Zend_Db_Expr(
                    "CASE
                        WHEN main_table.items_json IS NULL OR main_table.items_json = '' THEN 0
                        WHEN main_table.items_json = '[]' THEN 0
                        ELSE (LENGTH(main_table.items_json) - LENGTH(REPLACE(main_table.items_json, '\"id\":', '')))
                    END"
                )
            ]);

            // Order by version number descending
            $collection->setOrder('version_number', 'DESC');

            // Apply search criteria filters
            $this->prepareUpdateUrl();

            $items = $collection->getItems();
            $data = [];

            foreach ($items as $item) {
                $itemData = $item->getData();

                // Truncate version_comment for preview (will be handled by TruncatedText component)
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
            // Log error and return empty result
            return [
                'totalRecords' => 0,
                'items' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get collection
     *
     * @return \Panth\MegaMenu\Model\ResourceModel\MenuVersion\Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();

            // Filter by menu_id if provided
            $menuId = $this->request->getParam('menu_id');
            if ($menuId) {
                $this->collection->addFieldToFilter('menu_id', $menuId);
            }
        }

        return $this->collection;
    }
}
