<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\Model\Item;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use Panth\MegaMenu\ViewModel\ItemRenderer;
use Panth\MegaMenu\ViewModel\Menu;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ItemRendererTest extends TestCase
{
    use ConfigHelperTrait;
    use EscaperTrait;
    use MenuViewModelTrait;

    private function renderer(array $config = [], ?Menu $menu = null): ItemRenderer
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new ItemRenderer(
            $this->dataHelper($config),
            $this->filterProvider(),
            $storeManager,
            $this->realEscaper(),
            $this->createStub(LoggerInterface::class),
            $menu ?? $this->menuViewModel($config)
        );
    }

    public function testRenderIconRespectsConfig(): void
    {
        $item = $this->item(['icon_class' => 'fa-star']);

        $this->assertSame('', $this->renderer()->renderIcon($item));
        $this->assertSame(
            '<i class="fa-star megamenu-icon" aria-hidden="true"></i>',
            $this->renderer([Data::XML_PATH_SHOW_ICONS => 1])->renderIcon($item)
        );
        $this->assertSame('', $this->renderer([Data::XML_PATH_SHOW_ICONS => 1])->renderIcon($this->item([])));
    }

    public function testBadges(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('', $renderer->renderBadge($this->item([])));
        $this->assertSame(
            '<span class="megamenu-badge badge bg-primary text-white">50% &amp; more</span>',
            $renderer->renderBadge($this->item(['content' => 'x <BADGE> 50% & more </badge>']))
        );
        $this->assertStringContainsString('>New<', $renderer->renderBadge($this->item(['content' => 'shiny [new]'])));
        $this->assertStringContainsString('>Hot<', $renderer->renderBadge($this->item(['content' => '[HOT]'])));
        $this->assertStringContainsString('>Sale<', $renderer->renderBadge($this->item(['content' => '[Sale]'])));
        $this->assertSame('', $renderer->renderBadge($this->item(['content' => 'plain'])));
    }

    public function testImageRendering(): void
    {
        $item = $this->item(['title' => 'Shoes', 'content' => '<img src="/i.png">']);

        $this->assertSame('', $this->renderer()->renderImage($item));
        $plain = $this->renderer([Data::XML_PATH_SHOW_IMAGES => 1])->renderImage($item);
        $this->assertStringContainsString('src="&#x2F;i.png"', $plain);
        $this->assertStringContainsString('alt="Shoes"', $plain);
        $this->assertStringNotContainsString('data-src', $plain);

        $lazy = $this->renderer([Data::XML_PATH_SHOW_IMAGES => 1, Data::XML_PATH_LAZY_LOAD => 1])->renderImage($item);
        $this->assertStringContainsString('data-src="&#x2F;i.png"', $lazy);
        $this->assertStringContainsString('megamenu-image&#x20;lazy', $lazy);
        $this->assertSame('', $this->renderer([Data::XML_PATH_SHOW_IMAGES => 1])->renderImage($this->item(['content' => 'none'])));
    }

    public function testDescriptionIsFiltered(): void
    {
        $renderer = $this->renderer();

        $this->assertSame(
            '<span class="megamenu-description text-sm text-gray-600">[f]Best deals</span>',
            $renderer->renderDescription($this->item(['content' => "<description>\n Best deals \n</description>"]))
        );
        $this->assertSame('', $renderer->renderDescription($this->item(['content' => 'nothing'])));
        $this->assertSame('', $renderer->renderDescription($this->item([])));
    }

    public function testRenderLinkForHyvaParent(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'title' => 'Child']);
        $item = $this->item(['item_id' => 1, 'is_active' => 1, 'title' => 'Shop', 'url' => '/shop', 'open_new_tab' => 1], [$child]);

        $html = $this->renderer()->renderLink($item, ItemRenderer::THEME_HYVA);

        $this->assertStringStartsWith('<a href="/shop" class="megamenu-link" target="_blank" rel="noopener&#x20;noreferrer" @click.prevent="toggleSubmenu($event)">', $html);
        $this->assertStringContainsString('<span class="megamenu-title">Shop</span>', $html);
        $this->assertStringContainsString(':class="{\'rotate-180\': submenuOpen}"', $html);
    }

    public function testRenderLinkForLumaLeaf(): void
    {
        $item = $this->item(['item_id' => 1, 'is_active' => 1, 'title' => 'Leaf', 'url' => '/leaf']);

        $html = $this->renderer()->renderLink($item, ItemRenderer::THEME_LUMA);

        $this->assertStringStartsWith('<a href="/leaf" class="megamenu-link" target="_self">', $html);
        $this->assertStringNotContainsString('data-bind', $html);
        $this->assertStringNotContainsString('megamenu-dropdown-icon', $html);
    }

    public function testDropdownIndicatorVariants(): void
    {
        $renderer = $this->renderer();
        $item = $this->item([]);

        $this->assertStringContainsString('rotate-180', $renderer->renderDropdownIndicator($item, ItemRenderer::THEME_HYVA));
        $this->assertStringNotContainsString('rotate-180', $renderer->renderDropdownIndicator($item, ItemRenderer::THEME_LUMA));
    }

    public function testRenderHiddenItemReturnsEmpty(): void
    {
        $this->assertSame('', $this->renderer()->render($this->item(['is_active' => 0]), ItemRenderer::THEME_LUMA));
    }

    public function testRenderNestedTreeForLuma(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1, 'title' => 'Child', 'item_type' => 'link', 'url' => '/c']);
        $root = $this->item(['item_id' => 1, 'is_active' => 1, 'level' => 0, 'title' => 'Root', 'item_type' => 'link', 'columns' => 6, 'url' => '/r'], [$child]);

        $html = $this->renderer()->render($root, ItemRenderer::THEME_LUMA);

        $this->assertStringStartsWith('<li class="menu-item&#x20;menu-item-link&#x20;level-0&#x20;has-children" data-item-id="1" data-level="0" data-has-children="true" data-bind=', $html);
        $this->assertStringContainsString('<ul class="megamenu-submenu grid&#x20;grid-cols-4&#x20;gap-4" data-bind="visible: submenuOpen', $html);
        $this->assertStringContainsString('data-item-id="2" data-level="1">', $html);
        $this->assertStringEndsWith('</ul></li>', $html);
    }

    public function testRenderContentItemForHyva(): void
    {
        $item = $this->item(['item_id' => 5, 'is_active' => 1, 'level' => 0, 'title' => 'Promo', 'item_type' => 'content', 'content' => '<p>Deal</p>']);

        $html = $this->renderer()->render($item, ItemRenderer::THEME_HYVA);

        $this->assertStringContainsString('<div class="megamenu-content-wrapper" x-data="{ contentExpanded: false }">', $html);
        $this->assertStringContainsString('<div class="megamenu-content-body">[f]<p>Deal</p></div>', $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function testAutoThemeFallsBackToLumaWithoutHyva(): void
    {
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1]);
        $item = $this->item(['item_id' => 1, 'is_active' => 1, 'url' => '/x'], [$child]);
        $expected = class_exists('Hyva\Theme\ViewModel\HeroiconsSolid') ? '@click.prevent' : 'data-bind="click: toggleSubmenu"';

        $this->assertStringContainsString($expected, $this->renderer()->renderLink($item));
    }

    public function testWrapperAndDropdownClasses(): void
    {
        $renderer = $this->renderer();
        $child = $this->item(['item_id' => 2, 'is_active' => 1, 'level' => 1]);
        $mega = $this->item(['item_id' => 1, 'is_active' => 1, 'level' => 0, 'item_type' => 'category', 'columns' => 3], [$child]);
        $simple = $this->item(['item_id' => 3, 'is_active' => 1, 'level' => 1, 'parent_id' => 1, 'item_type' => 'link', 'columns' => 3]);

        $this->assertSame(
            'megamenu-item-wrapper megamenu-item-wrapper--top-level megamenu-item-wrapper--has-children megamenu-item-wrapper--level-0 megamenu-item-wrapper--category',
            $renderer->getWrapperClasses($mega)
        );
        $this->assertSame('megamenu-item-wrapper megamenu-item-wrapper--level-1 megamenu-item-wrapper--link', $renderer->getWrapperClasses($simple));
        $this->assertTrue($renderer->shouldRenderAsMegaDropdown($mega));
        $this->assertFalse($renderer->shouldRenderAsMegaDropdown($simple));
        $this->assertSame('megamenu-dropdown megamenu-dropdown--mega megamenu-dropdown--columns-3', $renderer->getDropdownPositionClasses($mega));
        $this->assertSame('megamenu-dropdown megamenu-dropdown--simple', $renderer->getDropdownPositionClasses($simple));
    }
}
