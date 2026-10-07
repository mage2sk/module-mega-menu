<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Source;

use Panth\MegaMenu\Model\Source\FontFamily;
use PHPUnit\Framework\TestCase;

class FontFamilyTest extends TestCase
{
    public function testFirstOptionIsThemeDefault(): void
    {
        $options = (new FontFamily())->toOptionArray();

        $this->assertSame('', $options[0]['value']);
        $this->assertCount(18, $options);
    }

    public function testValuesAreUniqueCssFontStacks(): void
    {
        $values = array_column((new FontFamily())->toOptionArray(), 'value');

        $this->assertSame($values, array_values(array_unique($values)));
        foreach (array_slice($values, 1) as $value) {
            $this->assertMatchesRegularExpression('/(sans-serif|serif|monospace|cursive)$/', $value);
        }
    }
}
