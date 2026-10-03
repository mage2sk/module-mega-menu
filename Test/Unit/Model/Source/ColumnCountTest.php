<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\ColumnCount;
use PHPUnit\Framework\TestCase;

class ColumnCountTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new ColumnCount())->toOptionArray();

        $this->assertSame(['1', '2', '3', '4', '5', '6'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new ColumnCount())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
