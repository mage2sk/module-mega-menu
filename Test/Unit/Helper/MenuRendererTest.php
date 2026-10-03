<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Helper;

use Magento\Cms\Model\Block as CmsBlock;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Cms\Model\Template\Filter as Template;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\MenuRenderer;
use Panth\MegaMenu\Test\Unit\EscaperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MenuRendererTest extends TestCase
{
    use EscaperTrait;

    private array $blocks = [];
    private bool $filterFails = false;

    private function renderer(): MenuRenderer
    {
        $cmsBlock = $this->createPartialMock(CmsBlock::class, ['load', 'getId', 'isActive', 'getContent']);
        $current = [];
        $cmsBlock->method('load')->willReturnCallback(function ($id) use ($cmsBlock, &$current) {
            $current = $this->blocks[$id] ?? [];
            return $cmsBlock;
        });
        $cmsBlock->method('getId')->willReturnCallback(function () use (&$current) {
            return $current['id'] ?? null;
        });
        $cmsBlock->method('isActive')->willReturnCallback(function () use (&$current) {
            return $current['active'] ?? false;
        });
        $cmsBlock->method('getContent')->willReturnCallback(function () use (&$current) {
            return $current['content'] ?? '';
        });

        $filter = $this->createStub(Template::class);
        $filter->method('setStoreId')->willReturnSelf();
        $filter->method('filter')->willReturnCallback(function ($content) {
            if ($this->filterFails) {
                throw new \RuntimeException('directive error');
            }
            return '[filtered]' . $content;
        });
        $filterProvider = $this->createStub(FilterProvider::class);
        $filterProvider->method('getPageFilter')->willReturn($filter);
        $filterProvider->method('getBlockFilter')->willReturn($filter);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $assets = $this->createStub(AssetRepository::class);
        $assets->method('getUrl')->willReturnCallback(fn (string $id) => 'https://static.test/' . $id);

        return new MenuRenderer($this->realEscaper(), $cmsBlock, $filterProvider, $storeManager, $assets);
    }

    public static function urlProvider(): array
    {
        return [
            'empty' => ['', '#'],
            'spaces' => ['   ', '#'],
            'relative' => ['/women.html', '/women.html'],
            'https' => ['https://x.test/a', 'https://x.test/a'],
            'mailto' => ['mailto:a@b.c', 'mailto:a@b.c'],
            'tel' => ['tel:+123', 'tel:+123'],
            'javascript' => ['javascript:alert(1)', '#'],
            'mixed case javascript' => ['JaVaScRiPt:alert(1)', '#'],
            'whitespace obfuscated' => ["java\tscript:alert(1)", '#'],
            'entity obfuscated' => ['javascript&colon;alert(1)', '#'],
            'data uri' => ['data:text/html;base64,xx', '#'],
            'vbscript' => ['vbscript:msgbox', '#'],
            'null' => [null, '#'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function testSanitizeUrl(?string $url, string $expected): void
    {
        $this->assertSame($expected, $this->renderer()->sanitizeUrl($url));
    }

    public function testRenderIconLibraries(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('', $renderer->renderIcon(''));
        $this->assertSame('<i class="fa-solid&#x20;fa-home"></i>', $renderer->renderIcon('fa-solid fa-home'));
        $this->assertSame('<i class="a&quot;&#x20;onclick&#x3D;&quot;x"></i>', $renderer->renderIcon('a" onclick="x'));
        $this->assertSame('<span class="menu-icon menu-icon-emoji">&lt;b&gt;</span>', $renderer->renderIcon('<b>', 'emoji'));
        $this->assertSame('<span class="material-symbols-outlined">home</span>', $renderer->renderIcon('home', 'material'));
        $this->assertSame('', $renderer->renderIcon('home', 'unknown'));
        $this->assertStringContainsString('<img src="https://x.test/i.svg"', $renderer->renderIcon('https://x.test/i.svg', 'svg'));
    }

    public function testInlineSvgSafetyChecks(): void
    {
        $renderer = $this->renderer();
        $safe = '<svg viewBox="0 0 1 1"><path d="M0 0"/></svg>';

        $this->assertSame('<span class="menu-icon menu-icon-svg">' . $safe . '</span>', $renderer->renderIcon($safe, 'svg'));
        foreach ([
            '<svg><script>alert(1)</script></svg>',
            '<svg onload="x()"></svg>',
            '<svg><foreignObject></foreignObject></svg>',
            '<svg><a href="javascript:x"></a></svg>',
            '<svg>&#106;</svg>',
            '<svg><iframe></iframe></svg>',
        ] as $unsafe) {
            $this->assertSame('', $renderer->renderIcon($unsafe, 'svg'), $unsafe);
        }
    }

    public function testRenderItemIconDefaultsToFontAwesome(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('<i class="fa-bolt"></i>', $renderer->renderItemIcon(['icon' => 'fa-bolt']));
        $this->assertSame('', $renderer->renderItemIcon([]));
        $this->assertSame($renderer->getEscaper(), $renderer->getEscaper());
    }

    public function testFilterContent(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('', $renderer->filterContent(''));
        $this->assertSame('[filtered]{{widget}}', $renderer->filterContent('{{widget}}'));
        $this->filterFails = true;
        $this->assertSame('', $renderer->filterContent('{{bad}}'));
    }

    public function testSanitizeTreeRecursesAndDropsNonArrays(): void
    {
        $tree = $this->renderer()->sanitizeTree([
            'bad',
            ['title' => 'A', 'url' => 'javascript:x', 'custom_content' => '<p>x</p>', 'children' => [
                ['title' => 'B', 'url' => '/b'],
                5,
            ]],
            ['title' => 'NoUrl'],
        ]);

        $this->assertSame([1, 2], array_keys($tree));
        $this->assertSame('#', $tree[1]['url']);
        $this->assertSame('[filtered]<p>x</p>', $tree[1]['custom_content']);
        $this->assertSame([['title' => 'B', 'url' => '/b']], $tree[1]['children']);
        $this->assertArrayNotHasKey('url', $tree[2]);
    }

    public function testRenderImageSizes(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('', $renderer->renderImage([]));
        $html = $renderer->renderImage(['image' => 'https://x.test/a.png', 'title' => 'Shoes'], 'medium');
        $this->assertStringContainsString('alt="Shoes"', $html);
        $this->assertStringContainsString('width="200" height="200"', $html);
        $custom = $renderer->renderImage(['image' => '/a.png', 'image_alt' => 'Alt', 'image_width' => '10', 'image_height' => 20], 'unknown');
        $this->assertStringContainsString('alt="Alt"', $custom);
        $this->assertStringContainsString('width="10" height="20"', $custom);
        $this->assertStringContainsString('width="50"', $renderer->renderImage(['image' => '/a.png']));
    }

    public function testRenderCmsBlockStates(): void
    {
        $this->blocks = [
            5 => ['id' => 5, 'active' => true, 'content' => 'Promo'],
            6 => ['id' => 6, 'active' => false, 'content' => 'Off'],
        ];
        $renderer = $this->renderer();

        $this->assertSame('[filtered]Promo', $renderer->renderCmsBlock(5));
        $this->assertStringContainsString('CMS Block Content (ID: 5)', $renderer->renderCmsBlock(5, true));
        $this->assertStringContainsString('not found or inactive (ID: 6)', $renderer->renderCmsBlock(6));
        $this->assertStringContainsString('not found or inactive (ID: 99)', $renderer->renderCmsBlock(99));
        $this->filterFails = true;
        $this->assertStringContainsString('Error loading CMS block', $renderer->renderCmsBlock(5));
    }

    private function sampleTree(): array
    {
        return [
            [
                'item_id' => 1, 'title' => 'Shop & Co', 'url' => '/shop', 'level' => 0, 'icon' => 'fa-bag',
                'background_color' => '#000', 'text_color' => '#fff', 'hover_effect' => 'glow', 'submenu_columns' => 3,
                'children' => [
                    ['item_id' => 2, 'title' => 'Shirts', 'url' => '/shirts', 'children' => [
                        ['item_id' => 3, 'title' => 'Tees', 'url' => '/tees'],
                    ]],
                    ['item_id' => 4, 'title' => 'Hidden child', 'is_active' => 0],
                ],
            ],
            ['item_id' => 5, 'title' => 'Disabled', 'level' => 0, 'is_active' => 0],
            ['item_id' => 6, 'title' => 'Nested', 'level' => 1],
            ['item_id' => 7, 'title' => 'Promo', 'level' => 0, 'item_type' => 'cms_block', 'cms_block_id' => 5, 'url' => '#'],
        ];
    }

    public function testRenderDesktopMenuStructure(): void
    {
        $this->blocks = [5 => ['id' => 5, 'active' => true, 'content' => 'Deals']];
        $html = $this->renderer()->renderDesktopMenu($this->sampleTree());

        $this->assertStringContainsString('https://static.test/Panth_MegaMenu::css/fontawesome/all.min.css', $html);
        $this->assertStringContainsString('Shop &amp; Co', $html);
        $this->assertStringContainsString('data-root-id="1" @mouseenter="openMenu(1)"', $html);
        $this->assertStringContainsString('background-color: &#x23;000; color: &#x23;fff;', $html);
        $this->assertStringContainsString('hover-glow', $html);
        $this->assertStringContainsString('grid grid-cols-3 gap-4', $html);
        $this->assertStringContainsString('min-width: 750px;', $html);
        $this->assertStringContainsString('panth-dropdown-nested', $html);
        $this->assertStringContainsString('href="/tees"', $html);
        $this->assertStringNotContainsString('Hidden child', $html);
        $this->assertStringNotContainsString('Disabled', $html);
        $this->assertStringNotContainsString('>Nested<', $html);
        $this->assertStringContainsString('<span  class="flex items-center gap-2 px-4 py-3', $html);
        $this->assertStringContainsString('[filtered]Deals', $html);
    }

    public function testCmsBlockWithChildrenRendersBrowseSection(): void
    {
        $this->blocks = [5 => ['id' => 5, 'active' => true, 'content' => 'Deals']];
        $html = $this->renderer()->renderDesktopMenu([[
            'item_id' => 9, 'title' => 'Mega', 'level' => 0, 'item_type' => 'cms_block', 'cms_block_id' => 5,
            'url' => '/mega', 'children' => [['item_id' => 10, 'title' => 'Child', 'url' => '/c']],
        ]], true);

        $this->assertStringContainsString('Browse Categories', $html);
        $this->assertStringContainsString('CMS Block Content (ID: 5)', $html);
        $this->assertStringContainsString('<div class="relative ">', $html);
        $this->assertStringContainsString('href="/mega"', $html);
    }

    public function testItemsWithoutIdHaveNoChildrenRendered(): void
    {
        $html = $this->renderer()->renderDesktopMenu([
            ['title' => 'Orphan parent', 'level' => 0, 'children' => [['title' => 'Lost child']]],
        ]);

        $this->assertStringContainsString('Orphan parent', $html);
        $this->assertStringNotContainsString('Lost child', $html);
    }

    public function testLumaVariantScopesCssAndStripsGlobalRules(): void
    {
        $html = $this->renderer()->renderDesktopMenuLuma([['item_id' => 1, 'title' => 'A', 'level' => 0]]);

        $this->assertStringContainsString(
            '<style>.megamenu-container .fa, .megamenu-container .fas, .megamenu-container .far,',
            $html
        );
        $this->assertStringContainsString(
            '.megamenu-container .panth-dropdown { opacity: 0; visibility: hidden; transform: translateY(-10px);',
            $html
        );
        $this->assertStringContainsString('max-width: min(750px, calc(100vw - 2rem)); width: max-content; }', $html);
        $this->assertStringContainsString('#panthMenuContent { width: 100%; max-width: 100vw; position: relative; }', $html);
        $this->assertStringContainsString('.megamenu-container { max-width: 100%; }', $html);
        $this->assertStringContainsString('.megamenu-container ul { max-width: 100%; }', $html);
        $this->assertStringNotContainsString('.megamenu-container .megamenu-container', $html);
        $this->assertStringNotContainsString('.megamenu-container }', $html);
        $this->assertStringNotContainsString('x-init=', $html);
        $this->assertStringNotContainsString('overflow-x: hidden !important; width: 100% !important', $html);
        $this->assertStringContainsString('>A</span>', $html);
    }

    private function scopeCss(string $css): string
    {
        $method = new \ReflectionMethod(MenuRenderer::class, 'scopeCssToMegamenu');
        $html = $method->invoke($this->renderer(), '<div><style>' . $css . '</style></div>');

        return substr($html, strlen('<div><style>'), -strlen('</style></div>'));
    }

    public function testScopeCssHandlesSelectorListsAndAtRules(): void
    {
        $css = '/* note */ .a, .b > li:not(.c, .d) { color: red; background: url("x;y.png"); }'
            . '@media (min-width: 768px) { .e { margin: 0; } html .f, .g { padding: 1px; } }'
            . '@keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }'
            . '@import url("theme.css");'
            . ':root { --pmm: 1; } body.cms .h { top: 0; } .megamenu-container .i { left: 0; }'
            . '.content[data-x="a,b"] { content: "{;}"; }';

        $this->assertSame(
            '.megamenu-container .a, .megamenu-container .b > li:not(.c, .d) { color: red; background: url("x;y.png"); } '
            . '@media (min-width: 768px) { .megamenu-container .e { margin: 0; } html .f, .megamenu-container .g { padding: 1px; } } '
            . '@keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } } '
            . '@import url("theme.css"); '
            . ':root { --pmm: 1; } body.cms .h { top: 0; } .megamenu-container .i { left: 0; } '
            . '.megamenu-container .content[data-x="a,b"] { content: "{;}"; } ',
            $this->scopeCss($css)
        );
    }

    public function testScopeCssToleratesUnbalancedInput(): void
    {
        $this->assertSame('.megamenu-container .a { color: red; } ', $this->scopeCss('.a { color: red; } }'));
        $this->assertSame('.megamenu-container .b { color: blue; } ', $this->scopeCss('.b { color: blue;'));
        $this->assertSame('', $this->scopeCss(''));
    }

    public static function scheduleProvider(): array
    {
        return [
            'no dates' => ['', '', 'UTC', true],
            'end date only covers whole day' => ['', '2026-03-15', 'UTC', true],
            'end date with time in future' => ['', '2026-03-15 12:30', 'UTC', true],
            'end date with time passed' => ['', '2026-03-15T11:30', 'UTC', false],
            'start date only begins at midnight' => ['2026-03-15', '', 'UTC', true],
            'start later today' => ['2026-03-15 13:00:00', '', 'UTC', false],
            'store timezone keeps item visible' => ['', '2026-03-15 08:30', 'America/New_York', true],
            'store timezone hides after end' => ['', '2026-03-15 07:30', 'America/New_York', false],
            'invalid timezone falls back to UTC' => ['', '2026-03-15 11:30', 'Mars/Base', false],
            'zero date ignored' => ['0000-00-00 00:00:00', '0000-00-00 00:00:00', 'UTC', true],
            'garbage ignored' => ['soon', 'later', 'UTC', true],
        ];
    }

    #[DataProvider('scheduleProvider')]
    public function testIsWithinSchedule(string $start, string $end, string $timezone, bool $expected): void
    {
        $now = (new \DateTimeImmutable('2026-03-15 12:00:00', new \DateTimeZone('UTC')))->getTimestamp();

        $this->assertSame($expected, $this->renderer()->isWithinSchedule($start, $end, $now, $timezone));
    }

    public function testCommonStylesContainHoverEffects(): void
    {
        $css = $this->renderer()->getCommonStyles();

        foreach (['.hover-fade:hover', '.hover-slide:hover', '.hover-zoom:hover', '.hover-underline:hover', '.hover-glow:hover'] as $selector) {
            $this->assertStringContainsString($selector, $css);
        }
    }
}
