<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu\Collection;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Ui\Component\Listing\Column\MenuActions;
use Panth\MegaMenu\Ui\Component\Listing\Column\MenuOptions;
use Panth\MegaMenu\Ui\Component\Listing\Column\TruncatedText;
use Panth\MegaMenu\Ui\Component\Listing\Column\VersionActions;
use Panth\MegaMenu\Ui\Component\Listing\Column\VersionItemCount;
use PHPUnit\Framework\TestCase;

class ColumnsTest extends TestCase
{
    private function context(): ContextInterface
    {
        $context = $this->createStub(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createStub(Processor::class));

        return $context;
    }

    private function url(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            fn (string $path, array $params = []) => $path . '?' . http_build_query($params)
        );

        return $url;
    }

    public function testMenuActionsBuildsLinksForRowsWithId(): void
    {
        $column = new MenuActions($this->context(), $this->createStub(UiComponentFactory::class), $this->url(), [], ['name' => 'actions']);

        $result = $column->prepareDataSource(['data' => ['items' => [['menu_id' => 4], ['title' => 'no id']]]]);

        $actions = $result['data']['items'][0]['actions'];
        $this->assertSame(['edit', 'export', 'delete'], array_keys($actions));
        $this->assertSame('panth_menu/menu/edit?menu_id=4', $actions['edit']['href']);
        $this->assertSame('panth_menu/menu/export?menu_id=4', $actions['export']['href']);
        $this->assertTrue($actions['delete']['post']);
        $this->assertSame('Delete Menu', (string) $actions['delete']['confirm']['title']);
        $this->assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }

    public function testMenuActionsIgnoresEmptyDataSource(): void
    {
        $column = new MenuActions($this->context(), $this->createStub(UiComponentFactory::class), $this->url());

        $this->assertSame(['data' => []], $column->prepareDataSource(['data' => []]));
    }

    public function testVersionActionsIncludesRestoreDetails(): void
    {
        $column = new VersionActions($this->context(), $this->createStub(UiComponentFactory::class), $this->url(), [], ['name' => 'actions']);

        $result = $column->prepareDataSource(['data' => ['items' => [
            ['version_id' => 7, 'version_number' => 3, 'created_by' => 'jane', 'created_at' => '2026-01-02'],
            ['version_id' => 8],
        ]]]);

        $first = $result['data']['items'][0]['actions'];
        $this->assertSame('panth_menu/version/export?version_id=7', $first['export']['href']);
        $this->assertSame('_blank', $first['export']['target']);
        $this->assertSame('panth_menu/version/restore?version_id=7', $first['restore']['href']);
        $this->assertSame('Restore Version 3', (string) $first['restore']['confirm']['title']);
        $message = (string) $first['restore']['confirm']['message'];
        $this->assertStringContainsString('By: jane', $message);
        $this->assertStringContainsString('Comment: No comment', $message);
        $this->assertSame('panth_menu/version/delete?version_id=7', $first['delete']['href']);

        $second = (string) $result['data']['items'][1]['actions']['restore']['confirm']['message'];
        $this->assertStringContainsString('Version this version', $second);
        $this->assertStringContainsString('By: unknown user', $second);
    }

    public function testVersionItemCountCountsArrayEntries(): void
    {
        $column = new VersionItemCount($this->context(), $this->createStub(UiComponentFactory::class), [], ['name' => 'item_count']);

        $result = $column->prepareDataSource(['data' => ['items' => [
            ['items_json' => '[{"id":1},{"id":2},"junk"]'],
            ['items_json' => ''],
            ['items_json' => 'not json'],
            [],
        ]]]);

        $this->assertSame([2, 0, 0, 0], array_column($result['data']['items'], 'item_count'));
        $this->assertSame(['data' => ['totalRecords' => 0]], $column->prepareDataSource(['data' => ['totalRecords' => 0]]));
    }

    public function testTruncatedTextShortValueIsEscaped(): void
    {
        $column = new TruncatedText($this->context(), $this->createStub(UiComponentFactory::class), [], ['name' => 'version_comment']);

        $result = $column->prepareDataSource(['data' => ['items' => [['version_comment' => '<b>hi</b>'], ['other' => 1]]]]);

        $this->assertSame('&lt;b&gt;hi&lt;/b&gt;', $result['data']['items'][0]['version_comment']);
        $this->assertSame('<b>hi</b>', $result['data']['items'][0]['version_comment_full']);
        $this->assertSame(['other' => 1], $result['data']['items'][1]);
    }

    public function testTruncatedTextLongValueCutsAtWordBoundary(): void
    {
        $column = new TruncatedText($this->context(), $this->createStub(UiComponentFactory::class), [], ['name' => 'c']);
        $text = str_repeat('word ', 30);

        $result = $column->prepareDataSource(['data' => ['items' => [['c' => $text]]]]);

        $expectedShort = rtrim(substr($text, 0, 100));
        $this->assertSame(
            '<span title="' . $text . '">' . $expectedShort . '...</span>',
            $result['data']['items'][0]['c']
        );
    }

    public function testTruncatedTextWithoutSpacesCutsHard(): void
    {
        $column = new TruncatedText($this->context(), $this->createStub(UiComponentFactory::class), [], ['name' => 'c']);
        $text = str_repeat('x', 150);

        $result = $column->prepareDataSource(['data' => ['items' => [['c' => $text]]]]);

        $this->assertStringContainsString('>' . str_repeat('x', 100) . '...</span>', $result['data']['items'][0]['c']);
    }

    public function testMenuOptions(): void
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 1, 'title' => 'Main']),
            new DataObject(['id' => 2, 'title' => 'Footer']),
        ]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $this->assertSame([
            ['value' => 1, 'label' => 'Main (ID: 1)'],
            ['value' => 2, 'label' => 'Footer (ID: 2)'],
        ], (new MenuOptions($factory))->toOptionArray());
    }
}
