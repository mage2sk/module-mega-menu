<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Block\BlockFactory;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Cms\Model\Block as CmsBlock;
use Magento\Cms\Model\Template\Filter;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Api\ItemRepositoryInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Helper\MenuRenderer;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\ViewModel\Menu;

trait MenuViewModelTrait
{
    private array $deps = [];
    private int $now = 0;
    private int $groupId = 0;
    private int $storeId = 1;

    private function filterProvider(): FilterProvider
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('setStoreId')->willReturnSelf();
        $filter->method('filter')->willReturnCallback(fn ($c) => '[f]' . $c);
        $provider = $this->createStub(FilterProvider::class);
        $provider->method('getPageFilter')->willReturn($filter);
        $provider->method('getBlockFilter')->willReturn($filter);

        return $provider;
    }

    private function menuViewModel(array $config = [], array $overrides = []): Menu
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturnCallback(fn () => $this->storeId);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $session = $this->createStub(CustomerSession::class);
        $session->method('getCustomerGroupId')->willReturnCallback(fn () => $this->groupId);
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturnCallback(fn () => $this->now ?: time());

        $assets = $this->createStub(AssetRepository::class);
        $renderer = new MenuRenderer(
            $this->realEscaper(),
            $this->createStub(CmsBlock::class),
            $this->filterProvider(),
            $storeManager,
            $assets
        );

        $this->deps = $overrides + [
            'menuRepository' => $this->createStub(MenuRepositoryInterface::class),
            'itemRepository' => $this->createStub(ItemRepositoryInterface::class),
            'categoryRepository' => $this->createStub(CategoryRepositoryInterface::class),
            'productRepository' => $this->createStub(ProductRepositoryInterface::class),
            'urlBuilder' => $this->createStub(UrlInterface::class),
            'categoryHelper' => $this->createStub(CategoryHelper::class),
            'pageHelper' => $this->createStub(PageHelper::class),
            'filterProvider' => $this->filterProvider(),
            'cmsBlockFactory' => $this->createStub(BlockFactory::class),
        ];

        return new Menu(
            $this->deps['menuRepository'],
            $this->deps['itemRepository'],
            $this->deps['categoryRepository'],
            $this->createStub(CategoryCollectionFactory::class),
            $this->deps['productRepository'],
            $this->createStub(PageRepositoryInterface::class),
            $this->deps['urlBuilder'],
            $storeManager,
            $session,
            $this->deps['categoryHelper'],
            $this->deps['pageHelper'],
            $this->deps['filterProvider'],
            $this->dataHelper($config),
            $assets,
            $dateTime,
            new Json(),
            $this->createStub(SearchCriteriaBuilder::class),
            $this->createStub(\Psr\Log\LoggerInterface::class),
            $this->deps['cmsBlockFactory'],
            $renderer
        );
    }

    private function item(array $data, array $children = []): Item
    {
        $resource = $this->createStub(ItemResource::class);
        $resource->method('getIdFieldName')->willReturn('item_id');
        $item = new Item($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
        $item->setChildren($children);

        return $item;
    }
}
