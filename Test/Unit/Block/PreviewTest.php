<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\Json\DecoderInterface;
use Panth\MegaMenu\Block\Preview;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\ConfigHelperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\MenuViewModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PreviewTest extends TestCase
{
    use BlockContextTrait;
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuModelTrait;
    use MenuViewModelTrait;

    private int $loads = 0;

    private function block(array $rows = []): Preview
    {
        $decoder = $this->createStub(DecoderInterface::class);
        $decoder->method('decode')->willReturnCallback(function (string $json) {
            $decoded = json_decode($json, true);
            if ($decoded === null && $json !== 'null') {
                throw new \InvalidArgumentException('bad json');
            }
            return $decoded;
        });
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($rows) {
            $this->loads++;
            return $this->selfLoadingMenu($rows);
        });

        return new Preview(
            $this->blockContext(),
            $decoder,
            $this->menuViewModel(),
            $factory,
            [],
            $this->dataHelper([Data::XML_PATH_MOBILE_BREAKPOINT => '900'])
        );
    }

    public function testNoInputGivesEmptyPreview(): void
    {
        $block = $this->block();

        $this->assertSame([], $block->getMenuData());
        $this->assertNull($block->getCurrentMenu());
        $this->assertSame([], $block->getCurrentMenuTree());
    }

    public function testPostedItemsBuildPreviewMenu(): void
    {
        $this->requestPost = ['items_json' => json_encode([
            ['item_id' => 1, 'title' => 'Root', 'url' => 'javascript:alert(1)'],
            ['temp_id' => 'tmp', 'title' => 'Temp root', 'parent_id' => '0'],
            ['item_id' => 3, 'title' => 'Child', 'parent_id' => 1],
            ['item_id' => 4, 'title' => 'Temp child', 'parent_id' => 'tmp'],
            ['item_id' => 5, 'title' => 'Self parent', 'parent_id' => 5],
            ['title' => 'No id'],
            'garbage',
        ])];
        $this->requestParams = ['css_class' => 'preview-nav', 'item_gap' => '4px'];
        $block = $this->block();

        $data = $block->getMenuData();
        $this->assertSame('preview', $data['menu_id']);
        $this->assertSame('preview-nav', $data['css_class']);
        $this->assertSame('4px', $data['item_gap']);
        $this->assertSame($data, $block->getMenuData());

        $tree = $block->getCurrentMenuTree();
        $this->assertSame(['Root', 'Temp root', 'No id'], array_column($tree, 'title'));
        $this->assertSame('#', $tree[0]['url']);
        $this->assertSame('Child', $tree[0]['children'][0]['title']);
        $this->assertSame('Temp child', $tree[1]['children'][0]['title']);
        $this->assertSame([], $tree[2]['children']);
        $this->assertSame($tree, $block->getCurrentMenuTree());

        $menu = $block->getCurrentMenu();
        $this->assertSame('preview', $menu->getData('id'));
        $this->assertSame('Preview Menu', $menu->getData('title'));
    }

    public function testInvalidPostedJsonGivesEmptyData(): void
    {
        $this->requestPost = ['items_json' => '{broken'];

        $this->assertSame([], $this->block()->getMenuData());
    }

    public function testMenuLoadedFromDatabase(): void
    {
        $this->requestParams = ['menu_id' => '7'];
        $block = $this->block([[
            'menu_id' => 7, 'identifier' => 'main', 'title' => 'Main', 'is_active' => 1,
            'items_json' => '[{"item_id":1,"title":"A"}]', 'container_padding' => '3px',
        ]]);

        $data = $block->getMenuData();

        $this->assertSame(7, $data['menu_id']);
        $this->assertSame(1, $data['is_active']);
        $this->assertSame([['item_id' => 1, 'title' => 'A']], $data['items']);
        $this->assertSame('3px', $data['container_padding']);
        $this->assertSame('', $data['css_class']);
        $block->getMenuData();
        $this->assertSame(1, $this->loads);
    }

    public function testMissingDatabaseMenu(): void
    {
        $this->requestParams = ['menu_id' => '8'];

        $this->assertSame([], $this->block()->getMenuData());
    }

    public function testMenuWithoutItemsJson(): void
    {
        $this->requestParams = ['menu_id' => '7'];

        $this->assertSame([], $this->block([['menu_id' => 7, 'identifier' => 'main']])->getMenuData()['items']);
    }

    public function testAccessors(): void
    {
        $block = $this->block();

        $this->assertTrue($block->isEnabled());
        $this->assertSame('preview', $block->getId());
        $this->assertSame(900, $block->getMobileBreakpoint());
        $this->assertSame($block->getViewModel(), $block->getMenuViewModel());
        $this->assertSame($block->getViewModel()->getMenuRenderer(), $block->getMenuRenderer());
        $this->assertInstanceOf(Data::class, $block->getMenuHelper());
    }
}
