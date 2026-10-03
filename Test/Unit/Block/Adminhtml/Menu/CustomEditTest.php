<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block\Adminhtml\Menu;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Panth\MegaMenu\Block\Adminhtml\Menu\CustomEdit;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\PreviewAccess;
use Panth\MegaMenu\Model\ResourceModel\Item\Collection;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomEditTest extends TestCase
{
    use EscaperTrait;
    use MenuModelTrait;

    private mixed $previousObjectManager = null;
    private array $params = [];

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(ObjectManager::class, '_instance');
        $this->previousObjectManager = $property->getValue();
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(fn (string $class) => $this->createStub($class));
        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previousObjectManager);
    }

    private function block(array $rows = [], array $collectionItems = [], array $data = []): CustomEdit
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(fn ($name, $default = null) => $this->params[$name] ?? $default);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(fn (string $path) => '/admin/' . $path);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        $context->method('getEscaper')->willReturn($this->realEscaper());

        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->selfLoadingMenu($rows));

        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getSize')->willReturn(count($collectionItems));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($collectionItems));
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        return new CustomEdit($context, $factory, $collectionFactory, $data);
    }

    public function testNewMenuDefaults(): void
    {
        $block = $this->block();

        $this->assertNull($block->getMenuId());
        $this->assertSame(
            ['menu_id' => null, 'title' => '', 'identifier' => '', 'is_active' => 1, 'css_class' => '', 'sort_order' => 0, 'description' => '', 'custom_css' => ''],
            json_decode($block->getMenuData(), true)
        );
        $this->assertSame('[]', $block->getMenuItemsData());
        $this->assertSame('/admin/panth_menu/menu/customsave', $block->getSaveUrl());
        $this->assertSame('/admin/panth_menu/menu/index', $block->getListUrl());
    }

    public function testExistingMenuData(): void
    {
        $this->params = ['menu_id' => '4'];
        $block = $this->block([['menu_id' => 4, 'title' => 'Main', 'identifier' => 'main', 'is_active' => '1', 'sort_order' => '3', 'items_json' => '[{"a":1}]']]);

        $data = json_decode($block->getMenuData(), true);
        $this->assertSame(4, $data['menu_id']);
        $this->assertSame(1, $data['is_active']);
        $this->assertSame(3, $data['sort_order']);
        $this->assertSame('[{"a":1}]', $block->getMenuItemsData());
    }

    public function testMissingMenu(): void
    {
        $this->params = ['menu_id' => '9'];
        $block = $this->block();

        $this->assertSame('null', $block->getMenuData());
        $this->assertSame('[]', $block->getMenuItemsData());
    }

    public function testItemsFallBackToLegacyItemTable(): void
    {
        $this->params = ['menu_id' => '4'];
        $legacy = new DataObject([
            'item_id' => 11, 'menu_id' => 4, 'title' => 'Legacy', 'url' => '/l', 'item_type' => 'link',
            'parent_id' => null, 'position' => '2', 'level' => '0', 'is_active' => '1', 'target' => '_self',
        ]);

        $items = json_decode($this->block([['menu_id' => 4]], [$legacy])->getMenuItemsData(), true);

        $this->assertSame(11, $items[0]['item_id']);
        $this->assertSame(2, $items[0]['position']);
        $this->assertSame(1, $items[0]['show_on_frontend']);
        $this->assertSame('Legacy', $items[0]['title']);
        $this->assertSame('[]', $this->block([['menu_id' => 4]])->getMenuItemsData());
    }

    public function testPreviewTokenRequiresPreviewAccess(): void
    {
        $this->assertSame('', $this->block()->getPreviewToken());

        $deployment = $this->createStub(DeploymentConfig::class);
        $deployment->method('get')->willReturn('key');
        $access = new PreviewAccess($deployment);
        $token = $this->block([], [], ['preview_access' => $access])->getPreviewToken();

        $this->assertTrue($access->isValidToken($token));
    }
}
