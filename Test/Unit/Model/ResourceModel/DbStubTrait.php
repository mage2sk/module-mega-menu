<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;

trait DbStubTrait
{
    private array $selectCalls = [];
    private array $writes = [];

    private function select(): Select
    {
        $select = $this->createStub(Select::class);
        foreach (['from', 'where', 'joinLeft', 'limit', 'order', 'orWhere'] as $method) {
            $select->method($method)->willReturnCallback(function (...$args) use ($select, $method) {
                while ($args !== [] && end($args) === null) {
                    array_pop($args);
                }
                $this->selectCalls[] = [$method, $args];
                return $select;
            });
        }

        return $select;
    }

    private function connection(): AdapterInterface&MockObject
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturnCallback(fn () => $this->select());
        foreach (['delete', 'insert', 'insertMultiple', 'update'] as $method) {
            $connection->method($method)->willReturnCallback(function (...$args) use ($method) {
                $this->writes[] = [$method, $args];
                return 1;
            });
        }

        return $connection;
    }

    private function resource(string $class, AdapterInterface $connection, string $mainTable): object
    {
        $resource = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getMainTable', 'getTable'])
            ->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn($mainTable);
        $resource->method('getTable')->willReturnArgument(0);

        return $resource;
    }

    private function invoke(object $object, string $method, ...$args): mixed
    {
        return (new \ReflectionMethod($object, $method))->invoke($object, ...$args);
    }

    private function whereCalls(): array
    {
        return array_values(array_map(
            fn ($call) => $call[1],
            array_filter($this->selectCalls, fn ($call) => $call[0] === 'where')
        ));
    }
}
