<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\Alignment;
use PHPUnit\Framework\TestCase;

class AlignmentTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new Alignment())->toOptionArray();

        $this->assertSame(['left', 'center', 'right', 'full'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new Alignment())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
