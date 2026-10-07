<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Version;

use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Version\Delete;
use Panth\MegaMenu\Controller\Adminhtml\Version\Export;
use Panth\MegaMenu\Controller\Adminhtml\Version\Restore;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class VersionActionsTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private const VERSION = [
        'version_id' => 3,
        'menu_id' => 9,
        'version_number' => 4,
        'title' => 'Snapshot',
        'identifier' => 'main',
        'items_json' => '[{"t":1}]',
        'is_active' => 0,
        'sort_order' => '5',
        'description' => 'desc',
        'container_padding' => '2px',
        'created_by' => 'jane',
    ];

    private function versionFactory(): MenuVersionFactory
    {
        $factory = $this->createStub(MenuVersionFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->newVersion());

        return $factory;
    }

    public function testDeleteRequiresId(): void
    {
        (new Delete($this->context(), $this->versionFactory(), $this->createStub(VersionResource::class)))->execute();

        $this->assertSame(['Version ID is required.'], $this->messages['error']);
        $this->assertSame(['panth_menu/menu/', []], $this->redirectedTo);
    }

    public function testDeleteUnknownVersion(): void
    {
        $this->params = ['version_id' => 3];
        (new Delete($this->context(), $this->versionFactory(), $this->loadingMenuResource([], VersionResource::class)))->execute();

        $this->assertSame(['Version not found.'], $this->messages['error']);
        $this->assertSame(['panth_menu/menu/', []], $this->redirectedTo);
    }

    public function testDeleteRemovesVersionAndReturnsToMenu(): void
    {
        $this->params = ['version_id' => 3];
        $deleted = [];
        $resource = $this->loadingMenuResource([self::VERSION], VersionResource::class);
        $resource->method('delete')->willReturnCallback(function ($version) use (&$deleted, $resource) {
            $deleted[] = $version->getVersionId();
            return $resource;
        });

        (new Delete($this->context(), $this->versionFactory(), $resource))->execute();

        $this->assertSame([3], $deleted);
        $this->assertSame(['Version #4 has been deleted.'], $this->messages['success']);
        $this->assertSame(['panth_menu/menu/edit', ['menu_id' => 9]], $this->redirectedTo);
    }

    public function testDeleteUnexpectedError(): void
    {
        $this->params = ['version_id' => 3];
        $resource = $this->loadingMenuResource([self::VERSION], VersionResource::class);
        $resource->method('delete')->willThrowException(new \RuntimeException('fk'));

        (new Delete($this->context(), $this->versionFactory(), $resource))->execute();

        $this->assertSame(['An error occurred while deleting the version.'], $this->messages['exception']);
        $this->assertSame('Panth_MegaMenu::menu', Delete::ADMIN_RESOURCE);
    }

    public function testExportValidation(): void
    {
        $controller = new Export($this->context(), $this->versionFactory(), $this->loadingMenuResource([], VersionResource::class));
        $controller->execute();
        $this->assertSame(['Version ID is required'], $this->messages['error']);

        $this->params = ['version_id' => 7];
        $controller->execute();
        $this->assertSame(['Version ID is required', 'Version not found'], $this->messages['error']);
        $this->assertSame(['*/*/index', []], $this->redirectedTo);
    }

    public function testExportWritesDownload(): void
    {
        $this->params = ['version_id' => 3];
        $controller = new Export($this->context(), $this->versionFactory(), $this->loadingMenuResource([self::VERSION], VersionResource::class));

        $this->assertSame($this->response, $controller->execute());

        $this->assertMatchesRegularExpression(
            '/^attachment; filename="menu_main_version_4_\d{8}_\d{6}\.json"$/',
            $this->headers['Content-Disposition']
        );
        $data = json_decode($this->body, true);
        $this->assertSame(9, $data['menu']['menu_id']);
        $this->assertSame('5', $data['menu']['sort_order']);
        $this->assertSame('desc', $data['menu']['description']);
        $this->assertSame('2px', $data['menu']['container_padding']);
        $this->assertSame([['t' => 1]], $data['items']);
        $this->assertSame('jane', $data['version_info']['created_by']);
        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }

    public function testExportErrorRedirects(): void
    {
        $this->params = ['version_id' => 3];
        $resource = $this->createStub(VersionResource::class);
        $resource->method('load')->willThrowException(new \RuntimeException('down'));

        (new Export($this->context(), $this->versionFactory(), $resource))->execute();

        $this->assertSame(['Error: down'], $this->messages['error']);
    }

    private function restore(array $menuRows, array $versionRows, ?MenuRepositoryInterface $repository = null): Restore
    {
        $menuFactory = $this->createStub(MenuFactory::class);
        $menuFactory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new Restore(
            $this->context(),
            $menuFactory,
            $this->versionFactory(),
            $this->loadingMenuResource($menuRows),
            $this->loadingMenuResource($versionRows, VersionResource::class),
            $repository ?? $this->createStub(MenuRepositoryInterface::class)
        );
    }

    public function testRestoreValidation(): void
    {
        $this->restore([], [])->execute();
        $this->assertSame(['Version ID is required.'], $this->messages['error']);

        $this->params = ['version_id' => 3];
        $this->restore([], [])->execute();
        $this->restore([], [self::VERSION])->execute();
        $this->assertSame([
            'Version ID is required.',
            'Version not found.',
            'Menu no longer exists and cannot be restored.',
        ], $this->messages['error']);
        $this->assertSame(['panth_menu/menu/', []], $this->redirectedTo);
    }

    public function testRestoreSavesThroughRepositoryWithComment(): void
    {
        $this->params = ['version_id' => 3];
        $saved = null;
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->once())->method('save')->willReturnCallback(function (Menu $menu) use (&$saved) {
            $saved = $menu;
            return $menu;
        });

        $this->restore([['menu_id' => 9, 'title' => 'Now', 'is_active' => 1]], [self::VERSION], $repository)->execute();

        $this->assertSame('Snapshot', $saved->getTitle());
        $this->assertFalse($saved->getIsActive());
        $this->assertSame(5, $saved->getSortOrder());
        $this->assertSame('desc', $saved->getDescription());
        $this->assertSame('2px', $saved->getData('container_padding'));
        $this->assertSame('Restored from version #4', $saved->getData('version_comment'));
        $this->assertSame(['Menu has been restored from version #4.'], $this->messages['success']);
        $this->assertSame(['panth_menu/menu/edit', ['menu_id' => 9]], $this->redirectedTo);
    }

    public function testRestoreUnexpectedError(): void
    {
        $this->params = ['version_id' => 3];
        $repository = $this->createStub(MenuRepositoryInterface::class);
        $repository->method('save')->willThrowException(new \RuntimeException('x'));

        $this->restore([['menu_id' => 9]], [self::VERSION], $repository)->execute();

        $this->assertSame(['An error occurred while restoring the menu from version.'], $this->messages['exception']);
        $this->assertAclResource($this->restore([], []), 'Panth_MegaMenu::menu');
    }
}
