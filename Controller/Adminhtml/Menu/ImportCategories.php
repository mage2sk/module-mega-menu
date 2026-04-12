<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ImportCategories extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $categoryCollectionFactory;
    protected $storeManager;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);

        try {
            $maxLevel = $this->getRequest()->getParam('max_level', 0); // 0 = all levels

            // Get category URL suffix from config
            $categoryUrlSuffix = $this->scopeConfig->getValue(
                'catalog/seo/category_url_suffix',
                ScopeInterface::SCOPE_STORE
            );

            // Get all active categories
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'url_key', 'url_path', 'level', 'parent_id', 'position'])
                ->addIsActiveFilter()
                ->addOrderField('level')
                ->addOrderField('position');

            // Build category tree structure
            $categoryData = [];
            $categoryChildren = [];

            foreach ($collection as $category) {
                // Skip root and default category (level 0 and 1)
                if ($category->getLevel() <= 1) {
                    continue;
                }

                $catId = $category->getId();
                $parentId = $category->getParentId();
                $level = (int)$category->getLevel() - 2;

                $categoryData[$catId] = [
                    'id' => $catId,
                    'parent_id' => $parentId,
                    'level' => $level,
                    'position' => $category->getPosition(),
                    'name' => $category->getName(),
                    'url_path' => $category->getUrlPath(),
                    'url_key' => $category->getUrlKey()
                ];

                // Group children by parent
                if (!isset($categoryChildren[$parentId])) {
                    $categoryChildren[$parentId] = [];
                }
                $categoryChildren[$parentId][] = $catId;
            }

            // Determine root categories to import
            $rootCategories = [];
            if ($maxLevel > 0) {
                // Import categories at specific level as roots
                foreach ($categoryData as $catId => $cat) {
                    if ($cat['level'] == ($maxLevel - 1)) {
                        $rootCategories[] = $catId;
                    }
                }
            } else {
                // Import all top-level categories (level 0)
                foreach ($categoryData as $catId => $cat) {
                    if ($cat['level'] == 0) {
                        $rootCategories[] = $catId;
                    }
                }
            }

            // Sort roots by position
            usort($rootCategories, function($a, $b) use ($categoryData) {
                return $categoryData[$a]['position'] - $categoryData[$b]['position'];
            });

            // Build items array recursively to maintain parent-child order
            $items = [];
            $position = 0;

            $buildTree = function($parentCatId, $parentTempId, $currentLevel) use (
                &$buildTree, &$items, &$position, $categoryData, $categoryChildren, $categoryUrlSuffix
            ) {
                if (!isset($categoryChildren[$parentCatId])) {
                    return;
                }

                // Sort children by position
                $children = $categoryChildren[$parentCatId];
                usort($children, function($a, $b) use ($categoryData) {
                    return $categoryData[$a]['position'] - $categoryData[$b]['position'];
                });

                foreach ($children as $catId) {
                    $cat = $categoryData[$catId];
                    $tempId = 'cat_' . $catId;

                    // Build relative URL with suffix from config
                    $relativeUrl = '';
                    if ($cat['url_path']) {
                        $relativeUrl = '/' . ltrim($cat['url_path'], '/');
                    } elseif ($cat['url_key']) {
                        $relativeUrl = '/' . ltrim($cat['url_key'], '/');
                    }

                    // Append category URL suffix if configured
                    if ($relativeUrl && $categoryUrlSuffix) {
                        $relativeUrl .= $categoryUrlSuffix;
                    }

                    $items[] = [
                        'item_id' => null,
                        'temp_id' => $tempId,
                        'menu_id' => null,
                        'title' => $cat['name'],
                        'url' => $relativeUrl,
                        'item_type' => 'category',
                        'category_id' => $catId,
                        'parent_id' => $parentTempId,
                        'position' => $position++,
                        'level' => $currentLevel,
                        'is_active' => 1,
                        'show_on_frontend' => 1,
                        'target' => '_self',
                        'css_class' => '',
                        'icon' => '',
                        'icon_library' => 'fontawesome',
                        'hover_effect' => 'fade'
                    ];

                    // Recursively process children
                    $buildTree($catId, $tempId, $currentLevel + 1);
                }
            };

            // Process each root category and its descendants
            foreach ($rootCategories as $rootCatId) {
                $cat = $categoryData[$rootCatId];
                $tempId = 'cat_' . $rootCatId;

                // Build relative URL with suffix from config
                $relativeUrl = '';
                if ($cat['url_path']) {
                    $relativeUrl = '/' . ltrim($cat['url_path'], '/');
                } elseif ($cat['url_key']) {
                    $relativeUrl = '/' . ltrim($cat['url_key'], '/');
                }

                // Append category URL suffix if configured
                if ($relativeUrl && $categoryUrlSuffix) {
                    $relativeUrl .= $categoryUrlSuffix;
                }

                $items[] = [
                    'item_id' => null,
                    'temp_id' => $tempId,
                    'menu_id' => null,
                    'title' => $cat['name'],
                    'url' => $relativeUrl,
                    'item_type' => 'category',
                    'category_id' => $rootCatId,
                    'parent_id' => null,
                    'position' => $position++,
                    'level' => 0,
                    'is_active' => 1,
                    'show_on_frontend' => 1,
                    'target' => '_self',
                    'css_class' => '',
                    'icon' => '',
                    'icon_library' => 'fontawesome',
                    'hover_effect' => 'fade'
                ];

                // Recursively build children
                $buildTree($rootCatId, $tempId, 1);
            }

            return $result->setData([
                'success' => true,
                'items' => $items,
                'count' => count($items),
                'message' => 'Successfully imported ' . count($items) . ' categories'
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error importing categories: ' . $e->getMessage()
            ]);
        }
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
