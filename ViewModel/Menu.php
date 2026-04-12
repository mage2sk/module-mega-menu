<?php
/**
 * Enhanced Menu ViewModel - Theme-Agnostic Data Provider
 *
 * Provides menu data and item management for both Hyva (Alpine.js) and Luma (KnockoutJS) themes.
 * This ViewModel serves as the primary data interface for menu rendering in templates.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Psr\Log\LoggerInterface;

class Menu implements ArgumentInterface
{
    /**
     * @var MenuRepositoryInterface
     */
    private $menuRepository;

    /**
     * @var ItemRepositoryInterface
     */
    private $itemRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CategoryHelper
     */
    private $categoryHelper;

    /**
     * @var PageHelper
     */
    private $pageHelper;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var MenuHelper
     */
    private $menuHelper;

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Cms\Block\BlockFactory
     */
    private $cmsBlockFactory;

    /**
     * @var \Panth\MegaMenu\Helper\MenuRenderer
     */
    private $menuRenderer;

    /**
     * @var array
     */
    private $categoryCache = [];

    /**
     * @var array
     */
    private $pageCache = [];

    /**
     * @var array
     */
    private $menuTreeCache = [];

    /**
     * @var MenuInterface|null
     */
    private $currentMenu = null;

    /**
     * Constructor
     *
     * @param MenuRepositoryInterface $menuRepository
     * @param ItemRepositoryInterface $itemRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param PageRepositoryInterface $pageRepository
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param CategoryHelper $categoryHelper
     * @param PageHelper $pageHelper
     * @param FilterProvider $filterProvider
     * @param MenuHelper $menuHelper
     * @param AssetRepository $assetRepository
     * @param DateTime $dateTime
     * @param Json $jsonSerializer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param \Magento\Cms\Block\BlockFactory $cmsBlockFactory
     * @param \Panth\MegaMenu\Helper\MenuRenderer $menuRenderer
     */
    public function __construct(
        MenuRepositoryInterface $menuRepository,
        ItemRepositoryInterface $itemRepository,
        CategoryRepositoryInterface $categoryRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductRepositoryInterface $productRepository,
        PageRepositoryInterface $pageRepository,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        CategoryHelper $categoryHelper,
        PageHelper $pageHelper,
        FilterProvider $filterProvider,
        MenuHelper $menuHelper,
        AssetRepository $assetRepository,
        DateTime $dateTime,
        Json $jsonSerializer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger,
        \Magento\Cms\Block\BlockFactory $cmsBlockFactory,
        \Panth\MegaMenu\Helper\MenuRenderer $menuRenderer
    ) {
        $this->menuRepository = $menuRepository;
        $this->itemRepository = $itemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productRepository = $productRepository;
        $this->pageRepository = $pageRepository;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->categoryHelper = $categoryHelper;
        $this->pageHelper = $pageHelper;
        $this->filterProvider = $filterProvider;
        $this->menuHelper = $menuHelper;
        $this->assetRepository = $assetRepository;
        $this->dateTime = $dateTime;
        $this->jsonSerializer = $jsonSerializer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->cmsBlockFactory = $cmsBlockFactory;
        $this->menuRenderer = $menuRenderer;
    }

    /**
     * Helper method to get value from item (handles both array and object)
     *
     * @param ItemInterface|array $item
     * @param string $key Array key
     * @param string|null $method Object method name
     * @param mixed $default Default value
     * @return mixed
     */
    private function getItemValue($item, string $key, ?string $method = null, $default = null)
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }
        return $method && method_exists($item, $method) ? $item->$method() : $default;
    }

    /**
     * Get menu data as array
     *
     * @param MenuInterface $menu
     * @return array
     */
    public function getMenuData(MenuInterface $menu): array
    {
        return [
            'menu_id' => $menu->getMenuId(),
            'identifier' => $menu->getIdentifier(),
            'title' => $menu->getTitle(),
            'is_active' => $menu->getIsActive(),
            'store_ids' => $menu->getStoreIds(),
            'created_at' => $menu->getCreatedAt(),
            'updated_at' => $menu->getUpdatedAt()
        ];
    }

    /**
     * Get menu items as hierarchical tree
     *
     * @param string|null $menuIdentifier
     * @return array
     */
    public function getMenuItems(?string $menuIdentifier = null): array
    {
        try {
            $menu = $this->getMenu($menuIdentifier);

            if (!$menu || !$menu->getIsActive()) {
                return [];
            }

            $menuId = $menu->getMenuId();

            // Check cache
            if (isset($this->menuTreeCache[$menuId])) {
                return $this->menuTreeCache[$menuId];
            }

            // Get menu tree
            $items = $this->itemRepository->getMenuTree($menuId);

            // Filter visible items
            $visibleItems = $this->filterVisibleItems($items);

            // Cache result
            $this->menuTreeCache[$menuId] = $visibleItems;

            return $visibleItems;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get menu by identifier
     *
     * @param string|null $identifier
     * @return MenuInterface|null
     */
    public function getMenu(?string $identifier = null): ?MenuInterface
    {
        if ($this->currentMenu !== null && ($identifier === null || $this->currentMenu->getIdentifier() === $identifier)) {
            return $this->currentMenu;
        }

        try {
            if ($identifier === null) {
                $identifier = $this->menuHelper->getConfigValue('panth_megamenu/general/default_menu_identifier') ?: 'pmenu';
            }

            $storeId = (int)$this->storeManager->getStore()->getId();
            $this->currentMenu = $this->menuRepository->getByIdentifier($identifier, $storeId);

            return $this->currentMenu;
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get item URL
     *
     * @param ItemInterface|array $item
     * @return string
     */
    public function getItemUrl($item): string
    {
        $isActive = $this->getItemValue($item, 'is_active', 'getIsActive', true);
        if (!$isActive) {
            return '#';
        }

        // First, try to get URL directly from the item
        $url = $this->getItemValue($item, 'url', 'getUrl', '');

        // If URL is valid and not just '#', return it
        if (!empty($url) && $url !== '#') {
            return $url;
        }

        // If URL is '#' or empty, try to generate based on item type
        $itemType = $this->getItemValue($item, 'item_type', 'getItemType', 'link');

        try {
            switch ($itemType) {
                case 'category':
                    $categoryId = $this->getItemValue($item, 'category_id', 'getCategoryId');
                    if ($categoryId) {
                        return $this->getCategoryUrl((int)$categoryId);
                    }
                    break;

                case 'cms_page':
                    $cmsPageId = $this->getItemValue($item, 'cms_page_id', 'getCmsPageId');
                    if ($cmsPageId) {
                        return $this->getCmsPageUrl($cmsPageId);
                    }
                    break;

                case 'product':
                    $productId = $this->getItemValue($item, 'product_id', 'getProductId');
                    if ($productId) {
                        return $this->getProductUrl((int)$productId);
                    }
                    break;

                case 'link':
                case 'dropdown':
                case 'custom_html':
                case 'cms_block':
                case 'widget':
                default:
                    // For dropdown, custom_html, cms_block, widget - return # (not clickable)
                    // For link - should have URL already
                    return '#';
            }
        } catch (\Exception $e) {
            // Silently handle errors
        }

        return '#';
    }

    /**
     * Check if item has children
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function hasChildren($item): bool
    {
        if (is_array($item)) {
            return !empty($item['children']);
        }
        return $item->hasChildren() && !empty($this->getChildren($item));
    }

    /**
     * Get item children
     *
     * @param ItemInterface $item
     * @return array
     */
    public function getChildren($item): array
    {
        if (is_array($item)) {
            $children = $item['children'] ?? [];
            return is_array($children) ? $children : [];
        }

        if (!$item->hasChildren()) {
            return [];
        }

        return $this->filterVisibleItems($item->getChildren());
    }

    /**
     * Render item content (process CMS content, widgets, etc.)
     *
     * @param ItemInterface $item
     * @return string
     */
    public function renderItemContent($item): string
    {
        $content = $this->getItemValue($item, 'content', 'getContent', '');

        if (!$content) {
            return '';
        }

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $filter = $this->filterProvider->getPageFilter();
            $filter->setStoreId($storeId);

            return $filter->filter($content);
        } catch (\Exception $e) {
            return $content;
        }
    }

    /**
     * Process item content (with CMS block support)
     *
     * @param mixed $item
     * @return string
     */
    public function processItemContent($item): string
    {
        $cmsBlock = $this->getItemValue($item, 'cms_block', 'getCmsBlock', '');

        // If CMS block is specified, load and render it
        if ($cmsBlock) {
            try {
                $blockHtml = '';
                $cmsBlockInstance = $this->cmsBlockFactory->create();
                $cmsBlockInstance->setBlockId($cmsBlock);
                $blockHtml = $cmsBlockInstance->toHtml();

                if ($blockHtml) {
                    return $blockHtml;
                }
            } catch (\Exception $e) {
                // Silently handle errors
            }
        }

        // Otherwise, return custom content
        $content = $this->getItemValue($item, 'custom_content', 'getCustomContent', '');

        if (!$content) {
            $content = $this->getItemValue($item, 'content', 'getContent', '');
        }

        if (!$content) {
            return '';
        }

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $filter = $this->filterProvider->getPageFilter();
            $filter->setStoreId($storeId);

            return $filter->filter($content);
        } catch (\Exception $e) {
            return $content;
        }
    }

    /**
     * Get image URL for item
     *
     * @param ItemInterface $item
     * @param string $type
     * @return string
     */
    public function getImageUrl(ItemInterface $item, string $type = 'thumbnail'): string
    {
        try {
            // Check if item has custom image in content
            $content = $item->getContent();
            if ($content && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
                return $matches[1];
            }

            // For category items, get category image
            if ($item->getLinkType() === ItemInterface::LINK_CATEGORY && $item->getLinkValue()) {
                return $this->getCategoryImageUrl((int)$item->getLinkValue(), $type);
            }

            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if item is visible (based on dates, customer groups, etc.)
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function isItemVisible($item): bool
    {
        // Check if active
        $isActive = $this->getItemValue($item, 'is_active', 'getIsActive', true);
        if (!$isActive) {
            return false;
        }

        // Check max depth
        $maxDepth = $this->menuHelper->getMaxDepth();
        $level = $this->getItemValue($item, 'level', 'getLevel', 0);
        if ($maxDepth > 0 && $level >= $maxDepth) {
            return false;
        }

        // Check schedule visibility (start/end dates)
        if (!$this->isVisibleBySchedule($item)) {
            return false;
        }

        // Check store view visibility
        if (!$this->isVisibleInCurrentStore($item)) {
            return false;
        }

        // Check customer group restrictions
        if (!$this->isVisibleForCustomerGroup($item)) {
            return false;
        }

        // Check device visibility (if all devices are hidden, item is not visible)
        if (!$this->isVisibleOnCurrentDevice($item)) {
            return false;
        }

        return true;
    }

    /**
     * Check if item is visible based on schedule (start/end dates)
     *
     * @param mixed $item
     * @return bool
     */
    private function isVisibleBySchedule($item): bool
    {
        $startDate = $this->getItemValue($item, 'start_date', 'getStartDate', null);
        $endDate = $this->getItemValue($item, 'end_date', 'getEndDate', null);

        if (!$startDate && !$endDate) {
            return true; // No schedule restrictions
        }

        $currentTimestamp = $this->dateTime->gmtTimestamp();

        // Check start date
        if ($startDate) {
            $startTimestamp = strtotime($startDate);
            if ($currentTimestamp < $startTimestamp) {
                return false; // Not yet visible
            }
        }

        // Check end date
        if ($endDate) {
            $endTimestamp = strtotime($endDate . ' 23:59:59');
            if ($currentTimestamp > $endTimestamp) {
                return false; // No longer visible
            }
        }

        return true;
    }

    /**
     * Check if item is visible in current store view
     *
     * @param mixed $item
     * @return bool
     */
    private function isVisibleInCurrentStore($item): bool
    {
        $storeIds = $this->getItemValue($item, 'store_ids', 'getStoreIds', null);

        if (empty($storeIds)) {
            return true; // Visible in all stores
        }

        // Convert to array if it's a string
        if (is_string($storeIds)) {
            $storeIds = explode(',', $storeIds);
        }

        $currentStoreId = $this->storeManager->getStore()->getId();

        // Check if current store or "all stores" (0) is in the list
        return in_array($currentStoreId, $storeIds) || in_array(0, $storeIds);
    }

    /**
     * Check if item is visible for current customer group
     *
     * @param mixed $item
     * @return bool
     */
    private function isVisibleForCustomerGroup($item): bool
    {
        $customerGroupIds = $this->getItemValue($item, 'customer_group_ids', 'getCustomerGroupIds', null);

        if (empty($customerGroupIds)) {
            return true; // Visible for all customer groups
        }

        // Convert to array if it's a string
        if (is_string($customerGroupIds)) {
            $customerGroupIds = explode(',', $customerGroupIds);
        }

        $currentCustomerGroupId = $this->customerSession->getCustomerGroupId();

        return in_array($currentCustomerGroupId, $customerGroupIds);
    }

    /**
     * Get menu as JSON for JavaScript initialization
     *
     * @param string|null $menuIdentifier
     * @return string
     */
    public function getMenuJson(?string $menuIdentifier = null): string
    {
        try {
            $menu = $this->getMenu($menuIdentifier);

            if (!$menu) {
                return '{}';
            }

            $items = $this->getMenuItems($menuIdentifier);

            $data = [
                'menu' => $this->getMenuData($menu),
                'items' => $this->convertItemsToArray($items)
            ];

            return $this->jsonSerializer->serialize($data);
        } catch (\Exception $e) {
            return '{}';
        }
    }

    /**
     * Get item CSS class
     *
     * @param ItemInterface $item
     * @param string $additionalClasses
     * @return string
     */
    public function getItemClass(ItemInterface $item, string $additionalClasses = ''): string
    {
        $classes = $this->menuHelper->getItemClasses($item);

        if ($additionalClasses) {
            $classes .= ' ' . $additionalClasses;
        }

        return $classes;
    }

    /**
     * Check if item is active/current
     *
     * @param ItemInterface $item
     * @param string|null $currentUrl
     * @return bool
     */
    public function isActive(ItemInterface $item, ?string $currentUrl = null): bool
    {
        if (!$item->getIsActive()) {
            return false;
        }

        if ($currentUrl === null) {
            $currentUrl = $this->urlBuilder->getCurrentUrl();
        }

        $itemUrl = $this->getItemUrl($item);

        if ($itemUrl === '#') {
            return false;
        }

        // Normalize URLs for comparison
        $currentUrl = rtrim($currentUrl, '/');
        $itemUrl = rtrim($itemUrl, '/');

        return $currentUrl === $itemUrl;
    }

    /**
     * Get link target attribute
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getLinkTarget($item): string
    {
        $openNewTab = $this->getItemValue($item, 'open_new_tab', 'getOpenNewTab', false);
        return $openNewTab ? '_blank' : '_self';
    }

    /**
     * Get link rel attribute
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getLinkRel($item): string
    {
        $openNewTab = $this->getItemValue($item, 'open_new_tab', 'getOpenNewTab', false);
        if ($openNewTab) {
            return 'noopener noreferrer';
        }

        return '';
    }

    /**
     * Get column width class based on columns count
     *
     * @param ItemInterface $item
     * @return string
     */
    public function getColumnWidthClass($item): string
    {
        $columns = $this->getItemValue($item, 'columns', 'getColumns', 1);

        if ($columns <= 0) {
            $columns = 1;
        }

        // Calculate Tailwind grid column span
        $gridMap = [
            1 => 'col-span-12',
            2 => 'col-span-6',
            3 => 'col-span-4',
            4 => 'col-span-3',
            6 => 'col-span-2',
            12 => 'col-span-1'
        ];

        return $gridMap[$columns] ?? 'col-span-12';
    }

    /**
     * Check if item should show content
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function shouldShowContent($item): bool
    {
        $itemType = $this->getItemValue($item, 'item_type', 'getItemType');
        $content = $this->getItemValue($item, 'content', 'getContent');
        return $itemType === ItemInterface::TYPE_CONTENT
            && !empty($content);
    }

    /**
     * Get item depth level
     *
     * @param ItemInterface $item
     * @return int
     */
    public function getItemDepth($item): int
    {
        return $this->getItemValue($item, 'level', 'getLevel', 0);
    }

    /**
     * Check if item is top level
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function isTopLevel($item): bool
    {
        $level = $this->getItemValue($item, 'level', 'getLevel', 0);
        $parentId = $this->getItemValue($item, 'parent_id', 'getParentId');
        return $level === 0 || $parentId === null;
    }

    /**
     * Get breadcrumb trail for item
     *
     * @param ItemInterface $item
     * @param array $allItems
     * @return array
     */
    public function getBreadcrumbTrail(ItemInterface $item, array $allItems): array
    {
        $trail = [$item];
        $parentId = $item->getParentId();

        while ($parentId !== null) {
            $parent = $this->findItemById($parentId, $allItems);
            if (!$parent) {
                break;
            }
            array_unshift($trail, $parent);
            $parentId = $parent->getParentId();
        }

        return $trail;
    }

    /**
     * Get category URL
     *
     * @param int $categoryId
     * @return string
     */
    private function getCategoryUrl(int $categoryId): string
    {
        if (isset($this->categoryCache[$categoryId]['url'])) {
            return $this->categoryCache[$categoryId]['url'];
        }

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $category = $this->categoryRepository->get($categoryId, $storeId);

            if (!$category->getIsActive()) {
                return '#';
            }

            $url = $this->categoryHelper->getCategoryUrl($category);

            if (!isset($this->categoryCache[$categoryId])) {
                $this->categoryCache[$categoryId] = [];
            }
            $this->categoryCache[$categoryId]['url'] = $url;

            return $url;
        } catch (NoSuchEntityException $e) {
            return '#';
        }
    }

    /**
     * Get category image URL
     *
     * @param int $categoryId
     * @param string $type
     * @return string
     */
    private function getCategoryImageUrl(int $categoryId, string $type = 'thumbnail'): string
    {
        try {
            if (isset($this->categoryCache[$categoryId]['image'])) {
                return $this->categoryCache[$categoryId]['image'];
            }

            $storeId = $this->storeManager->getStore()->getId();
            $category = $this->categoryRepository->get($categoryId, $storeId);

            $imageUrl = '';
            if ($category->getImageUrl()) {
                $imageUrl = $category->getImageUrl();
            }

            if (!isset($this->categoryCache[$categoryId])) {
                $this->categoryCache[$categoryId] = [];
            }
            $this->categoryCache[$categoryId]['image'] = $imageUrl;

            return $imageUrl;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get CMS page URL
     *
     * @param string $pageIdentifier
     * @return string
     */
    private function getCmsPageUrl(string $pageIdentifier): string
    {
        if (isset($this->pageCache[$pageIdentifier])) {
            return $this->pageCache[$pageIdentifier];
        }

        try {
            $url = $this->pageHelper->getPageUrl($pageIdentifier);
            $this->pageCache[$pageIdentifier] = $url ?: '#';

            return $this->pageCache[$pageIdentifier];
        } catch (\Exception $e) {
            return '#';
        }
    }

    /**
     * Get product URL
     *
     * @param int $productId
     * @return string
     */
    private function getProductUrl(int $productId): string
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $product = $this->productRepository->getById($productId, false, $storeId);

            if (!$product->isVisibleInSiteVisibility()) {
                return '#';
            }

            return $product->getProductUrl();
        } catch (NoSuchEntityException $e) {
            return '#';
        } catch (\Exception $e) {
            return '#';
        }
    }

    /**
     * Get custom URL
     *
     * @param string $url
     * @return string
     */
    private function getCustomUrl(string $url): string
    {
        // If URL starts with http:// or https://, return as is
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // If URL starts with /, treat as absolute path
        if (strpos($url, '/') === 0) {
            return $this->urlBuilder->getBaseUrl() . ltrim($url, '/');
        }

        // Otherwise, build URL relative to base
        return $this->urlBuilder->getUrl($url);
    }

    /**
     * Filter visible items
     *
     * @param array $items
     * @return array
     */
    private function filterVisibleItems(array $items): array
    {
        $visibleItems = [];

        foreach ($items as $item) {
            if ($this->isItemVisible($item)) {
                $visibleItems[] = $item;
            }
        }

        return $visibleItems;
    }

    /**
     * Convert items to array for JSON serialization
     *
     * @param array $items
     * @return array
     */
    private function convertItemsToArray(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $itemData = [
                'item_id' => $item->getItemId(),
                'parent_id' => $item->getParentId(),
                'title' => $item->getTitle(),
                'url' => $this->getItemUrl($item),
                'item_type' => $item->getItemType(),
                'level' => $item->getLevel(),
                'is_active' => $item->getIsActive(),
                'has_children' => $this->hasChildren($item),
                'icon_class' => $item->getIconClass(),
                'css_class' => $item->getCssClass(),
                'open_new_tab' => $item->getOpenNewTab(),
                'columns' => $item->getColumns()
            ];

            if ($this->hasChildren($item)) {
                $itemData['children'] = $this->convertItemsToArray($item->getChildren());
            }

            $result[] = $itemData;
        }

        return $result;
    }

    /**
     * Find item by ID in array
     *
     * @param int $itemId
     * @param array $items
     * @return ItemInterface|null
     */
    private function findItemById(int $itemId, array $items): ?ItemInterface
    {
        foreach ($items as $item) {
            if ($item->getItemId() === $itemId) {
                return $item;
            }

            if ($item->hasChildren()) {
                $found = $this->findItemById($itemId, $item->getChildren());
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Get item title with icon
     *
     * @param mixed $item
     * @return string
     */
    public function getItemTitleWithIcon($item): string
    {
        $title = $this->getItemValue($item, 'title', 'getTitle', '');
        $iconClass = $this->getItemValue($item, 'icon', 'getIcon', '');

        if (!$iconClass) {
            $iconClass = $this->getItemValue($item, 'icon_class', 'getIconClass', '');
        }

        $html = '';

        if ($iconClass) {
            $html .= '<i class="' . $iconClass . ' megamenu-icon" aria-hidden="true"></i> ';
        }

        $html .= $title;

        return $html;
    }

    /**
     * Get badge HTML for item
     *
     * @param mixed $item
     * @return string
     */
    public function getBadgeHtml($item): string
    {
        $badge = $this->getItemValue($item, 'badge', 'getBadge', '');
        $badgeText = $this->getItemValue($item, 'badge_text', 'getBadgeText', '');

        if (!$badge || $badge === 'none') {
            return '';
        }

        $displayText = $badgeText ?: strtoupper($badge);

        return '<span class="pmenu-badge badge-' . $badge . '">' . $displayText . '</span>';
    }

    /**
     * Get inline styles for item
     *
     * @param mixed $item
     * @return string
     */
    public function getItemInlineStyles($item): string
    {
        $styles = [];

        // Background and text colors
        $bgColor = $this->getItemValue($item, 'bg_color', 'getBgColor', '');
        $textColor = $this->getItemValue($item, 'text_color', 'getTextColor', '');

        if ($bgColor) {
            $styles[] = 'background-color:' . $bgColor;
        }
        if ($textColor) {
            $styles[] = 'color:' . $textColor;
        }

        // Typography
        $fontFamily = $this->getItemValue($item, 'font_family', 'getFontFamily', '');
        $fontSize = $this->getItemValue($item, 'font_size', 'getFontSize', '');
        $fontWeight = $this->getItemValue($item, 'font_weight', 'getFontWeight', '');
        $textTransform = $this->getItemValue($item, 'text_transform', 'getTextTransform', '');

        if ($fontFamily && $fontFamily !== 'default') {
            $styles[] = 'font-family:' . $fontFamily;
        }
        if ($fontSize) {
            $styles[] = 'font-size:' . $fontSize;
        }
        if ($fontWeight && $fontWeight !== 'default') {
            $styles[] = 'font-weight:' . $fontWeight;
        }
        if ($textTransform && $textTransform !== 'none') {
            $styles[] = 'text-transform:' . $textTransform;
        }

        // Spacing
        $padding = $this->getItemValue($item, 'padding', 'getPadding', '');
        $margin = $this->getItemValue($item, 'margin', 'getMargin', '');
        $gap = $this->getItemValue($item, 'gap', 'getGap', '');

        if ($padding) {
            $styles[] = 'padding:' . $padding;
        }
        if ($margin) {
            $styles[] = 'margin:' . $margin;
        }
        if ($gap) {
            $styles[] = 'gap:' . $gap;
        }

        // Border and effects
        $borderRadius = $this->getItemValue($item, 'border_radius', 'getBorderRadius', '');
        $boxShadow = $this->getItemValue($item, 'box_shadow', 'getBoxShadow', '');
        $textShadow = $this->getItemValue($item, 'text_shadow', 'getTextShadow', '');
        $opacity = $this->getItemValue($item, 'opacity', 'getOpacity', '');

        if ($borderRadius) {
            $styles[] = 'border-radius:' . $borderRadius;
        }
        if ($boxShadow) {
            $styles[] = 'box-shadow:' . $boxShadow;
        }
        if ($textShadow) {
            $styles[] = 'text-shadow:' . $textShadow;
        }
        if ($opacity && $opacity !== '1') {
            $styles[] = 'opacity:' . $opacity;
        }

        return !empty($styles) ? implode(';', $styles) : '';
    }

    /**
     * Get custom data attributes as array
     *
     * @param mixed $item
     * @return array
     */
    public function getCustomDataAttributes($item): array
    {
        $customDataAttributes = $this->getItemValue($item, 'custom_data_attributes', 'getCustomDataAttributes', '');

        if (!$customDataAttributes) {
            return [];
        }

        try {
            $dataAttrs = $this->jsonSerializer->unserialize($customDataAttributes);
            if (is_array($dataAttrs)) {
                return $dataAttrs;
            }
        } catch (\Exception $e) {
            // Silently handle errors
        }

        return [];
    }

    /**
     * Get hover data attributes
     *
     * @param mixed $item
     * @return array
     */
    public function getHoverDataAttributes($item): array
    {
        $attributes = [];

        $hoverBgColor = $this->getItemValue($item, 'hover_bg_color', 'getHoverBgColor', '');
        $hoverTextColor = $this->getItemValue($item, 'hover_text_color', 'getHoverTextColor', '');
        $hoverEffect = $this->getItemValue($item, 'hover_effect', 'getHoverEffect', '');

        if ($hoverBgColor) {
            $attributes['data-hover-bg-color'] = $hoverBgColor;
        }
        if ($hoverTextColor) {
            $attributes['data-hover-text-color'] = $hoverTextColor;
        }
        if ($hoverEffect && $hoverEffect !== 'default') {
            $attributes['data-hover-effect'] = $hoverEffect;
        }

        return $attributes;
    }

    /**
     * Get animation CSS class
     *
     * @param mixed $item
     * @return string
     */
    public function getAnimationClass($item): string
    {
        $animation = $this->getItemValue($item, 'animation', 'getAnimation', '');

        if (!$animation || $animation === 'none') {
            return '';
        }

        return 'animate__animated animate__' . $animation;
    }

    /**
     * Check if item should be visible on current device
     *
     * @param mixed $item
     * @return bool
     */
    public function isVisibleOnCurrentDevice($item): bool
    {
        $showOnDesktop = $this->getItemValue($item, 'show_on_desktop', 'getShowOnDesktop', true);
        $showOnTablet = $this->getItemValue($item, 'show_on_tablet', 'getShowOnTablet', true);
        $showOnMobile = $this->getItemValue($item, 'show_on_mobile', 'getShowOnMobile', true);

        // Convert string values to boolean
        if (is_string($showOnDesktop)) {
            $showOnDesktop = $showOnDesktop !== '0';
        }
        if (is_string($showOnTablet)) {
            $showOnTablet = $showOnTablet !== '0';
        }
        if (is_string($showOnMobile)) {
            $showOnMobile = $showOnMobile !== '0';
        }

        // If all devices are hidden, return false
        if (!$showOnDesktop && !$showOnTablet && !$showOnMobile) {
            return false;
        }

        // Otherwise, let CSS handle the visibility
        return true;
    }

    /**
     * Get device visibility CSS classes
     *
     * @param mixed $item
     * @return string
     */
    public function getDeviceVisibilityClasses($item): string
    {
        $classes = [];

        $showOnDesktop = $this->getItemValue($item, 'show_on_desktop', 'getShowOnDesktop', true);
        $showOnTablet = $this->getItemValue($item, 'show_on_tablet', 'getShowOnTablet', true);
        $showOnMobile = $this->getItemValue($item, 'show_on_mobile', 'getShowOnMobile', true);

        // Convert string values to boolean
        if (is_string($showOnDesktop)) {
            $showOnDesktop = $showOnDesktop !== '0';
        }
        if (is_string($showOnTablet)) {
            $showOnTablet = $showOnTablet !== '0';
        }
        if (is_string($showOnMobile)) {
            $showOnMobile = $showOnMobile !== '0';
        }

        if (!$showOnDesktop) {
            $classes[] = 'hide-desktop';
        }
        if (!$showOnTablet) {
            $classes[] = 'hide-tablet';
        }
        if (!$showOnMobile) {
            $classes[] = 'hide-mobile';
        }

        return implode(' ', $classes);
    }

    /**
     * Get tooltip text
     *
     * @param mixed $item
     * @return string
     */
    public function getTooltipText($item): string
    {
        return $this->getItemValue($item, 'tooltip_text', 'getTooltipText', '');
    }

    /**
     * Get custom click action JavaScript
     *
     * @param mixed $item
     * @return string
     */
    public function getCustomClickAction($item): string
    {
        return $this->getItemValue($item, 'custom_click_action', 'getCustomClickAction', '');
    }

    /**
     * Get ARIA label
     *
     * @param mixed $item
     * @return string
     */
    public function getAriaLabel($item): string
    {
        $ariaLabel = $this->getItemValue($item, 'aria_label', 'getAriaLabel', '');

        if (!$ariaLabel) {
            // Fallback to title
            $ariaLabel = $this->getItemValue($item, 'title', 'getTitle', '');
        }

        return $ariaLabel;
    }

    /**
     * Get ARIA role
     *
     * @param mixed $item
     * @return string
     */
    public function getAriaRole($item): string
    {
        $ariaRole = $this->getItemValue($item, 'aria_role', 'getAriaRole', '');

        if (!$ariaRole || $ariaRole === 'default') {
            return 'menuitem';
        }

        return $ariaRole;
    }

    /**
     * Get column width for mega menu
     *
     * @param mixed $item
     * @return string
     */
    public function getColumnWidth($item): string
    {
        return $this->getItemValue($item, 'column_width', 'getColumnWidth', 'auto');
    }

    /**
     * Get the MenuRenderer helper instance
     *
     * @return \Panth\MegaMenu\Helper\MenuRenderer
     */
    public function getMenuRenderer(): \Panth\MegaMenu\Helper\MenuRenderer
    {
        return $this->menuRenderer;
    }
}
