<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Framework\Flag;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;

trait FlagTestTrait
{
    private function newFlag(array $data = []): Flag
    {
        $resource = $this->createStub(FlagResource::class);
        $resource->method('getIdFieldName')->willReturn('flag_id');

        return new Flag(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $resource,
            null,
            $data,
            new Json(),
            new Serialize()
        );
    }
}
