<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\Collection as BlockCollection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\GetCategories;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GetCategoriesTest extends TestCase
{
    use ControllerTestTrait;

    private array $filters = [];
    private array $paging = [];

    private function collection(string $class, array $items, int $size): object
    {
        $collection = $this->createStub($class);
        foreach (['addAttributeToSelect', 'addOrderField', 'addFieldToSelect'] as $method) {
            if (method_exists($class, $method)) {
                $collection->method($method)->willReturnSelf();
            }
        }
        foreach (['addAttributeToFilter', 'addFieldToFilter'] as $method) {
            if (method_exists($class, $method)) {
                $collection->method($method)->willReturnCallback(function ($field, $cond = null) use ($collection, $class) {
                    $this->filters[$class][] = [$field, $cond];
                    return $collection;
                });
            }
        }
        $collection->method('setPageSize')->willReturnCallback(function ($size) use ($collection, $class) {
            $this->paging[$class]['size'] = $size;
            return $collection;
        });
        $collection->method('setCurPage')->willReturnCallback(function ($page) use ($collection, $class) {
            $this->paging[$class]['page'] = $page;
            return $collection;
        });
        $collection->method('getSize')->willReturn($size);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));

        return $collection;
    }

    private function factory(string $class, object $collection): object
    {
        $factory = $this->createStub($class);
        $factory->method('create')->willReturn($collection);

        return $factory;
    }

    private function controller(bool $failCategories = false): GetCategories
    {
        $categories = $this->collection(CategoryCollection::class, [
            new DataObject([
                'id' => 5, 'name' => 'Women', 'level' => 2, 'url' => 'https://shop.test/women.html',
                'parent_id' => 2, 'position' => '3', 'is_active' => '1', 'image' => 'w.jpg', 'children_count' => '4',
            ]),
            new DataObject([
                'id' => 6, 'name' => 'Tops', 'level' => 3, 'url' => 'https://cdn.test/tops.html',
                'parent_id' => 5, 'include_in_menu' => '0', 'description' => 'All tops',
            ]),
        ], 2);
        $categoryFactory = $this->createStub(CategoryCollectionFactory::class);
        if ($failCategories) {
            $categoryFactory->method('create')->willThrowException(new \RuntimeException('index broken'));
        } else {
            $categoryFactory->method('create')->willReturn($categories);
        }
        $pages = $this->collection(PageCollection::class, [new DataObject(['id' => 1, 'title' => 'Home', 'identifier' => 'home'])], 1);
        $blocks = $this->collection(BlockCollection::class, [new DataObject(['id' => 8, 'title' => 'Promo', 'identifier' => 'promo'])], 1);
        $products = $this->collection(ProductCollection::class, [
            new DataObject(['id' => 3, 'name' => 'Bag', 'sku' => 'B1', 'product_url' => 'https://shop.test/bag.html']),
        ], 5);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturnCallback(
            fn ($type = UrlInterface::URL_TYPE_LINK) => $type === UrlInterface::URL_TYPE_MEDIA
                ? 'https://shop.test/media/' : 'https://shop.test/'
        );
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new GetCategories(
            $this->context(),
            $this->jsonFactory(),
            $categoryFactory,
            $this->factory(ProductCollectionFactory::class, $products),
            $this->factory(PageCollectionFactory::class, $pages),
            $this->factory(BlockCollectionFactory::class, $blocks),
            $storeManager
        );
    }

    public function testDefaultTypeReturnsCategoriesWithRelativeUrls(): void
    {
        $this->controller()->execute();

        $this->assertSame(2, $this->jsonPayload['total_count']);
        [$women, $tops] = $this->jsonPayload['categories'];
        $this->assertSame([
            'id' => 5,
            'name' => 'Women',
            'url' => '/women.html',
            'level' => 0,
            'parent_id' => 2,
            'position' => 3,
            'include_in_menu' => 1,
            'is_active' => 1,
            'description' => '',
            'image' => 'https://shop.test/media/catalog/category/w.jpg',
            'children_count' => 4,
        ], $women);
        $this->assertSame('https://cdn.test/tops.html', $tops['url']);
        $this->assertSame(1, $tops['level']);
        $this->assertSame(0, $tops['include_in_menu']);
        $this->assertSame('', $tops['image']);
        $this->assertSame('All tops', $tops['description']);
        $this->assertSame(['size' => 50, 'page' => 1], $this->paging[CategoryCollection::class]);
        $this->assertSame('application/json', $this->headers['Content-Type']);
    }

    public function testSearchAndPagingParametersAreApplied(): void
    {
        $this->params = ['type' => 'category', 'search' => '  wom ', 'page' => '2', 'pageSize' => '10'];
        $this->controller()->execute();

        $this->assertContains(['name', ['like' => '%wom%']], $this->filters[CategoryCollection::class]);
        $this->assertSame(['size' => 10, 'page' => 2], $this->paging[CategoryCollection::class]);
    }

    public static function typeProvider(): array
    {
        return [
            'pages' => ['cms_page', 'pages', [['id' => 1, 'title' => 'Home', 'identifier' => 'home']], 1],
            'blocks' => ['cms_block', 'blocks', [['id' => 8, 'title' => 'Promo', 'identifier' => 'promo']], 1],
            'products' => ['product', 'products', [['id' => 3, 'name' => 'Bag', 'sku' => 'B1', 'url' => '/bag.html']], 5],
        ];
    }

    #[DataProvider('typeProvider')]
    public function testSingleTypes(string $type, string $key, array $expected, int $total): void
    {
        $this->params = ['type' => $type];
        $this->controller()->execute();

        $this->assertSame($expected, $this->jsonPayload[$key]);
        $this->assertSame($total, $this->jsonPayload['total_count']);
    }

    public function testSearchFiltersPagesOnTitleAndIdentifier(): void
    {
        $this->params = ['type' => 'cms_page', 'search' => 'ho'];
        $this->controller()->execute();

        $this->assertContains(
            [['title', 'identifier'], [['like' => '%ho%'], ['like' => '%ho%']]],
            $this->filters[PageCollection::class]
        );
    }

    public function testAllTypeAggregatesCounts(): void
    {
        $this->params = ['type' => 'all'];
        $this->controller()->execute();

        $this->assertSame(8, $this->jsonPayload['total_count']);
        $this->assertCount(2, $this->jsonPayload['categories']);
        $this->assertCount(1, $this->jsonPayload['pages']);
        $this->assertCount(1, $this->jsonPayload['products']);
        $this->assertArrayNotHasKey('blocks', $this->jsonPayload);
    }

    public function testInvalidType(): void
    {
        $this->params = ['type' => 'widget'];
        $this->controller()->execute();

        $this->assertSame(['error' => true, 'message' => 'Invalid type: widget'], $this->jsonPayload);
    }

    public function testExceptionIsReported(): void
    {
        $this->controller(true)->execute();

        $this->assertSame(['error' => true, 'message' => 'index broken'], $this->jsonPayload);
    }

    public function testBuildCategoryTreeNestsByParent(): void
    {
        $method = new \ReflectionMethod(GetCategories::class, 'buildCategoryTree');
        $tree = $method->invoke($this->controller(), [
            ['id' => 1, 'parent_id' => 0, 'name' => 'Root'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Child'],
            ['id' => 3, 'parent_id' => 2, 'name' => 'Grandchild'],
            ['id' => 4, 'parent_id' => 99, 'name' => 'Orphan'],
        ]);

        $this->assertCount(2, $tree);
        $this->assertSame('Root', $tree[0]['name']);
        $this->assertSame('Child', $tree[0]['children'][0]['name']);
        $this->assertSame('Grandchild', $tree[0]['children'][0]['children'][0]['name']);
        $this->assertSame('Orphan', $tree[1]['name']);
    }

    public function testCsrfAndAcl(): void
    {
        $controller = $this->controller();

        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
