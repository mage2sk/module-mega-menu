<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit;

use Magento\Framework\Escaper;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\ZendEscaper;
use Psr\Log\LoggerInterface;

trait EscaperTrait
{
    private function realEscaper(): Escaper
    {
        $escaper = new Escaper();
        $inline = $this->createStub(InlineInterface::class);
        $inline->method('isAllowed')->willReturn(false);
        foreach (['escaper' => new ZendEscaper(), 'translateInline' => $inline, 'logger' => $this->createStub(LoggerInterface::class)] as $name => $value) {
            $property = new \ReflectionProperty(Escaper::class, $name);
            $property->setValue($escaper, $value);
        }

        return $escaper;
    }
}
