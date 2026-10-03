<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\Target;
use PHPUnit\Framework\TestCase;

class TargetTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new Target())->toOptionArray();

        $this->assertSame(['_self', '_blank'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new Target())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
