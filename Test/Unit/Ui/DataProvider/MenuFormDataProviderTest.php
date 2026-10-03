<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Ui\DataProvider\MenuFormDataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class MenuFormDataProviderTest extends TestCase
{
    use MenuModelTrait;

    private DataPersistorInterface&MockObject $persistor;

    private function provider(array $items, $persisted = null, bool $fail = false): MenuFormDataProvider
    {
        $collection = $this->createStub(Collection::class);
        if ($fail) {
            $collection->method('getItems')->willThrowException(new \RuntimeException('db'));
        } else {
            $collection->method('getItems')->willReturn($items);
        }
        $collection->method('getNewEmptyItem')->willReturnCallback(fn () => $this->newMenu());
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $this->persistor = $this->createMock(DataPersistorInterface::class);
        $this->persistor->method('get')->willReturn($persisted);

        return new MenuFormDataProvider('f', 'menu_id', 'menu_id', $factory, $this->persistor, $this->createStub(LoggerInterface::class));
    }

    public function testMissingItemsJsonDefaultsToEmptyArray(): void
    {
        $data = $this->provider([
            new DataObject(['id' => 1, 'title' => 'A']),
            new DataObject(['id' => 2, 'items_json' => '[{"x":1}]']),
            new DataObject(['id' => 3, 'items_json' => null]),
        ])->getData();

        $this->assertSame('[]', $data[1]['items_json']);
        $this->assertSame('[{"x":1}]', $data[2]['items_json']);
        $this->assertSame('[]', $data[3]['items_json']);
    }

    public function testPersistedDataIsMergedAndCleared(): void
    {
        $provider = $this->provider([], ['menu_id' => 7, 'title' => 'Draft']);
        $this->persistor->expects($this->once())->method('clear')->with('panth_megamenu_menu');

        $data = $provider->getData();

        $this->assertSame(['menu_id' => 7, 'title' => 'Draft', 'items_json' => '[]'], $data[7]);
        $this->assertSame($data, $provider->getData());
    }

    public function testErrorsYieldEmptyData(): void
    {
        $this->assertSame([], $this->provider([], null, true)->getData());
    }
}
