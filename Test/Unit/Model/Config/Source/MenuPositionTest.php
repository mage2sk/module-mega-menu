<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Config\Source;

use Panth\MegaMenu\Model\Config\Source\MenuPosition;
use PHPUnit\Framework\TestCase;

class MenuPositionTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new MenuPosition())->toOptionArray();

        $this->assertSame(['left', 'right'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new MenuPosition())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
