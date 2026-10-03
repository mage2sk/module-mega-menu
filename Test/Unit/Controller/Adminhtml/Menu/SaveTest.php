<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Save;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SaveTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private DataPersistorInterface&MockObject $persistor;
    private MenuRepositoryInterface&MockObject $repository;
    private AdapterInterface&MockObject $connection;
    private ?Menu $savedMenu = null;

    private function controller(array $rows = []): Save
    {
        $this->persistor = $this->createMock(DataPersistorInterface::class);
        $this->repository = $this->createMock(MenuRepositoryInterface::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $resource = $this->loadingMenuResource($rows);
        $resource->method('getConnection')->willReturn($this->connection);
        $resource->method('getTable')->willReturnArgument(0);
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new Save($this->context(), $this->persistor, $factory, $resource, $this->repository);
    }

    public function testEmptyPostRedirectsToGrid(): void
    {
        $controller = $this->controller();
        $this->repository->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testNewMenuIsSavedWithDefaultsAndStoreRelation(): void
    {
        $this->postValue = ['title' => 'Main', 'identifier' => 'main', 'is_active' => '1'];
        $controller = $this->controller();
        $this->repository->expects($this->once())->method('save')
            ->willReturnCallback(function (Menu $menu) {
                $menu->setId(11);
                $this->savedMenu = $menu;
                return $menu;
            });
        $this->connection->expects($this->once())->method('delete')
            ->with('panth_megamenu_store', ['menu_id = ?' => 11]);
        $this->connection->expects($this->once())->method('insert')
            ->with('panth_megamenu_store', ['menu_id' => 11, 'store_id' => 0]);
        $this->persistor->expects($this->once())->method('clear')->with('panth_megamenu_menu');

        $controller->execute();

        $this->assertSame('Main', $this->savedMenu->getTitle());
        $this->assertSame('[]', $this->savedMenu->getItemsJson());
        $this->assertSame(0, $this->savedMenu->getSortOrder());
        $this->assertSame('', $this->savedMenu->getData('container_box_shadow'));
        $this->assertSame(['The menu has been saved.'], $this->messages['success']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testBackParamReturnsToEditPage(): void
    {
        $this->postValue = ['title' => 'Main', 'identifier' => 'main'];
        $this->params = ['menu_id' => '4', 'back' => 'edit'];
        $controller = $this->controller([['menu_id' => 4, 'title' => 'Old']]);
        $this->repository->expects($this->once())->method('save')->willReturnArgument(0);

        $controller->execute();

        $this->assertSame(['*/*/edit', ['menu_id' => '4']], $this->redirectedTo);
    }

    public function testExistingMenuThatVanishedRedirectsWithError(): void
    {
        $this->postValue = ['title' => 'Main'];
        $this->params = ['menu_id' => '8'];
        $controller = $this->controller();
        $this->repository->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame(['This menu no longer exists.'], $this->messages['error']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testDuplicateIdentifierPersistsFormData(): void
    {
        $this->postValue = ['title' => 'Main', 'identifier' => 'main'];
        $controller = $this->controller([['menu_id' => 2, 'identifier' => 'main']]);
        $this->repository->expects($this->never())->method('save');
        $this->persistor->expects($this->once())->method('set')->with('panth_megamenu_menu', $this->postValue);

        $controller->execute();

        $this->assertSame(
            ['Menu identifier "main" already exists. Please use a different identifier.'],
            $this->messages['error']
        );
        $this->assertSame(['*/*/edit', ['menu_id' => null]], $this->redirectedTo);
    }

    public function testUnexpectedErrorAddsExceptionMessage(): void
    {
        $this->postValue = ['title' => 'Main', 'identifier' => 'main'];
        $controller = $this->controller();
        $this->repository->method('save')->willThrowException(new \RuntimeException('db'));
        $this->persistor->expects($this->once())->method('set');

        $controller->execute();

        $this->assertSame(['Something went wrong while saving the menu.'], $this->messages['exception']);
        $this->assertSame('*/*/edit', $this->redirectedTo[0]);
    }

    public function testAcl(): void
    {
        $this->assertAclResource($this->controller(), 'Panth_MegaMenu::menu');
    }
}
