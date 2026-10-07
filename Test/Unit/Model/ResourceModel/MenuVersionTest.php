<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel;

use Panth\MegaMenu\Model\ResourceModel\MenuVersion;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuVersionTest extends TestCase
{
    use DbStubTrait;

    private function versionResource($fetchOne, array $fetchAll = []): MenuVersion
    {
        $connection = $this->connection();
        $connection->method('fetchOne')->willReturn($fetchOne);
        $connection->method('fetchAll')->willReturn($fetchAll);

        return $this->resource(MenuVersion::class, $connection, 'panth_megamenu_menu_version');
    }

    public function testNextVersionNumber(): void
    {
        $this->assertSame(1, $this->versionResource(null)->getNextVersionNumber(3));
        $this->assertSame(1, $this->versionResource(false)->getNextVersionNumber(3));
        $this->assertSame(8, $this->versionResource('7')->getNextVersionNumber(3));
        $this->assertSame(['menu_id = ?', 3], $this->whereCalls()[0]);
    }

    public function testVersionsByMenuIdOrdersNewestFirst(): void
    {
        $rows = [['version_id' => 2], ['version_id' => 1]];

        $this->assertSame($rows, $this->versionResource(null, $rows)->getVersionsByMenuId(4));
        $this->assertContains(['order', ['version_number DESC']], $this->selectCalls);
    }
}
