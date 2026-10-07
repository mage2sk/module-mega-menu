<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Controller\Adminhtml\Menu\VersionView;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\TestCase;

class VersionViewTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private function controller(array $versionRows, array $menuRows = [], ?VersionResource $versionResource = null): VersionView
    {
        $versionFactory = $this->createStub(MenuVersionFactory::class);
        $versionFactory->method('create')->willReturnCallback(fn () => $this->newVersion());
        $menuFactory = $this->createStub(MenuFactory::class);
        $menuFactory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new VersionView(
            $this->context(),
            $this->jsonFactory(),
            $versionFactory,
            $versionResource ?? $this->loadingMenuResource($versionRows, VersionResource::class),
            $menuFactory,
            $this->loadingMenuResource($menuRows, MenuResource::class)
        );
    }

    private function items(int $count): string
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = ['id' => $i, 'title' => 'Item ' . $i, 'children' => $i === 1 ? [['id' => 99]] : []];
        }

        return json_encode($items);
    }

    public function testRequiresVersionId(): void
    {
        $this->controller([])->execute();

        $this->assertFalse($this->jsonPayload['success']);
        $this->assertSame('Version ID is required.', (string) $this->jsonPayload['message']);
    }

    public function testUnknownVersion(): void
    {
        $this->params = ['version_id' => 4];
        $this->controller([])->execute();

        $this->assertSame(['success' => false, 'message' => 'Version not found.'], $this->jsonPayload);
    }

    public function testVersionDetailsWithTruncatedPreview(): void
    {
        $this->params = ['version_id' => 4];
        $this->controller([[
            'version_id' => 4, 'menu_id' => 2, 'version_number' => 7, 'title' => 'Snap',
            'items_json' => $this->items(12), 'created_by' => 'jane',
        ]])->execute();

        $version = $this->jsonPayload['version'];
        $this->assertTrue($this->jsonPayload['success']);
        $this->assertSame(7, $version['version_number']);
        $this->assertSame(12, $version['item_count']);
        $this->assertCount(11, $version['items_preview']);
        $this->assertSame(
            ['id' => 1, 'title' => 'Item 1', 'type' => 'link', 'level' => 0, 'has_children' => true],
            $version['items_preview'][0]
        );
        $this->assertFalse($version['items_preview'][1]['has_children']);
        $this->assertSame(['title' => '... and 2 more'], $version['items_preview'][10]);
        $this->assertArrayNotHasKey('current_version', $this->jsonPayload);
    }

    public function testInvalidItemsJsonYieldsEmptyPreview(): void
    {
        $this->params = ['version_id' => 4];
        $this->controller([['version_id' => 4, 'menu_id' => 2, 'items_json' => 'oops']])->execute();

        $this->assertSame(0, $this->jsonPayload['version']['item_count']);
        $this->assertSame([], $this->jsonPayload['version']['items_preview']);
    }

    public function testComparisonListsDifferences(): void
    {
        $this->params = ['version_id' => 4, 'compare' => '1'];
        $this->controller(
            [['version_id' => 4, 'menu_id' => 2, 'title' => 'Old', 'identifier' => 'main', 'is_active' => 1, 'items_json' => '[1,2]']],
            [['menu_id' => 2, 'title' => 'New', 'identifier' => 'main', 'is_active' => '1', 'items_json' => '[1,2,3]']]
        )->execute();

        $this->assertSame('New', $this->jsonPayload['current_version']['title']);
        $this->assertSame([
            'title' => ['version' => 'Old', 'current' => 'New'],
            'items' => ['version_count' => 2, 'current_count' => 3, 'changed' => true],
        ], $this->jsonPayload['differences']);
    }

    public function testComparisonWithIdenticalMenuHasNoDifferences(): void
    {
        $row = ['title' => 'Same', 'identifier' => 'main', 'items_json' => '[]'];
        $this->params = ['version_id' => 4, 'compare' => 1];
        $this->controller([['version_id' => 4, 'menu_id' => 2] + $row], [['menu_id' => 2] + $row])->execute();

        $this->assertSame([], $this->jsonPayload['differences']);
    }

    public function testComparisonSkippedWhenMenuDeleted(): void
    {
        $this->params = ['version_id' => 4, 'compare' => 1];
        $this->controller([['version_id' => 4, 'menu_id' => 2, 'items_json' => '[]']])->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $this->assertArrayNotHasKey('differences', $this->jsonPayload);
    }

    public function testGenericErrorMessage(): void
    {
        $this->params = ['version_id' => 4];
        $resource = $this->createStub(VersionResource::class);
        $resource->method('load')->willThrowException(new \RuntimeException('sql'));

        $controller = $this->controller([], [], $resource);
        $controller->execute();

        $this->assertSame('An error occurred while loading version details.', (string) $this->jsonPayload['message']);
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu_version_view');
    }
}
