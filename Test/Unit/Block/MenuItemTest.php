<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Panth\MegaMenu\Block\MenuItem;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\ConfigHelperTrait;
use Panth\MegaMenu\Test\Unit\ViewModel\MenuViewModelTrait;
use PHPUnit\Framework\TestCase;

class MenuItemTest extends TestCase
{
    use BlockContextTrait;
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuViewModelTrait;

    private function block(array $data = [], ?LayoutInterface $layout = null): MenuItem
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getCurrentUrl')->willReturn('https://shop.test/current');

        return new MenuItem(
            $this->blockContext($layout),
            $this->dataHelper(),
            $this->menuViewModel([], ['urlBuilder' => $url]),
            $data
        );
    }

    public function testEmptyBlockDefaults(): void
    {
        $block = $this->block();

        $this->assertNull($block->getItem());
        $this->assertSame('#', $block->getItemUrl());
        $this->assertSame('', $block->getItemClasses());
        $this->assertSame([], $block->getLinkAttributes());
        $this->assertSame('', $block->renderLinkAttributes());
        $this->assertSame('', $block->getItemTitleHtml());
        $this->assertFalse($block->hasChildren());
        $this->assertSame([], $block->getChildren());
        $this->assertSame('', $block->renderChildren());
        $this->assertFalse($block->shouldShowContent());
        $this->assertSame('', $block->getItemContent());
        $this->assertSame('', $block->getColumnWidthClass());
        $this->assertFalse($block->isActive());
        $this->assertSame(0, $block->getLevel());
    }

    public function testModelItemClassesAndAttributes(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1]);
        $item = $this->item([
            'item_id' => 1, 'is_active' => 1, 'item_type' => 'category', 'css_class' => 'promo',
            'title' => 'Sale "now"', 'url' => 'https://shop.test/current', 'open_new_tab' => 1, 'columns' => 4,
        ], [$child]);
        $block = $this->block()->setItem($item)->setLevel(2);

        $this->assertSame(2, $block->getLevel());
        $this->assertSame('megamenu-item level-2 type-category has-children promo active', $block->getItemClasses());
        $attributes = $block->getLinkAttributes();
        $this->assertSame('https://shop.test/current', $attributes['href']);
        $this->assertSame('Sale&#x20;&quot;now&quot;', $attributes['title']);
        $this->assertSame('_blank', $attributes['target']);
        $this->assertSame('noopener noreferrer', $attributes['rel']);
        $this->assertSame('true', $attributes['aria-haspopup']);
        $this->assertStringContainsString('class="megamenu-link"', $block->renderLinkAttributes());
        $this->assertSame('Sale &quot;now&quot;', $block->getItemTitleHtml());
        $this->assertSame([$child], $block->getChildren());
        $this->assertSame('col-span-3', $block->getColumnWidthClass());
    }

    public function testItemFromBlockData(): void
    {
        $item = $this->item(['item_id' => 1, 'is_active' => 1, 'item_type' => 'content', 'content' => 'Body']);
        $block = $this->block(['item' => $item]);

        $this->assertSame($item, $block->getItem());
        $this->assertTrue($block->shouldShowContent());
        $this->assertSame('[f]Body', $block->getItemContent());
        $this->assertSame([], $block->getChildren());
    }

    public function testArrayItemsSupportClassesAndActiveCheck(): void
    {
        $block = $this->block(['item' => [
            'title' => 'A', 'url' => '/a', 'item_type' => 'link', 'css_class' => 'promo', 'children' => [['title' => 'B']],
        ]]);

        $this->assertSame('/a', $block->getItemUrl());
        $this->assertTrue($block->hasChildren());
        $this->assertSame([['title' => 'B']], $block->getChildren());
        $this->assertSame('megamenu-item level-0 type-link has-children promo', $block->getItemClasses());
        $this->assertFalse($block->isActive());

        $current = $this->block(['item' => ['title' => 'C', 'url' => 'https://shop.test/current']]);
        $this->assertTrue($current->isActive());
        $this->assertSame('megamenu-item level-0 active', $current->getItemClasses());
        $this->assertFalse($this->block(['item' => ['url' => 'https://shop.test/current', 'is_active' => 0]])->isActive());
    }

    public function testRenderChildrenAcceptsArrayChildren(): void
    {
        $child = ['item_id' => 2, 'title' => 'Kid', 'url' => '/kid'];
        $created = [];
        $childBlock = $this->createMock(MenuItem::class);
        $childBlock->method('setItem')->willReturnCallback(function ($item) use ($childBlock, &$created) {
            $created[] = $item;
            return $childBlock;
        });
        $childBlock->method('setLevel')->willReturnSelf();
        $childBlock->method('toHtml')->willReturn('<li>Kid</li>');
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($childBlock);
        $block = $this->block([], $layout)->setItem(['item_id' => 1, 'title' => 'Root', 'children' => [$child]]);

        $this->assertSame('<ul class="submenu level-1"><li>Kid</li></ul>', $block->renderChildren());
        $this->assertSame([$child], $created);

        $real = $this->block()->setItem($child);
        $this->assertSame($child, $real->getItem());
    }

    public function testRenderChildrenCreatesChildBlocks(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'title' => 'Kid']);
        $created = [];
        $childBlock = $this->createMock(MenuItem::class);
        $childBlock->method('setItem')->willReturnCallback(function ($item) use ($childBlock, &$created) {
            $created[] = $item;
            return $childBlock;
        });
        $childBlock->expects($this->once())->method('setLevel')->with(1)->willReturnSelf();
        $childBlock->method('toHtml')->willReturn('<li>Kid</li>');
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($childBlock);
        $block = $this->block([], $layout)->setItem($this->item(['item_id' => 1, 'is_active' => 1], [$child]));

        $this->assertSame('<ul class="submenu level-1"><li>Kid</li></ul>', $block->renderChildren());
        $this->assertSame([$child], $created);
        $this->assertSame($block->getMenuHelper(), $block->getMenuHelper());
        $this->assertSame($block->getMenuViewModel(), $block->getMenuViewModel());
    }
}
