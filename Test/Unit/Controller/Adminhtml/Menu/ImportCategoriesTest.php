<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\ImportCategories;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\TestCase;

class ImportCategoriesTest extends TestCase
{
    use ControllerTestTrait;

    private function controller(array $categories, ?string $suffix = '.html', bool $fail = false): ImportCategories
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addIsActiveFilter')->willReturnSelf();
        $collection->method('addOrderField')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator($categories));
        $factory = $this->createStub(CollectionFactory::class);
        if ($fail) {
            $factory->method('create')->willThrowException(new \RuntimeException('flat index missing'));
        } else {
            $factory->method('create')->willReturn($collection);
        }
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn($suffix);

        return new ImportCategories(
            $this->context(),
            $this->jsonFactory(),
            $factory,
            $this->createStub(StoreManagerInterface::class),
            $scope
        );
    }

    private function cat(int $id, int $parent, int $level, int $position, string $name, ?string $path = null, ?string $key = null): DataObject
    {
        return new DataObject([
            'id' => $id, 'parent_id' => $parent, 'level' => $level, 'position' => $position,
            'name' => $name, 'url_path' => $path, 'url_key' => $key,
        ]);
    }

    private function tree(): array
    {
        return [
            $this->cat(1, 0, 0, 0, 'Root Catalog'),
            $this->cat(2, 1, 1, 0, 'Default Category'),
            $this->cat(10, 2, 2, 2, 'Men', 'men'),
            $this->cat(11, 2, 2, 1, 'Women', 'women'),
            $this->cat(20, 11, 3, 5, 'Dresses', null, 'dresses'),
            $this->cat(21, 11, 3, 1, 'Tops', 'women/tops'),
            $this->cat(30, 21, 4, 0, 'Tees', 'women/tops/tees'),
            $this->cat(40, 10, 3, 0, 'NoUrl'),
        ];
    }

    public function testBuildsOrderedTreeWithRelativeUrls(): void
    {
        $this->controller($this->tree())->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $items = $this->jsonPayload['items'];
        $this->assertSame(
            ['Women', 'Tops', 'Tees', 'Dresses', 'Men', 'NoUrl'],
            array_column($items, 'title')
        );
        $this->assertSame([0, 1, 2, 3, 4, 5], array_column($items, 'position'));
        $this->assertSame([0, 1, 2, 1, 0, 1], array_column($items, 'level'));
        $this->assertSame(
            ['/women.html', '/women/tops.html', '/women/tops/tees.html', '/dresses.html', '/men.html', ''],
            array_column($items, 'url')
        );
        $this->assertSame([null, 'cat_11', 'cat_21', 'cat_11', null, 'cat_10'], array_column($items, 'parent_id'));
        $this->assertSame('cat_11', $items[0]['temp_id']);
        $this->assertSame('category', $items[0]['item_type']);
        $this->assertSame(6, $this->jsonPayload['count']);
        $this->assertSame('Successfully imported 6 categories', $this->jsonPayload['message']);
    }

    public function testMaxLevelLimitsDepth(): void
    {
        $this->params = ['max_level' => '2'];
        $this->controller($this->tree())->execute();

        $this->assertSame(['Women', 'Tops', 'Dresses', 'Men', 'NoUrl'], array_column($this->jsonPayload['items'], 'title'));
    }

    public function testMaxLevelOneReturnsOnlyTopLevel(): void
    {
        $this->params = ['max_level' => 1];
        $this->controller($this->tree())->execute();

        $this->assertSame(['Women', 'Men'], array_column($this->jsonPayload['items'], 'title'));
    }

    public function testNoSuffixConfigured(): void
    {
        $this->controller([$this->cat(10, 2, 2, 0, 'Men', 'men')], null)->execute();

        $this->assertSame('/men', $this->jsonPayload['items'][0]['url']);
    }

    public function testEmptyCatalog(): void
    {
        $this->controller([])->execute();

        $this->assertSame(['success' => true, 'items' => [], 'count' => 0, 'message' => 'Successfully imported 0 categories'], $this->jsonPayload);
    }

    public function testErrorIsReported(): void
    {
        $this->controller([], '.html', true)->execute();

        $this->assertSame(['success' => false, 'message' => 'Error importing categories: flat index missing'], $this->jsonPayload);
    }

    public function testCsrfAndAcl(): void
    {
        $controller = $this->controller([]);

        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
