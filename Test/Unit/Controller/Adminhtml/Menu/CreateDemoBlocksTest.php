<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block as BlockResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\MegaMenu\Controller\Adminhtml\Menu\CreateDemoBlocks;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CreateDemoBlocksTest extends TestCase
{
    use ControllerTestTrait;

    private array $saved = [];
    private array $loaded = [];

    private function controller(array $existingIds, array $failing = []): CreateDemoBlocks
    {
        $identifier = null;
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnCallback(function ($cond, $value) use ($select, &$identifier) {
            $identifier = $value;
            return $select;
        });
        $select->method('limit')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturnCallback(function () use (&$identifier, $existingIds) {
            return $existingIds[$identifier] ?? false;
        });
        $resourceConnection = $this->createStub(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($connection);
        $resourceConnection->method('getTableName')->willReturnArgument(0);

        $factory = $this->createStub(BlockFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->createPartialMock(Block::class, []));
        $resource = $this->createStub(BlockResource::class);
        $resource->method('load')->willReturnCallback(function (Block $block, $id) use ($resource) {
            $this->loaded[] = $id;
            $block->setData('block_id', $id);
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function (Block $block) use ($resource, $failing) {
            $key = $block->getIdentifier() ?? $block->getData('block_id');
            if (in_array($key, $failing, true)) {
                throw new \RuntimeException('save failed');
            }
            $this->saved[] = $block;
            return $resource;
        });

        return new CreateDemoBlocks($this->context(), $this->jsonFactory(), $factory, $resource, $resourceConnection);
    }

    public function testCreatesAllThreeBlocks(): void
    {
        $this->controller([])->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $this->assertSame(
            ['panth_menu_demo_featured', 'panth_menu_demo_promo', 'panth_menu_demo_newsletter'],
            array_column($this->jsonPayload['blocks'], 'identifier')
        );
        $this->assertSame('3 demo CMS blocks created successfully', $this->jsonPayload['message']);
        $this->assertCount(3, $this->saved);
        $this->assertSame([0], $this->saved[0]->getData('stores'));
        $this->assertSame(1, $this->saved[0]->getData('is_active'));
        $this->assertStringContainsString('Featured Products', $this->saved[0]->getData('content'));
        $this->assertStringContainsString('Summer Sale', $this->saved[1]->getData('content'));
        $this->assertStringContainsString('newsletter', $this->saved[2]->getData('content'));
    }

    public function testExistingBlockIsUpdatedInPlace(): void
    {
        $this->controller(['panth_menu_demo_promo' => '15'])->execute();

        $this->assertSame(['15'], $this->loaded);
        $this->assertCount(3, $this->saved);
        $this->assertSame('Panth Menu Demo - Promotional Banner', $this->saved[1]->getTitle());
        $this->assertSame('15', $this->saved[1]->getData('block_id'));
    }

    public function testFailedBlockIsSkipped(): void
    {
        $this->controller([], ['panth_menu_demo_newsletter'])->execute();

        $this->assertSame(
            ['panth_menu_demo_featured', 'panth_menu_demo_promo'],
            array_column($this->jsonPayload['blocks'], 'identifier')
        );
        $this->assertSame('2 demo CMS blocks created successfully', $this->jsonPayload['message']);
    }

    public function testAcl(): void
    {
        $this->assertAclResource($this->controller([]), 'Panth_MegaMenu::menu');
    }
}
