<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Panth\MegaMenu\Setup\Patch\Data\AddDefaultStoreRelations;
use Panth\MegaMenu\Test\Unit\Model\ResourceModel\DbStubTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class AddDefaultStoreRelationsTest extends TestCase
{
    use DbStubTrait;

    private function patch(array $menus, array $relationCounts, bool $fail = false): AddDefaultStoreRelations
    {
        $connection = $this->connection();
        if ($fail) {
            $connection->method('fetchAll')->willThrowException(new \RuntimeException('no table'));
        } else {
            $connection->method('fetchAll')->willReturn($menus);
        }
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(...($relationCounts ?: [false]));
        $connection->expects($this->once())->method('startSetup');
        $connection->expects($this->once())->method('endSetup');
        $setup = $this->createStub(ModuleDataSetupInterface::class);
        $setup->method('getConnection')->willReturn($connection);
        $setup->method('getTable')->willReturnArgument(0);

        return new AddDefaultStoreRelations($setup, $this->createStub(LoggerInterface::class));
    }

    public function testMenusWithoutRelationsGetAllStoreViews(): void
    {
        $patch = $this->patch([['menu_id' => 1], ['menu_id' => 2], ['menu_id' => 3]], ['0', '2', 0]);

        $this->assertSame($patch, $patch->apply());
        $this->assertSame([
            ['insert', ['panth_megamenu_store', ['menu_id' => 1, 'store_id' => 0]]],
            ['insert', ['panth_megamenu_store', ['menu_id' => 3, 'store_id' => 0]]],
        ], $this->writes);
    }

    public function testFailureStillEndsSetup(): void
    {
        $patch = $this->patch([], [], true);

        $this->assertSame($patch, $patch->apply());
        $this->assertSame([], $this->writes);
    }

    public function testMetadata(): void
    {
        $this->assertSame([], AddDefaultStoreRelations::getDependencies());
        $patch = new AddDefaultStoreRelations(
            $this->createStub(ModuleDataSetupInterface::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->assertSame([], $patch->getAliases());
    }
}
