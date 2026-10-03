<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\CustomEdit;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Edit;
use Panth\MegaMenu\Controller\Adminhtml\Menu\ImportForm;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Index;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\TestCase;

class PageActionsTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    public function testIndexRendersManagerPage(): void
    {
        $controller = new Index($this->context(), $this->pageFactory());
        $controller->execute();

        $this->assertSame('Panth_MegaMenu::menu', $this->activeMenu);
        $this->assertSame(['MegaMenu Manager'], $this->titles);
        $this->assertSame('Panth_MegaMenu::menu', Index::ADMIN_RESOURCE);
        $this->assertInstanceOf(HttpGetActionInterface::class, $controller);
    }

    public function testImportFormRendersPage(): void
    {
        (new ImportForm($this->context(), $this->pageFactory()))->execute();

        $this->assertSame(['Import Menu'], $this->titles);
        $this->assertSame('Panth_MegaMenu::menu', $this->activeMenu);
    }

    public function testCustomEditTitleDependsOnMenuId(): void
    {
        (new CustomEdit($this->context(), $this->pageFactory()))->execute();
        $this->params = ['menu_id' => 3];
        (new CustomEdit($this->context(), $this->pageFactory()))->execute();

        $this->assertSame(['New Menu', 'Edit Menu'], $this->titles);
    }

    public function testCustomEditAcl(): void
    {
        $this->assertAclResource(new CustomEdit($this->context(), $this->pageFactory()), 'Panth_MegaMenu::menu');
    }

    private function editController(array $rows): Edit
    {
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new Edit($this->context(), $this->pageFactory(), $factory, $this->loadingMenuResource($rows));
    }

    public function testEditNewMenu(): void
    {
        $this->editController([])->execute();

        $this->assertSame(['New Menu'], $this->titles);
        $this->assertNull($this->redirectedTo);
    }

    public function testEditExistingMenuUsesTitle(): void
    {
        $this->params = ['menu_id' => 4];
        $this->editController([['menu_id' => 4, 'title' => 'Header']])->execute();

        $this->assertSame(['Edit Menu: Header'], $this->titles);
    }

    public function testEditMissingMenuRedirectsWithError(): void
    {
        $this->params = ['menu_id' => 9];
        $this->editController([])->execute();

        $this->assertSame(['*/*/', []], $this->redirectedTo);
        $this->assertSame(['This menu no longer exists.'], $this->messages['error']);
        $this->assertSame([], $this->titles);
    }

    public function testEditAcl(): void
    {
        $this->assertAclResource($this->editController([]), 'Panth_MegaMenu::menu');
    }
}
