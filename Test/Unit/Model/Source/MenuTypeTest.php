<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\MenuType;
use PHPUnit\Framework\TestCase;

class MenuTypeTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new MenuType())->toOptionArray();

        $this->assertSame(['header', 'footer', 'sidebar', 'mobile'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new MenuType())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
