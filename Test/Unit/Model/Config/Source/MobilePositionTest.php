<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Config\Source;

use Panth\MegaMenu\Model\Config\Source\MobilePosition;
use PHPUnit\Framework\TestCase;

class MobilePositionTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new MobilePosition())->toOptionArray();

        $this->assertSame(['left', 'right'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new MobilePosition())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
