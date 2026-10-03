<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model\Config\Source;

use Panth\MegaMenu\Model\Config\Source\ImageSize;
use PHPUnit\Framework\TestCase;

class ImageSizeTest extends TestCase
{
    public function testOptionValues(): void
    {
        $values = array_column((new ImageSize())->toOptionArray(), 'value');

        $this->assertSame(['small', 'thumbnail', 'medium', 'large'], $values);
    }

    public function testToArrayMatchesOptionArray(): void
    {
        $source = new ImageSize();
        $fromOptions = [];
        foreach ($source->toOptionArray() as $option) {
            $fromOptions[$option['value']] = (string) $option['label'];
        }

        $this->assertSame($fromOptions, array_map('strval', $source->toArray()));
        $this->assertStringContainsString('80x80', $fromOptions['small']);
    }
}
