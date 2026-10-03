<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\MegaMenu\Helper\Data;

trait ConfigHelperTrait
{
    private function dataHelper(array $values = []): Data
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(fn (string $path) => $values[$path] ?? null);
        $scope->method('isSetFlag')->willReturnCallback(fn (string $path) => !empty($values[$path]));
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scope);

        return new Data($context, $this->createStub(TypeListInterface::class), $this->createStub(Pool::class));
    }
}
