<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\FontWeight;
use PHPUnit\Framework\TestCase;

class FontWeightTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new FontWeight())->toOptionArray();

        $this->assertSame(['', '100', '200', '300', '400', '500', '600', '700', '800', '900'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new FontWeight())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
