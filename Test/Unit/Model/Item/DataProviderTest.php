<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Item;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\Config as ConfigHelper;
use Panth\MegaMenu\Model\Item\DataProvider;
use Panth\MegaMenu\Model\ItemRepository;
use Panth\MegaMenu\Model\ResourceModel\Item\Collection;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class DataProviderTest extends TestCase
{
    private DataPersistorInterface&MockObject $persistor;

    private function provider(array $items, $persisted = null, bool $storeFails = false): DataProvider
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $this->persistor = $this->createMock(DataPersistorInterface::class);
        $this->persistor->method('get')->willReturn($persisted);
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        if ($storeFails) {
            $storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));
        } else {
            $storeManager->method('getStore')->willReturn($store);
        }

        return new DataProvider(
            'item_form',
            'item_id',
            'item_id',
            $factory,
            $this->createStub(ItemRepository::class),
            $this->persistor,
            $this->createStub(RequestInterface::class),
            $storeManager,
            $this->createStub(LoggerInterface::class),
            $this->createStub(ConfigHelper::class)
        );
    }

    public function testCastsAndDefaults(): void
    {
        $item = new DataObject([
            'id' => 4, 'is_active' => '1', 'position' => '3', 'level' => '2', 'show_children' => '0',
        ]);

        $data = $this->provider([$item])->getData()[4];

        $this->assertTrue($data['is_active']);
        $this->assertSame(3, $data['position']);
        $this->assertSame(2, $data['level']);
        $this->assertFalse($data['show_children']);
        $this->assertSame('custom_url', $data['item_type']);
        $this->assertSame(1, $data['columns']);
        $this->assertSame('_self', $data['target']);
        $this->assertArrayNotHasKey('open_in_new_tab', $data);
    }

    public function testOpenInNewTabForcesBlankTarget(): void
    {
        $data = $this->provider([new DataObject(['id' => 1, 'open_in_new_tab' => '1', 'target' => '_self', 'columns' => '3'])])->getData()[1];

        $this->assertSame('_blank', $data['target']);
        $this->assertTrue($data['open_in_new_tab']);
        $this->assertSame(3, $data['columns']);
    }

    public function testBlankTargetSetsNewTabFlag(): void
    {
        $data = $this->provider([new DataObject(['id' => 1, 'target' => '_blank', 'item_type' => 'category'])])->getData()[1];

        $this->assertTrue($data['open_in_new_tab']);
        $this->assertSame('category', $data['item_type']);
    }

    public function testImageIsConvertedForUploader(): void
    {
        $data = $this->provider([new DataObject(['id' => 1, 'image' => '/icons/a.png'])])->getData()[1];

        $this->assertSame([[
            'name' => 'a.png',
            'url' => 'https://shop.test/media/panth/megamenu/item/icons/a.png',
            'file' => '/icons/a.png',
        ]], $data['image']);
    }

    public function testImageUrlEmptyWhenStoreUnavailable(): void
    {
        $data = $this->provider([new DataObject(['id' => 1, 'image' => 'a.png'])], null, true)->getData()[1];

        $this->assertSame('', $data['image'][0]['url']);
    }

    public function testArrayImageIsLeftAsIs(): void
    {
        $image = [['name' => 'x.png']];
        $data = $this->provider([new DataObject(['id' => 1, 'image' => $image])])->getData()[1];

        $this->assertSame($image, $data['image']);
    }

    public function testPersistedDataIsAddedUnderEmptyKeyAndCleared(): void
    {
        $provider = $this->provider([], ['title' => 'Draft']);
        $this->persistor->expects($this->once())->method('clear')->with('panth_megamenu_item');

        $this->assertSame(['' => ['title' => 'Draft']], $provider->getData());
        $this->assertSame(['' => ['title' => 'Draft']], $provider->getData());
    }
}
