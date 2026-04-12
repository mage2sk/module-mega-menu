<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class GetCategories extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $categoryCollectionFactory;
    protected $productCollectionFactory;
    protected $pageCollectionFactory;
    protected $blockCollectionFactory;
    protected $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        PageCollectionFactory $pageCollectionFactory,
        BlockCollectionFactory $blockCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);

        $result = $this->jsonFactory->create();
        $type = $this->getRequest()->getParam('type', 'category');
        $search = trim($this->getRequest()->getParam('search', ''));
        $page = (int)$this->getRequest()->getParam('page', 1);
        $pageSize = (int)$this->getRequest()->getParam('pageSize', 50);

        try {
            $data = [];

            if ($type === 'all') {
                $categoriesData = $this->getCategories($search, $page, $pageSize);
                $pagesData = $this->getCmsPages($search, $page, $pageSize);
                $productsData = $this->getProducts($search, $page, $pageSize);

                $data['categories'] = $categoriesData['items'];
                $data['pages'] = $pagesData['items'];
                $data['products'] = $productsData['items'];
                $data['total_count'] = $categoriesData['total_count'] + $pagesData['total_count'] + $productsData['total_count'];
            } elseif ($type === 'category') {
                $categoriesData = $this->getCategories($search, $page, $pageSize);
                $data['categories'] = $categoriesData['items'];
                $data['total_count'] = $categoriesData['total_count'];
            } elseif ($type === 'cms_page') {
                $pagesData = $this->getCmsPages($search, $page, $pageSize);
                $data['pages'] = $pagesData['items'];
                $data['total_count'] = $pagesData['total_count'];
            } elseif ($type === 'cms_block') {
                $blocksData = $this->getCmsBlocks($search, $page, $pageSize);
                $data['blocks'] = $blocksData['items'];
                $data['total_count'] = $blocksData['total_count'];
            } elseif ($type === 'product') {
                $productsData = $this->getProducts($search, $page, $pageSize);
                $data['products'] = $productsData['items'];
                $data['total_count'] = $productsData['total_count'];
            } else {
                return $result->setData(['error' => true, 'message' => 'Invalid type: ' . $type]);
            }

            if (!isset($data['total_count'])) {
                $data['total_count'] = 0;
            }

            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function getCategories($search = '', $page = 1, $pageSize = 50)
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect([
                'name',
                'url_key',
                'url_path',
                'level',
                'parent_id',
                'children_count',
                'include_in_menu',
                'is_active',
                'position',
                'description',
                'image',
                'meta_title',
                'meta_description'
            ])
            // REMOVED addIsActiveFilter() to import ALL categories (active and inactive)
            ->addAttributeToFilter('level', ['gt' => 1]) // Filter out root and default category
            ->addOrderField('level')
            ->addOrderField('position');

        // Add search filter if provided
        if (!empty($search)) {
            $collection->addAttributeToFilter('name', ['like' => '%' . $search . '%']);
        }

        // Get total count before pagination
        $totalCount = $collection->getSize();

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        $categories = [];

        foreach ($collection as $category) {
            $adjustedLevel = (int)$category->getLevel() - 2;

            // Use Magento's proper URL generation with rewrites and .html suffix
            $categoryUrl = $category->getUrl();

            // Convert to relative URL by removing base URL
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();
            if (strpos($categoryUrl, $storeUrl) === 0) {
                $categoryUrl = '/' . ltrim(substr($categoryUrl, strlen($storeUrl)), '/');
            }

            // Get category image URL if available
            $categoryImage = '';
            if ($category->getImage()) {
                $categoryImage = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/category/' . $category->getImage();
            }

            // Get include_in_menu value - default to 1 if not set
            $includeInMenu = $category->getData('include_in_menu');
            if ($includeInMenu === null) {
                $includeInMenu = 1; // Default to enabled if not set
            }

            $categoryData = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'url' => $categoryUrl,
                'level' => $adjustedLevel,
                'parent_id' => $category->getParentId(),
                'position' => (int)$category->getPosition(),
                'include_in_menu' => (int)$includeInMenu,
                'is_active' => (int)$category->getIsActive(),
                'description' => $category->getDescription() ?: '',
                'image' => $categoryImage,
                'children_count' => (int)$category->getChildrenCount()
            ];

            $categories[] = $categoryData;
        }

        return [
            'items' => $categories,
            'total_count' => $totalCount
        ];
    }

    protected function buildCategoryTree($categories)
    {
        $tree = [];
        $lookup = [];

        // First pass: index by ID
        foreach ($categories as $category) {
            $lookup[$category['id']] = $category;
            $lookup[$category['id']]['children'] = [];
        }

        // Second pass: build tree
        foreach ($lookup as $id => $category) {
            if ($category['parent_id'] && isset($lookup[$category['parent_id']])) {
                $lookup[$category['parent_id']]['children'][] = &$lookup[$id];
            } else {
                $tree[] = &$lookup[$id];
            }
        }

        return $tree;
    }

    protected function getCmsPages($search = '', $page = 1, $pageSize = 50)
    {
        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        // Add search filter if provided
        if (!empty($search)) {
            $collection->addFieldToFilter(
                ['title', 'identifier'],
                [
                    ['like' => '%' . $search . '%'],
                    ['like' => '%' . $search . '%']
                ]
            );
        }

        // Get total count before pagination
        $totalCount = $collection->getSize();

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        $pages = [];
        foreach ($collection as $pageItem) {
            $pages[] = [
                'id' => $pageItem->getId(),
                'title' => $pageItem->getTitle(),
                'identifier' => $pageItem->getIdentifier()
            ];
        }

        return [
            'items' => $pages,
            'total_count' => $totalCount
        ];
    }

    protected function getCmsBlocks($search = '', $page = 1, $pageSize = 50)
    {
        $collection = $this->blockCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);

        // Add search filter if provided
        if (!empty($search)) {
            $collection->addFieldToFilter(
                ['title', 'identifier'],
                [
                    ['like' => '%' . $search . '%'],
                    ['like' => '%' . $search . '%']
                ]
            );
        }

        // Get total count before pagination
        $totalCount = $collection->getSize();

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        $blocks = [];
        foreach ($collection as $block) {
            $blocks[] = [
                'id' => $block->getId(),
                'title' => $block->getTitle(),
                'identifier' => $block->getIdentifier()
            ];
        }

        return [
            'items' => $blocks,
            'total_count' => $totalCount
        ];
    }

    protected function getProducts($search = '', $page = 1, $pageSize = 50)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'url_key'])
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]);

        // Add search filter if provided
        if (!empty($search)) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'name', 'like' => '%' . $search . '%'],
                    ['attribute' => 'sku', 'like' => '%' . $search . '%']
                ]
            );
        }

        // Get total count before pagination
        $totalCount = $collection->getSize();

        // Apply pagination
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        $products = [];
        foreach ($collection as $product) {
            // Get product URL
            $productUrl = $product->getProductUrl();

            // Convert to relative URL by removing base URL
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();
            if (strpos($productUrl, $storeUrl) === 0) {
                $productUrl = '/' . ltrim(substr($productUrl, strlen($storeUrl)), '/');
            }

            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'url' => $productUrl
            ];
        }

        return [
            'items' => $products,
            'total_count' => $totalCount
        ];
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
