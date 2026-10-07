<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Config\Source;

use Panth\MegaMenu\Model\Config\Source\HoverEffect;
use PHPUnit\Framework\TestCase;

class HoverEffectTest extends TestCase
{
    public function testOptionValues(): void
    {
        $values = array_column((new HoverEffect())->toOptionArray(), 'value');

        $this->assertSame(['none', 'underline', 'fade', 'highlight', 'slide', 'glow', 'grow'], $values);
    }

    public function testToArrayMatchesOptionArray(): void
    {
        $source = new HoverEffect();
        $fromOptions = [];
        foreach ($source->toOptionArray() as $option) {
            $fromOptions[$option['value']] = (string) $option['label'];
        }

        $this->assertSame($fromOptions, array_map('strval', $source->toArray()));
    }
}
