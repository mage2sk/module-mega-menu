<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Ui\Component\MassAction\Filter;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\MassDelete;
use Panth\MegaMenu\Controller\Adminhtml\Menu\MassDisable;
use Panth\MegaMenu\Controller\Adminhtml\Menu\MassEnable;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MassActionsTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private MenuRepositoryInterface&MockObject $repository;

    private array $saved = [];

    private array $deleted = [];

    private function deps(array $menus, bool $fail = false): array
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($menus);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $filter = $this->createStub(Filter::class);
        if ($fail) {
            $filter->method('getCollection')->willThrowException(new \RuntimeException('Please select menus.'));
        } else {
            $filter->method('getCollection')->willReturn($collection);
        }
        $this->repository = $this->createMock(MenuRepositoryInterface::class);
        $this->repository->method('save')->willReturnCallback(function ($menu) {
            $this->saved[] = [(int) $menu->getMenuId(), (int) $menu->getIsActive()];
            return $menu;
        });
        $this->repository->method('delete')->willReturnCallback(function ($menu) {
            $this->deleted[] = (int) $menu->getMenuId();
            return true;
        });

        return [$this->context(), $filter, $factory, $this->repository];
    }

    private function menus(): array
    {
        return [
            $this->newMenu(['menu_id' => 2, 'is_active' => 1]),
            $this->newMenu(['menu_id' => 3, 'is_active' => 0]),
        ];
    }

    public function testMassDisableSavesOnlyActiveMenus(): void
    {
        (new MassDisable(...$this->deps($this->menus())))->execute();

        $this->assertSame([[2, 0]], $this->saved);
        $this->assertSame(['A total of 2 menu(s) have been disabled.'], $this->messages['success']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testMassEnableSavesOnlyInactiveMenus(): void
    {
        (new MassEnable(...$this->deps($this->menus())))->execute();

        $this->assertSame([[3, 1]], $this->saved);
        $this->assertSame(['A total of 2 menu(s) have been enabled.'], $this->messages['success']);
    }

    public function testMassDeleteRemovesEverySelectedMenu(): void
    {
        (new MassDelete(...$this->deps($this->menus())))->execute();

        $this->assertSame([2, 3], $this->deleted);
        $this->assertSame(['A total of 2 menu(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('Panth_MegaMenu::menu_delete', MassDelete::ADMIN_RESOURCE);
        $this->assertSame('Panth_MegaMenu::menu_save', MassEnable::ADMIN_RESOURCE);
    }

    public function testErrorsAreReportedAndRedirectToGrid(): void
    {
        (new MassDelete(...$this->deps([], true)))->execute();

        $this->assertSame([], $this->deleted);
        $this->assertSame(['Please select menus.'], $this->messages['error']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }
}
