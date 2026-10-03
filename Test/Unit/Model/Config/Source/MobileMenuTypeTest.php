<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Config\Source;

use Panth\MegaMenu\Model\Config\Source\MobileMenuType;
use PHPUnit\Framework\TestCase;

class MobileMenuTypeTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new MobileMenuType())->toOptionArray();

        $this->assertSame(['slide', 'overlay', 'dropdown', 'accordion'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new MobileMenuType())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
