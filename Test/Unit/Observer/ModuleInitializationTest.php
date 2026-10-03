<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Panth\MegaMenu\Model\InitFlag;
use Panth\MegaMenu\Observer\ModuleInitialization;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ModuleInitializationTest extends TestCase
{
    public function testMarksInitializedOnFirstRun(): void
    {
        $flag = $this->createMock(InitFlag::class);
        $flag->method('isInitialized')->willReturn(false);
        $flag->expects($this->once())->method('markAsInitialized');

        (new ModuleInitialization($flag, $this->createStub(LoggerInterface::class)))->execute(new Observer());
    }

    public function testDoesNothingWhenAlreadyInitialized(): void
    {
        $flag = $this->createMock(InitFlag::class);
        $flag->method('isInitialized')->willReturn(true);
        $flag->expects($this->never())->method('markAsInitialized');

        (new ModuleInitialization($flag, $this->createStub(LoggerInterface::class)))->execute(new Observer());
    }

    public function testWriteFailureIsSwallowed(): void
    {
        $flag = $this->createMock(InitFlag::class);
        $flag->method('isInitialized')->willReturn(false);
        $flag->expects($this->once())->method('markAsInitialized')->willThrowException(new \RuntimeException('ro'));

        $this->assertNull((new ModuleInitialization($flag, $this->createStub(LoggerInterface::class)))->execute(new Observer()));
    }
}
