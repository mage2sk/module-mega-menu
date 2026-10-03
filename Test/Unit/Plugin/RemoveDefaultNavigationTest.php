<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Plugin\RemoveDefaultNavigation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RemoveDefaultNavigationTest extends TestCase
{
    private MenuResource&MockObject $menuResource;
    private PageConfig&MockObject $pageConfig;
    private StoreManagerInterface&MockObject $storeManager;

    private function plugin(bool $enabled, ?string $identifier): RemoveDefaultNavigation
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('isSetFlag')->willReturn($enabled);
        $scope->method('getValue')->willReturn($identifier);
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn('1');
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->menuResource = $this->createMock(MenuResource::class);
        $this->pageConfig = $this->createMock(PageConfig::class);

        return new RemoveDefaultNavigation($scope, $this->storeManager, $this->menuResource, $this->pageConfig);
    }

    private function layout(array $present, array &$removed): Layout
    {
        $layout = $this->createStub(Layout::class);
        $layout->method('hasElement')->willReturnCallback(fn (string $name) => in_array($name, $present, true));
        $layout->method('unsetElement')->willReturnCallback(function (string $name) use (&$removed, $layout) {
            $removed[] = $name;
            return $layout;
        });

        return $layout;
    }

    public function testDisabledModuleLeavesLayoutAlone(): void
    {
        $plugin = $this->plugin(false, 'main');
        $this->menuResource->expects($this->never())->method('loadByIdentifier');
        $this->pageConfig->expects($this->never())->method('addBodyClass');
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav'], $removed));

        $this->assertSame([], $removed);
    }

    public function testBlankIdentifierLeavesLayoutAlone(): void
    {
        $plugin = $this->plugin(true, '   ');
        $this->menuResource->expects($this->never())->method('loadByIdentifier');
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav'], $removed));

        $this->assertSame([], $removed);
    }

    public function testResolvableMenuRemovesNativeNavigation(): void
    {
        $plugin = $this->plugin(true, ' main ');
        $this->menuResource->expects($this->once())->method('loadByIdentifier')->with('main', 1)
            ->willReturn(['items_json' => json_encode([['title' => 'A']])]);
        $this->pageConfig->expects($this->once())->method('addBodyClass')->with('panth-megamenu-active');
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav', 'topmenu_mobile'], $removed));

        $this->assertSame(['catalog.topnav', 'topmenu_mobile'], $removed);
    }

    public static function unresolvableProvider(): array
    {
        return [
            'no row' => [[]],
            'invalid json' => [['items_json' => '{bad']],
            'empty list' => [['items_json' => '[]']],
            'all hidden' => [['items_json' => json_encode([['show_on_frontend' => 0], ['show_on_frontend' => '']])]],
            'non array entries' => [['items_json' => json_encode(['a', 1])]],
        ];
    }

    #[DataProvider('unresolvableProvider')]
    public function testUnresolvableMenuKeepsNativeNavigation(array $row): void
    {
        $plugin = $this->plugin(true, 'main');
        $this->menuResource->method('loadByIdentifier')->willReturn($row);
        $this->pageConfig->expects($this->never())->method('addBodyClass');
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav'], $removed));

        $this->assertSame([], $removed);
    }

    public function testResolutionIsMemoisedPerStoreAndIdentifier(): void
    {
        $plugin = $this->plugin(true, 'main');
        $this->menuResource->expects($this->once())->method('loadByIdentifier')
            ->willReturn(['items_json' => json_encode([['show_on_frontend' => 1]])]);
        $removed = [];
        $layout = $this->layout(['topmenu_desktop'], $removed);

        $plugin->afterGenerateElements($layout);
        $plugin->afterGenerateElements($layout);

        $this->assertSame(['topmenu_desktop', 'topmenu_desktop'], $removed);
    }

    public function testStoreLookupFailureKeepsNativeNavigation(): void
    {
        $this->plugin(true, 'main');
        $failingStore = $this->createStub(StoreManagerInterface::class);
        $failingStore->method('getStore')->willThrowException(new \RuntimeException('no store'));
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('isSetFlag')->willReturn(true);
        $scope->method('getValue')->willReturn('main');
        $plugin = new RemoveDefaultNavigation($scope, $failingStore, $this->menuResource, $this->pageConfig);
        $this->menuResource->expects($this->never())->method('loadByIdentifier');
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav'], $removed));

        $this->assertSame([], $removed);
    }

    public function testResourceExceptionKeepsNativeNavigation(): void
    {
        $plugin = $this->plugin(true, 'main');
        $this->menuResource->method('loadByIdentifier')->willThrowException(new \RuntimeException('db'));
        $removed = [];

        $plugin->afterGenerateElements($this->layout(['catalog.topnav'], $removed));

        $this->assertSame([], $removed);
    }
}
