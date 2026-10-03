<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Panth\MegaMenu\Model\MenuVersion;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;
use PHPUnit\Framework\TestCase;

class MenuVersionTest extends TestCase
{
    private function version(array $data = []): MenuVersion
    {
        return new MenuVersion(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $this->resource(),
            null,
            $data
        );
    }

    private function resource(): VersionResource
    {
        $resource = $this->createStub(VersionResource::class);
        $resource->method('getIdFieldName')->willReturn('version_id');

        return $resource;
    }

    public function testIdsAreCastOrNull(): void
    {
        $empty = $this->version();
        $this->assertNull($empty->getVersionId());
        $this->assertNull($empty->getMenuId());
        $this->assertNull($empty->getVersionNumber());

        $full = $this->version(['version_id' => '4', 'menu_id' => '2', 'version_number' => '11']);
        $this->assertSame(4, $full->getVersionId());
        $this->assertSame(2, $full->getMenuId());
        $this->assertSame(11, $full->getVersionNumber());
    }

    public function testStoreIdsStayAsString(): void
    {
        $version = $this->version();
        $version->setStoreIds('0,1');

        $this->assertSame('0,1', $version->getStoreIds());
    }

    public function testContainerStylingRoundTrip(): void
    {
        $version = $this->version();
        $result = $version->setContainerBgColor('#fff')
            ->setContainerPadding('10px')
            ->setContainerMargin('0')
            ->setContainerMaxWidth('1200px')
            ->setContainerBorder('1px solid')
            ->setContainerBorderRadius('4px')
            ->setContainerBoxShadow('none')
            ->setItemGap('8px')
            ->setIsActive(true)
            ->setVersionComment('initial')
            ->setCreatedBy('admin');

        $this->assertSame($version, $result);
        $this->assertSame('#fff', $version->getContainerBgColor());
        $this->assertSame('10px', $version->getContainerPadding());
        $this->assertSame('0', $version->getContainerMargin());
        $this->assertSame('1200px', $version->getContainerMaxWidth());
        $this->assertSame('1px solid', $version->getContainerBorder());
        $this->assertSame('4px', $version->getContainerBorderRadius());
        $this->assertSame('none', $version->getContainerBoxShadow());
        $this->assertSame('8px', $version->getItemGap());
        $this->assertTrue($version->getIsActive());
        $this->assertSame('initial', $version->getVersionComment());
        $this->assertSame('admin', $version->getCreatedBy());
    }
}
