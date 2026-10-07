<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\CustomSave;
use Panth\MegaMenu\Model\ItemFactory;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomSaveTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private MenuRepositoryInterface&MockObject $repository;
    private AdapterInterface&MockObject $connection;
    private ?Menu $saved = null;

    private function controller(array $rows = []): CustomSave
    {
        $this->repository = $this->createMock(MenuRepositoryInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $resource = $this->loadingMenuResource($rows);
        $resource->method('getConnection')->willReturn($this->connection);
        $resource->method('getTable')->willReturnArgument(0);
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new CustomSave(
            $this->context(),
            $this->jsonFactory(),
            $factory,
            $this->createStub(ItemFactory::class),
            $resource,
            $this->createStub(ItemResource::class),
            $this->createStub(ItemCollectionFactory::class),
            $this->repository
        );
    }

    public function testInvalidPayload(): void
    {
        $this->content = '{"items":[]}';
        $controller = $this->controller();
        $this->repository->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame(['success' => false, 'message' => 'Invalid data'], $this->jsonPayload);
    }

    public function testNewMenuSavedWithItemsJsonAndDefaultStore(): void
    {
        $items = [['title' => 'Home', 'url' => '/']];
        $this->content = json_encode([
            'form_key' => 'abc',
            'menu' => ['title' => 'Main', 'identifier' => 'main', 'item_gap' => '5px', 'container_border' => '1px'],
            'items' => $items,
        ]);
        $controller = $this->controller();
        $this->repository->expects($this->once())->method('save')->willReturnCallback(function (Menu $menu) {
            $menu->setMenuId(21);
            $this->saved = $menu;
            return $menu;
        });
        $this->connection->expects($this->once())->method('delete')->with('panth_megamenu_store', ['menu_id = ?' => 21]);
        $this->connection->expects($this->once())->method('insert')->with('panth_megamenu_store', ['menu_id' => 21, 'store_id' => 0]);

        $controller->execute();

        $this->assertSame(['success' => true, 'message' => 'Menu saved successfully', 'menu_id' => 21], $this->jsonPayload);
        $this->assertSame('abc', $this->setParams['form_key']);
        $this->assertSame(json_encode($items), $this->saved->getItemsJson());
        $this->assertTrue($this->saved->getIsActive());
        $this->assertSame('accordion', $this->saved->getMobileLayout());
        $this->assertSame('5px', $this->saved->getData('item_gap'));
        $this->assertSame('1px', $this->saved->getData('container_border'));
        $this->assertFalse($this->saved->hasData('container_padding'));
    }

    public function testDuplicateIdentifierForNewMenu(): void
    {
        $this->content = json_encode(['menu' => ['title' => 'Main', 'identifier' => 'main']]);
        $controller = $this->controller([['menu_id' => 4, 'identifier' => 'main']]);
        $this->repository->expects($this->never())->method('save');

        $controller->execute();

        $this->assertFalse($this->jsonPayload['success']);
        $this->assertStringContainsString('Menu identifier "main" already exists', $this->jsonPayload['message']);
    }

    public function testUnknownMenuId(): void
    {
        $this->content = json_encode(['menu' => ['menu_id' => 77, 'title' => 'Main', 'identifier' => 'main']]);
        $this->controller()->execute();

        $this->assertSame(['success' => false, 'message' => 'Menu not found'], $this->jsonPayload);
    }

    public function testExistingMenuUpdatedAndErrorsReported(): void
    {
        $this->content = json_encode(['menu' => ['menu_id' => 4, 'title' => 'New', 'identifier' => 'main', 'is_active' => 0]]);
        $controller = $this->controller([['menu_id' => 4, 'identifier' => 'main', 'title' => 'Old']]);
        $this->repository->method('save')->willThrowException(new \RuntimeException('Could not save'));

        $controller->execute();

        $this->assertSame(['success' => false, 'message' => 'Could not save'], $this->jsonPayload);
    }

    public function testCsrfValidationReadsFormKeyFromJsonBody(): void
    {
        $this->content = json_encode(['form_key' => 'xyz']);
        $controller = $this->controller();
        $request = $this->request();

        $this->assertTrue($controller->validateForCsrf($request));
        $this->assertSame('xyz', $this->setParams['form_key']);
        $this->assertNull($controller->createCsrfValidationException($request));
    }

    public function testCsrfValidationKeepsExistingFormKey(): void
    {
        $this->params = ['form_key' => 'present'];
        $this->content = json_encode(['form_key' => 'other']);
        $this->formKeyValid = false;
        $controller = $this->controller();

        $this->assertFalse($controller->validateForCsrf($this->request()));
        $this->assertArrayNotHasKey('form_key', $this->setParams);
    }

    public function testAcl(): void
    {
        $this->assertAclResource($this->controller(), 'Panth_MegaMenu::menu');
    }
}
