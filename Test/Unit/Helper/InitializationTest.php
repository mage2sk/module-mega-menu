<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Flag;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;
use Panth\MegaMenu\Helper\Initialization;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InitializationTest extends TestCase
{
    use FlagTestTrait;

    private FlagResource&MockObject $flagResource;
    private WriterInterface&MockObject $writer;
    private TypeListInterface&MockObject $typeList;
    private AdapterInterface&MockObject $connection;
    private array $flags = [];

    private function helper(array $storedFlag = [], array $config = [], bool $dbFails = false): Initialization
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(fn (string $path) => $config[$path] ?? null);
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scope);

        $factory = $this->createStub(FlagFactory::class);
        $factory->method('create')->willReturnCallback(function (array $args) {
            $flag = $this->newFlag($args['data']);
            $this->flags[] = $flag;
            return $flag;
        });
        $this->flagResource = $this->createMock(FlagResource::class);
        $this->flagResource->method('load')->willReturnCallback(function (Flag $flag) use ($storedFlag) {
            $flag->addData($storedFlag);
            return $this->flagResource;
        });

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->connection->method('select')->willReturn($select);
        if ($dbFails) {
            $this->connection->method('fetchOne')->willThrowException(new \RuntimeException('db down'));
        } else {
            $this->connection->method('fetchOne')->willReturn('0');
        }
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $this->writer = $this->createMock(WriterInterface::class);
        $this->typeList = $this->createMock(TypeListInterface::class);

        return new Initialization($context, $factory, $this->flagResource, $resource, $this->writer, $this->typeList);
    }

    public function testIsInitializedReflectsFlagData(): void
    {
        $this->assertFalse($this->helper()->isInitialized());
        $this->assertTrue($this->helper(['flag_data' => '1'])->isInitialized());
    }

    public function testRunOneTimeSetupSkipsWhenAlreadyInitialized(): void
    {
        $helper = $this->helper(['flag_data' => '1']);
        $this->writer->expects($this->never())->method('save');
        $this->typeList->expects($this->never())->method('cleanType');

        $this->assertFalse($helper->runOneTimeSetup());
    }

    public function testRunOneTimeSetupWritesMissingDefaultsCleansCachesAndMarksFlag(): void
    {
        $helper = $this->helper([], ['panth_megamenu/performance/cache_enabled' => '0']);
        $saved = [];
        $this->writer->expects($this->exactly(2))->method('save')
            ->willReturnCallback(function (string $path, string $value) use (&$saved): void {
                $saved[$path] = $value;
            });
        $cleaned = [];
        $this->typeList->expects($this->exactly(4))->method('cleanType')
            ->willReturnCallback(function (string $type) use (&$cleaned): void {
                $cleaned[] = $type;
            });
        $this->flagResource->expects($this->once())->method('save');

        $this->assertTrue($helper->runOneTimeSetup());
        $this->assertSame([
            'panth_megamenu/general/mobile_breakpoint' => '768',
            'panth_megamenu/performance/cache_lifetime' => '3600',
        ], $saved);
        $this->assertSame(['config', 'layout', 'block_html', 'full_page'], $cleaned);
        $marked = end($this->flags);
        $this->assertSame(Initialization::FLAG_CODE, $marked->getFlagCode());
        $this->assertSame(1, $marked->getFlagData());
    }

    public function testRunOneTimeSetupReturnsFalseOnFailure(): void
    {
        $helper = $this->helper([], [], true);
        $this->flagResource->expects($this->never())->method('save');

        $this->assertFalse($helper->runOneTimeSetup());
    }

    public function testResetDeletesExistingFlag(): void
    {
        $helper = $this->helper(['flag_id' => 5]);
        $this->flagResource->expects($this->once())->method('delete');

        $helper->resetInitialization();
    }

    public function testResetSkipsMissingFlag(): void
    {
        $helper = $this->helper();
        $this->flagResource->expects($this->never())->method('delete');

        $helper->resetInitialization();
    }
}
