<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\ItemType;
use PHPUnit\Framework\TestCase;

class ItemTypeTest extends TestCase
{
    public function testOptionValuesInOrder(): void
    {
        $options = (new ItemType())->toOptionArray();

        $this->assertSame(['category', 'cms_page', 'custom_url', 'product', 'html', 'widget', 'separator'], array_column($options, 'value'));
    }

    public function testEveryOptionHasNonEmptyLabel(): void
    {
        foreach ((new ItemType())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
            $this->assertNotSame('', (string) $option['label']);
        }
    }
}
