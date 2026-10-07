<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DataObject;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryItemsTest extends TestCase
{
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuViewModelTrait;

    private array $filters = [];

    private function factory(array $categories, bool $fail = false): CategoryCollectionFactory
    {
        $collection = $this->createMock(Collection::class);
        foreach (['setStoreId', 'addAttributeToSelect', 'addIdFilter', 'addIsActiveFilter', 'addPathsFilter', 'addUrlRewriteToResult'] as $method) {
            $collection->method($method)->willReturnCallback(function (...$args) use ($method, $collection, $fail) {
                if ($fail) {
                    throw new \RuntimeException('db');
                }
                $this->filters[] = [$method, $args];
                return $collection;
            });
        }
        $objects = [];
        foreach ($categories as $id => $url) {
            $objects[] = new DataObject(['id' => $id, 'url' => $url]);
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($objects));
        $factory = $this->createStub(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return $factory;
    }

    private function tree(): array
    {
        return [
            ['title' => 'Shop', 'item_type' => 'custom', 'url' => '#', 'children' => [
                ['title' => 'Gear', 'item_type' => 'category', 'category_id' => '3', 'url' => '/gear.html', 'children' => [
                    ['title' => 'Bags', 'item_type' => 'category', 'category_id' => 4, 'url' => '/gear/bags.html'],
                    ['title' => 'Tote', 'item_type' => 'link', 'url' => '/tote.html'],
                ]],
                ['title' => 'Collections', 'item_type' => 'category', 'category_id' => 7, 'url' => '/collections.html', 'children' => [
                    ['title' => 'Eco', 'item_type' => 'category', 'category_id' => 36, 'url' => '/collections/eco.html'],
                ]],
            ]],
            ['title' => 'Sale', 'item_type' => 'link', 'url' => '/sale.html'],
        ];
    }

    public function testCollectsCategoryIdsFromTheWholeTree(): void
    {
        $vm = $this->menuViewModel();

        $this->assertSame([3, 4, 7, 36], $vm->collectCategoryIds($this->tree()));
        $this->assertSame([], $vm->collectCategoryIds([['item_type' => 'category', 'category_id' => 0], 'x']));
    }

    public function testInactiveOrForeignCategoriesAreRemovedAndUrlsFollowTheStore(): void
    {
        $vm = $this->menuViewModel([], ['categoryCollectionFactory' => $this->factory([
            3 => 'https://luma.test/gear.html',
            4 => 'https://luma.test/gear/bags.html',
            36 => 'https://luma.test/collections/eco.html',
        ])]);

        $result = $vm->resolveCategoryItems($this->tree());

        $shop = $result[0]['children'];
        $this->assertCount(1, $shop);
        $this->assertSame('Gear', $shop[0]['title']);
        $this->assertSame('https://luma.test/gear.html', $shop[0]['url']);
        $this->assertSame('https://luma.test/gear/bags.html', $shop[0]['children'][0]['url']);
        $this->assertSame('/tote.html', $shop[0]['children'][1]['url']);
        $this->assertSame('#', $result[0]['url']);
        $this->assertSame('/sale.html', $result[1]['url']);
        $this->assertContains(['addIdFilter', [[3, 4, 7, 36]]], $this->filters);
        $this->assertContains(['setStoreId', [1]], $this->filters);
        $this->assertContains(['addIsActiveFilter', []], $this->filters);
    }

    public function testTreeWithoutCategoriesIsReturnedWithoutQuery(): void
    {
        $vm = $this->menuViewModel([], ['categoryCollectionFactory' => $this->factory([], true)]);
        $tree = [['title' => 'Sale', 'item_type' => 'link', 'url' => '/sale.html']];

        $this->assertSame($tree, $vm->resolveCategoryItems($tree));
        $this->assertSame([], $this->filters);
    }

    public function testLookupFailureKeepsTheTree(): void
    {
        $vm = $this->menuViewModel([], ['categoryCollectionFactory' => $this->factory([], true)]);

        $this->assertSame($this->tree(), $vm->resolveCategoryItems($this->tree()));
    }

    public function testCategoryWithoutUrlKeepsTheStoredUrl(): void
    {
        $vm = $this->menuViewModel([], ['categoryCollectionFactory' => $this->factory([3 => ''])]);
        $tree = [['title' => 'Gear', 'item_type' => 'category', 'category_id' => 3, 'url' => '/gear.html']];

        $this->assertSame($tree, $vm->resolveCategoryItems($tree));
    }
}
