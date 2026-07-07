<?php
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
    private $menuRepository;

    private $itemRepository;

    private $categoryRepository;

    private $categoryCollectionFactory;

    private $productRepository;

    private $pageRepository;

    private $urlBuilder;

    private $storeManager;

    private $customerSession;

    private $categoryHelper;

    private $pageHelper;

    private $filterProvider;

    private $menuHelper;

    private $assetRepository;

    private $dateTime;

    private $jsonSerializer;

    private $searchCriteriaBuilder;

    private $logger;

    private $cmsBlockFactory;

    private $menuRenderer;

    private $categoryCache = [];

    private $pageCache = [];

    private $menuTreeCache = [];

    private $currentMenu = null;

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

    private function getItemValue($item, string $key, ?string $method = null, $default = null)
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }
        return $method && method_exists($item, $method) ? $item->$method() : $default;
    }

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

    public function getMenuItems(?string $menuIdentifier = null): array
    {
        try {
            $menu = $this->getMenu($menuIdentifier);

            if (!$menu || !$menu->getIsActive()) {
                return [];
            }

            $menuId = $menu->getMenuId();

            if (isset($this->menuTreeCache[$menuId])) {
                return $this->menuTreeCache[$menuId];
            }

            $items = $this->itemRepository->getMenuTree($menuId);

            $visibleItems = $this->filterVisibleItems($items);

            $this->menuTreeCache[$menuId] = $visibleItems;

            return $visibleItems;
        } catch (\Exception $e) {
            return [];
        }
    }

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

    public function getItemUrl($item): string
    {
        $isActive = $this->getItemValue($item, 'is_active', 'getIsActive', true);
        if (!$isActive) {
            return '#';
        }

        $url = $this->getItemValue($item, 'url', 'getUrl', '');

        if (!empty($url) && $url !== '#') {
            return $url;
        }

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

                    return '#';
            }
        } catch (\Exception $e) {
        }

        return '#';
    }

    public function hasChildren($item): bool
    {
        if (is_array($item)) {
            return !empty($item['children']);
        }
        return $item->hasChildren() && !empty($this->getChildren($item));
    }

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

    public function processItemContent($item): string
    {
        $cmsBlock = $this->getItemValue($item, 'cms_block', 'getCmsBlock', '');

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
            }
        }

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

    public function getImageUrl(ItemInterface $item, string $type = 'thumbnail'): string
    {
        try {
            $content = $item->getContent();
            if ($content && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
                return $matches[1];
            }

            if ($item->getLinkType() === ItemInterface::LINK_CATEGORY && $item->getLinkValue()) {
                return $this->getCategoryImageUrl((int)$item->getLinkValue(), $type);
            }

            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function isItemVisible($item): bool
    {
        $isActive = $this->getItemValue($item, 'is_active', 'getIsActive', true);
        if (!$isActive) {
            return false;
        }

        $maxDepth = $this->menuHelper->getMaxDepth();
        $level = $this->getItemValue($item, 'level', 'getLevel', 0);
        if ($maxDepth > 0 && $level >= $maxDepth) {
            return false;
        }

        if (!$this->isVisibleBySchedule($item)) {
            return false;
        }

        if (!$this->isVisibleInCurrentStore($item)) {
            return false;
        }

        if (!$this->isVisibleForCustomerGroup($item)) {
            return false;
        }

        if (!$this->isVisibleOnCurrentDevice($item)) {
            return false;
        }

        return true;
    }

    private function isVisibleBySchedule($item): bool
    {
        $startDate = $this->getItemValue($item, 'start_date', 'getStartDate', null);
        $endDate = $this->getItemValue($item, 'end_date', 'getEndDate', null);

        if (!$startDate && !$endDate) {
            return true;
        }

        $currentTimestamp = $this->dateTime->gmtTimestamp();

        if ($startDate) {
            $startTimestamp = strtotime($startDate);
            if ($currentTimestamp < $startTimestamp) {
                return false;
            }
        }

        if ($endDate) {
            $endTimestamp = strtotime($endDate . ' 23:59:59');
            if ($currentTimestamp > $endTimestamp) {
                return false;
            }
        }

        return true;
    }

    private function isVisibleInCurrentStore($item): bool
    {
        $storeIds = $this->getItemValue($item, 'store_ids', 'getStoreIds', null);

        if (empty($storeIds)) {
            return true;
        }

        if (is_string($storeIds)) {
            $storeIds = explode(',', $storeIds);
        }

        $currentStoreId = $this->storeManager->getStore()->getId();

        return in_array($currentStoreId, $storeIds) || in_array(0, $storeIds);
    }

    private function isVisibleForCustomerGroup($item): bool
    {
        $customerGroupIds = $this->getItemValue($item, 'customer_group_ids', 'getCustomerGroupIds', null);

        if (empty($customerGroupIds)) {
            return true;
        }

        if (is_string($customerGroupIds)) {
            $customerGroupIds = explode(',', $customerGroupIds);
        }

        $currentCustomerGroupId = $this->customerSession->getCustomerGroupId();

        return in_array($currentCustomerGroupId, $customerGroupIds);
    }

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

    public function getItemClass(ItemInterface $item, string $additionalClasses = ''): string
    {
        $classes = $this->menuHelper->getItemClasses($item);

        if ($additionalClasses) {
            $classes .= ' ' . $additionalClasses;
        }

        return $classes;
    }

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

        $currentUrl = rtrim($currentUrl, '/');
        $itemUrl = rtrim($itemUrl, '/');

        return $currentUrl === $itemUrl;
    }

    public function getLinkTarget($item): string
    {
        $openNewTab = $this->getItemValue($item, 'open_new_tab', 'getOpenNewTab', false);
        return $openNewTab ? '_blank' : '_self';
    }

    public function getLinkRel($item): string
    {
        $openNewTab = $this->getItemValue($item, 'open_new_tab', 'getOpenNewTab', false);
        if ($openNewTab) {
            return 'noopener noreferrer';
        }

        return '';
    }

    public function getColumnWidthClass($item): string
    {
        $columns = $this->getItemValue($item, 'columns', 'getColumns', 1);

        if ($columns <= 0) {
            $columns = 1;
        }

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

    public function shouldShowContent($item): bool
    {
        $itemType = $this->getItemValue($item, 'item_type', 'getItemType');
        $content = $this->getItemValue($item, 'content', 'getContent');
        return $itemType === ItemInterface::TYPE_CONTENT
            && !empty($content);
    }

    public function getItemDepth($item): int
    {
        return $this->getItemValue($item, 'level', 'getLevel', 0);
    }

    public function isTopLevel($item): bool
    {
        $level = $this->getItemValue($item, 'level', 'getLevel', 0);
        $parentId = $this->getItemValue($item, 'parent_id', 'getParentId');
        return $level === 0 || $parentId === null;
    }

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

    private function getCustomUrl(string $url): string
    {
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        if (strpos($url, '/') === 0) {
            return $this->urlBuilder->getBaseUrl() . ltrim($url, '/');
        }

        return $this->urlBuilder->getUrl($url);
    }

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

    public function getItemInlineStyles($item): string
    {
        $styles = [];

        $bgColor = $this->getItemValue($item, 'bg_color', 'getBgColor', '');
        $textColor = $this->getItemValue($item, 'text_color', 'getTextColor', '');

        if ($bgColor) {
            $styles[] = 'background-color:' . $bgColor;
        }
        if ($textColor) {
            $styles[] = 'color:' . $textColor;
        }

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
        }

        return [];
    }

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

    public function getAnimationClass($item): string
    {
        $animation = $this->getItemValue($item, 'animation', 'getAnimation', '');

        if (!$animation || $animation === 'none') {
            return '';
        }

        return 'animate__animated animate__' . $animation;
    }

    public function isVisibleOnCurrentDevice($item): bool
    {
        $showOnDesktop = $this->getItemValue($item, 'show_on_desktop', 'getShowOnDesktop', true);
        $showOnTablet = $this->getItemValue($item, 'show_on_tablet', 'getShowOnTablet', true);
        $showOnMobile = $this->getItemValue($item, 'show_on_mobile', 'getShowOnMobile', true);

        if (is_string($showOnDesktop)) {
            $showOnDesktop = $showOnDesktop !== '0';
        }
        if (is_string($showOnTablet)) {
            $showOnTablet = $showOnTablet !== '0';
        }
        if (is_string($showOnMobile)) {
            $showOnMobile = $showOnMobile !== '0';
        }

        if (!$showOnDesktop && !$showOnTablet && !$showOnMobile) {
            return false;
        }

        return true;
    }

    public function getDeviceVisibilityClasses($item): string
    {
        $classes = [];

        $showOnDesktop = $this->getItemValue($item, 'show_on_desktop', 'getShowOnDesktop', true);
        $showOnTablet = $this->getItemValue($item, 'show_on_tablet', 'getShowOnTablet', true);
        $showOnMobile = $this->getItemValue($item, 'show_on_mobile', 'getShowOnMobile', true);

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

    public function getTooltipText($item): string
    {
        return $this->getItemValue($item, 'tooltip_text', 'getTooltipText', '');
    }

    public function getCustomClickAction($item): string
    {
        return $this->getItemValue($item, 'custom_click_action', 'getCustomClickAction', '');
    }

    public function getAriaLabel($item): string
    {
        $ariaLabel = $this->getItemValue($item, 'aria_label', 'getAriaLabel', '');

        if (!$ariaLabel) {
            $ariaLabel = $this->getItemValue($item, 'title', 'getTitle', '');
        }

        return $ariaLabel;
    }

    public function getAriaRole($item): string
    {
        $ariaRole = $this->getItemValue($item, 'aria_role', 'getAriaRole', '');

        if (!$ariaRole || $ariaRole === 'default') {
            return 'menuitem';
        }

        return $ariaRole;
    }

    public function getColumnWidth($item): string
    {
        return $this->getItemValue($item, 'column_width', 'getColumnWidth', 'auto');
    }

    public function getMenuRenderer(): \Panth\MegaMenu\Helper\MenuRenderer
    {
        return $this->menuRenderer;
    }
}
