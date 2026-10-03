<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\ResourceModel\Page\Collection as PageCollection;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Model\ResourceModel\Item\Collection as ItemCollection;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection as MenuCollection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory as MenuCollectionFactory;
use Panth\MegaMenu\Model\Source\Category;
use Panth\MegaMenu\Model\Source\CmsPage;
use Panth\MegaMenu\Model\Source\CustomerGroup;
use Panth\MegaMenu\Model\Source\Menu;
use Panth\MegaMenu\Model\Source\ParentItems;
use Panth\MegaMenu\Model\Source\Product;
use PHPUnit\Framework\TestCase;

class CollectionSourcesTest extends TestCase
{
    private function collection(string $class, array $items): object
    {
        $collection = $this->createStub($class);
        foreach (['addAttributeToSelect', 'addAttributeToFilter', 'addFieldToSelect', 'addFieldToFilter', 'setOrder', 'setPageSize'] as $method) {
            if (method_exists($class, $method)) {
                $collection->method($method)->willReturnSelf();
            }
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));

        return $collection;
    }

    private function factory(string $factoryClass, object $collection, int $times = 1): object
    {
        $factory = $this->createMock($factoryClass);
        $factory->expects($this->exactly($times))->method('create')->willReturn($collection);

        return $factory;
    }

    public function testCategoryOptionsIndentByLevelAndCache(): void
    {
        $collection = $this->collection(CategoryCollection::class, [
            new DataObject(['id' => 3, 'level' => 2, 'name' => 'Men']),
            new DataObject(['id' => 4, 'level' => 4, 'name' => 'Shirts']),
        ]);
        $source = new Category(
            $this->factory(CategoryCollectionFactory::class, $collection),
            $this->createStub(StoreManagerInterface::class)
        );

        $expected = [
            ['value' => 3, 'label' => ' Men'],
            ['value' => 4, 'label' => '---- Shirts'],
        ];
        $this->assertSame($expected, $source->toOptionArray());
        $this->assertSame($expected, $source->toOptionArray());
    }

    public function testCategoryOptionsEmptyOnException(): void
    {
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('db'));
        $source = new Category($factory, $this->createStub(StoreManagerInterface::class));

        $this->assertSame([], $source->toOptionArray());
    }

    public function testCmsPageLabelIncludesIdentifier(): void
    {
        $collection = $this->collection(PageCollection::class, [
            new DataObject(['id' => 2, 'title' => 'About', 'identifier' => 'about-us']),
        ]);
        $source = new CmsPage($this->factory(PageCollectionFactory::class, $collection));

        $this->assertSame([['value' => 2, 'label' => 'About (about-us)']], $source->toOptionArray());
        $this->assertCount(1, $source->toOptionArray());
    }

    public function testCmsPageEmptyOnException(): void
    {
        $factory = $this->createStub(PageCollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('db'));

        $this->assertSame([], (new CmsPage($factory))->toOptionArray());
    }

    public function testCustomerGroupOptions(): void
    {
        $collection = $this->collection(GroupCollection::class, [
            new DataObject(['id' => 0, 'customer_group_code' => 'NOT LOGGED IN']),
            new DataObject(['id' => 1, 'customer_group_code' => 'General']),
        ]);
        $source = new CustomerGroup($this->factory(GroupCollectionFactory::class, $collection));

        $this->assertSame([
            ['value' => 0, 'label' => 'NOT LOGGED IN'],
            ['value' => 1, 'label' => 'General'],
        ], $source->toOptionArray());
    }

    public function testMenuOptionsStartWithPlaceholderAndCache(): void
    {
        $collection = $this->collection(MenuCollection::class, [
            new DataObject(['menu_id' => 5, 'title' => 'Main']),
        ]);
        $source = new Menu($this->factory(MenuCollectionFactory::class, $collection));

        $options = $source->toOptionArray();
        $this->assertCount(2, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame(['value' => 5, 'label' => 'Main'], $options[1]);
        $this->assertSame($options, $source->toOptionArray());
    }

    private function request(?string $menuId, ?string $itemId): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnMap([
            ['menu_id', null, $menuId],
            ['item_id', null, $itemId],
        ]);

        return $request;
    }

    public function testParentItemsWithoutMenuReturnsOnlyTopLevel(): void
    {
        $factory = $this->createMock(ItemCollectionFactory::class);
        $factory->expects($this->never())->method('create');
        $source = new ParentItems($factory, $this->request(null, null));

        $options = $source->toOptionArray();
        $this->assertCount(1, $options);
        $this->assertSame('', $options[0]['value']);
    }

    public function testParentItemsExcludesCurrentItemAndIndents(): void
    {
        $collection = $this->collection(ItemCollection::class, [
            new DataObject(['id' => 1, 'level' => 0, 'title' => 'Root']),
            new DataObject(['id' => 2, 'level' => 1, 'title' => 'Self']),
            new DataObject(['id' => 3, 'level' => 2, 'title' => 'Deep']),
        ]);
        $source = new ParentItems($this->factory(ItemCollectionFactory::class, $collection), $this->request('7', '2'));

        $options = $source->toOptionArray();
        $this->assertSame([1, 3], array_column(array_slice($options, 1), 'value'));
        $this->assertSame(' Root (ID: 1)', $options[1]['label']);
        $this->assertSame('---- Deep (ID: 3)', $options[2]['label']);
    }

    public function testProductOptionsAndCache(): void
    {
        $collection = $this->collection(ProductCollection::class, [
            new DataObject(['id' => 9, 'name' => 'Bag', 'sku' => 'BG-1']),
        ]);
        $status = $this->createStub(Status::class);
        $status->method('getVisibleStatusIds')->willReturn([1]);
        $visibility = $this->createStub(Visibility::class);
        $visibility->method('getVisibleInSiteIds')->willReturn([2, 4]);
        $source = new Product($this->factory(ProductCollectionFactory::class, $collection), $status, $visibility);

        $this->assertSame([['value' => 9, 'label' => 'Bag (BG-1)']], $source->toOptionArray());
        $this->assertCount(1, $source->toOptionArray());
    }

    public function testProductOptionsEmptyOnException(): void
    {
        $factory = $this->createStub(ProductCollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('db'));
        $source = new Product($factory, $this->createStub(Status::class), $this->createStub(Visibility::class));

        $this->assertSame([], $source->toOptionArray());
    }
}
