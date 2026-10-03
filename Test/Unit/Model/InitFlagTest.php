<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Panth\MegaMenu\Model\InitFlag;
use PHPUnit\Framework\TestCase;

class InitFlagTest extends TestCase
{
    public function testIsInitializedReadsDefaultScope(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnMap([
            [InitFlag::XML_PATH_INIT_FLAG, 'default', null, '1'],
            [InitFlag::XML_PATH_INIT_DATE, 'default', null, '2026-01-01 00:00:00'],
        ]);
        $flag = new InitFlag($this->createStub(WriterInterface::class), $scope, $this->createStub(DateTime::class));

        $this->assertTrue($flag->isInitialized());
        $this->assertSame('2026-01-01 00:00:00', $flag->getInitDate());
    }

    public function testNotInitializedWhenEmpty(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn(null);
        $flag = new InitFlag($this->createStub(WriterInterface::class), $scope, $this->createStub(DateTime::class));

        $this->assertFalse($flag->isInitialized());
        $this->assertNull($flag->getInitDate());
    }

    public function testMarkAsInitializedWritesFlagAndDate(): void
    {
        $saved = [];
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->exactly(2))->method('save')
            ->willReturnCallback(function ($path, $value, $scope, $scopeId) use (&$saved): void {
                $saved[] = [$path, $value, $scope, $scopeId];
            });
        $date = $this->createStub(DateTime::class);
        $date->method('gmtDate')->willReturn('2026-05-05 10:00:00');

        (new InitFlag($writer, $this->createStub(ScopeConfigInterface::class), $date))->markAsInitialized();

        $this->assertSame([
            [InitFlag::XML_PATH_INIT_FLAG, '1', 'default', 0],
            [InitFlag::XML_PATH_INIT_DATE, '2026-05-05 10:00:00', 'default', 0],
        ], $saved);
    }

    public function testResetDeletesBothPaths(): void
    {
        $deleted = [];
        $writer = $this->createMock(WriterInterface::class);
        $writer->expects($this->exactly(2))->method('delete')
            ->willReturnCallback(function ($path) use (&$deleted): void {
                $deleted[] = $path;
            });

        $flag = new InitFlag($writer, $this->createStub(ScopeConfigInterface::class), $this->createStub(DateTime::class));
        $flag->reset();

        $this->assertSame([InitFlag::XML_PATH_INIT_FLAG, InitFlag::XML_PATH_INIT_DATE], $deleted);
    }
}
