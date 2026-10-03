<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\AnimationType;
use PHPUnit\Framework\TestCase;

class AnimationTypeTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new AnimationType())->toOptionArray();

        $this->assertSame(['none', 'fade', 'slide', 'slide-up', 'zoom', 'bounce', 'flip', 'rotate'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new AnimationType())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
