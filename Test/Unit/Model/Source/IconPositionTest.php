<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\IconPosition;
use PHPUnit\Framework\TestCase;

class IconPositionTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new IconPosition())->toOptionArray();

        $this->assertSame(['left', 'right', 'top', 'bottom'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new IconPosition())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
