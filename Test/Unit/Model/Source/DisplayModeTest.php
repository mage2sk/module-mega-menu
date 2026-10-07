<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\DisplayMode;
use PHPUnit\Framework\TestCase;

class DisplayModeTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new DisplayMode())->toOptionArray();

        $this->assertSame([DisplayMode::MODE_DROPDOWN, DisplayMode::MODE_MEGA, DisplayMode::MODE_FLYOUT], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new DisplayMode())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
