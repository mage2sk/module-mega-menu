<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Menu;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Panth\MegaMenu\Helper\Config as ConfigHelper;
use Panth\MegaMenu\Model\Menu\DataProvider;
use Panth\MegaMenu\Model\MenuRepository;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataProviderTest extends TestCase
{
    use MenuModelTrait;

    private DataPersistorInterface&MockObject $persistor;

    private function provider(array $items, $persisted = null): DataProvider
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $this->persistor = $this->createMock(DataPersistorInterface::class);
        $this->persistor->method('get')->with('panth_megamenu_menu')->willReturn($persisted);

        return new DataProvider(
            'menu_form_data_source',
            'menu_id',
            'menu_id',
            $factory,
            $this->createStub(MenuRepository::class),
            $this->persistor,
            $this->createStub(LoggerInterface::class),
            $this->createStub(ConfigHelper::class)
        );
    }

    public function testMenuDataIsNormalised(): void
    {
        $menu = $this->newMenu(['menu_id' => 3, 'title' => 'Main', 'is_active' => '1', 'sort_order' => '4', 'store_ids' => '0,1']);
        $provider = $this->provider([$menu]);
        $this->persistor->expects($this->never())->method('clear');

        $data = $provider->getData();

        $this->assertSame(['0', '1'], $data[3]['store_ids']);
        $this->assertTrue($data[3]['is_active']);
        $this->assertSame(4, $data[3]['sort_order']);
        $this->assertSame('header', $data[3]['menu_type']);
    }

    public function testExistingMenuTypeIsKeptAndEmptyStoresBecomeArray(): void
    {
        $menu = $this->newMenu(['menu_id' => 3, 'menu_type' => 'footer']);

        $data = $this->provider([$menu])->getData();

        $this->assertSame('footer', $data[3]['menu_type']);
        $this->assertSame([], $data[3]['store_ids']);
        $this->assertArrayNotHasKey('is_active', $data[3]);
    }

    public function testObjectsWithoutStoreIdsAreLeftAlone(): void
    {
        $data = $this->provider([new DataObject(['id' => 9, 'title' => 'Raw'])])->getData();

        $this->assertArrayNotHasKey('store_ids', $data[9]);
    }

    public function testPersistedFormDataOverridesAndIsCleared(): void
    {
        $provider = $this->provider([], ['menu_id' => 5, 'title' => 'Unsaved']);
        $this->persistor->expects($this->once())->method('clear')->with('panth_megamenu_menu');

        $data = $provider->getData();

        $this->assertSame(['menu_id' => 5, 'title' => 'Unsaved'], $data[5]);
        $this->assertSame($data, $provider->getData());
    }

    public function testPersistedNewMenuUsesEmptyKey(): void
    {
        $data = $this->provider([], ['title' => 'Draft'])->getData();

        $this->assertSame(['' => ['title' => 'Draft']], $data);
    }

    public function testEmptyResult(): void
    {
        $this->assertSame([], $this->provider([])->getData());
    }
}
