<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\DataObject;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Restore;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersion;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RestoreTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private array $versions = [];
    private ?Menu $savedMenu = null;
    private ?MenuVersion $savedVersion = null;
    private bool $menuSaveFails = false;

    private function controller(array $menuRows, array $versionRows, ?string $user = 'admin'): Restore
    {
        $menuResource = $this->loadingMenuResource($menuRows);
        $menuResource->method('save')->willReturnCallback(function (Menu $menu) use ($menuResource) {
            if ($this->menuSaveFails) {
                throw new \RuntimeException('deadlock');
            }
            $this->savedMenu = $menu;
            return $menuResource;
        });
        $versionResource = $this->loadingMenuResource($versionRows, VersionResource::class);
        $versionResource->method('getNextVersionNumber')->willReturn(8);
        $versionResource->method('save')->willReturnCallback(function (MenuVersion $version) use ($versionResource) {
            $this->savedVersion = $version;
            return $versionResource;
        });
        $menuFactory = $this->createStub(MenuFactory::class);
        $menuFactory->method('create')->willReturnCallback(fn () => $this->newMenu());
        $versionFactory = $this->createStub(MenuVersionFactory::class);
        $versionFactory->method('create')->willReturnCallback(fn () => $this->newVersion());
        $session = $this->createPartialMock(Session::class, ['__call']);
        $session->method('__call')->willReturnCallback(
            fn (string $name) => $name === 'getUser' && $user !== null ? new DataObject(['user_name' => $user]) : null
        );

        return new Restore($this->context(), $menuFactory, $versionFactory, $menuResource, $versionResource, $session);
    }

    public function testMissingVersionIdRedirectsToGrid(): void
    {
        $this->controller([], [])->execute();

        $this->assertSame(['Version ID is required.'], $this->messages['error']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testUnknownVersion(): void
    {
        $this->params = ['version_id' => 3];
        $this->controller([], [])->execute();

        $this->assertSame(['Version not found.'], $this->messages['error']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testDeletedMenuCannotBeRestored(): void
    {
        $this->params = ['version_id' => 3];
        $this->controller([], [['version_id' => 3, 'menu_id' => 9, 'version_number' => 2]])->execute();

        $this->assertSame(['Menu no longer exists and cannot be restored.'], $this->messages['error']);
    }

    public function testRestoreCopiesSnapshotAndRecordsNewVersion(): void
    {
        $this->params = ['version_id' => 3];
        $this->controller(
            [['menu_id' => 9, 'title' => 'Current', 'identifier' => 'main', 'items_json' => '[]']],
            [[
                'version_id' => 3, 'menu_id' => 9, 'version_number' => 2, 'title' => 'Old title',
                'identifier' => 'main', 'items_json' => '[{"a":1},{"b":2}]', 'css_class' => 'old',
                'container_bg_color' => '#111', 'item_gap' => '6px', 'is_active' => 1, 'store_ids' => '0,1',
            ]]
        )->execute();

        $this->assertSame('Old title', $this->savedMenu->getTitle());
        $this->assertSame('[{"a":1},{"b":2}]', $this->savedMenu->getItemsJson());
        $this->assertSame('#111', $this->savedMenu->getData('container_bg_color'));
        $this->assertSame(['0', '1'], $this->savedMenu->getStoreIds());
        $this->assertTrue($this->savedMenu->getIsActive());

        $this->assertSame(8, $this->savedVersion->getVersionNumber());
        $this->assertSame(9, $this->savedVersion->getMenuId());
        $this->assertSame('Restored from version #2', $this->savedVersion->getVersionComment());
        $this->assertSame('admin', $this->savedVersion->getCreatedBy());
        $this->assertSame('0,1', $this->savedVersion->getStoreIds());
        $this->assertSame(2, $this->savedVersion->getData('item_count'));
        $this->assertSame('6px', $this->savedVersion->getItemGap());

        $this->assertSame(
            ['Menu has been restored from version #2. A new version #8 has been created.'],
            $this->messages['success']
        );
        $this->assertSame(['*/*/edit', ['menu_id' => 9]], $this->redirectedTo);
    }

    public function testRestoreWithoutUserLeavesCreatedByEmpty(): void
    {
        $this->params = ['version_id' => 3];
        $this->controller(
            [['menu_id' => 9]],
            [['version_id' => 3, 'menu_id' => 9, 'version_number' => 1, 'title' => 'T', 'identifier' => 'i', 'items_json' => 'bad']],
            null
        )->execute();

        $this->assertNull($this->savedVersion->getCreatedBy());
        $this->assertSame(0, $this->savedVersion->getData('item_count'));
    }

    public function testUnexpectedErrorIsReported(): void
    {
        $this->menuSaveFails = true;
        $this->params = ['version_id' => 3];
        $this->controller(
            [['menu_id' => 9]],
            [['version_id' => 3, 'menu_id' => 9, 'version_number' => 1, 'title' => 'T', 'identifier' => 'i']]
        )->execute();

        $this->assertSame(['An error occurred while restoring the menu from version.'], $this->messages['exception']);
        $this->assertSame(['*/*/', []], $this->redirectedTo);
    }

    public function testAcl(): void
    {
        $this->assertAclResource($this->controller([], []), 'Panth_MegaMenu::menu_version_restore');
    }
}
