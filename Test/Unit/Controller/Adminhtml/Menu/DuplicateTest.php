<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Controller\Adminhtml\Menu\Duplicate;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DuplicateTest extends TestCase
{
    use ControllerTestTrait;

    private array $created = [];
    private bool $saveFails = false;

    private function menuDouble(array $rows): Menu
    {
        $menu = $this->getMockBuilder(Menu::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'save'])
            ->getMock();
        (new \ReflectionProperty($menu, '_idFieldName'))->setValue($menu, 'menu_id');
        $menu->method('load')->willReturnCallback(function ($value, $field = null) use ($menu, $rows) {
            foreach ($rows as $row) {
                if ((string) ($row[$field ?? 'menu_id'] ?? '') === (string) $value) {
                    $menu->setData($row);
                }
            }
            return $menu;
        });
        $menu->method('save')->willReturnCallback(function () use ($menu) {
            if ($this->saveFails) {
                throw new \RuntimeException('disk full');
            }
            $menu->setId(50);
            return $menu;
        });

        return $menu;
    }

    private function controller(array $rows): Duplicate
    {
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($rows) {
            $menu = $this->menuDouble($rows);
            $this->created[] = $menu;
            return $menu;
        });

        return new Duplicate($this->context(), $this->jsonFactory(), $factory);
    }

    public function testRequiresMenuIdAndTitle(): void
    {
        $this->controller([])->execute();
        $this->assertSame(['success' => false, 'message' => 'Menu ID is required'], $this->jsonPayload);

        $this->params = ['menu_id' => 1];
        $this->controller([])->execute();
        $this->assertSame(['success' => false, 'message' => 'New menu title is required'], $this->jsonPayload);
    }

    public function testMissingOriginal(): void
    {
        $this->params = ['menu_id' => 1, 'new_title' => 'Copy'];
        $this->controller([])->execute();

        $this->assertSame(['success' => false, 'message' => 'Menu not found'], $this->jsonPayload);
    }

    public function testDuplicateCopiesFieldsDisablesAndUniquifiesIdentifier(): void
    {
        $rows = [
            ['menu_id' => 1, 'identifier' => 'main', 'title' => 'Main', 'css_class' => 'nav', 'items_json' => '[1]',
                'menu_type' => 'header', 'sort_order' => 3, 'is_active' => 1],
            ['menu_id' => 2, 'identifier' => 'summer_sale'],
            ['menu_id' => 3, 'identifier' => 'summer_sale_1'],
        ];
        $this->params = ['menu_id' => 1, 'new_title' => ' Summer   Sale!! '];

        $this->controller($rows)->execute();

        $this->assertSame(['success' => true, 'message' => 'Menu duplicated successfully', 'menu_id' => 50], $this->jsonPayload);
        $copy = $this->created[1];
        $this->assertSame('summer_sale_2', $copy->getIdentifier());
        $this->assertSame(' Summer   Sale!! ', $copy->getTitle());
        $this->assertFalse($copy->getIsActive());
        $this->assertSame('nav', $copy->getCssClass());
        $this->assertSame('[1]', $copy->getItemsJson());
        $this->assertSame('header', $copy->getMenuType());
        $this->assertSame(3, $copy->getSortOrder());
    }

    public static function identifierProvider(): array
    {
        return [
            'hyphens become underscores and clash is suffixed' => ['Top-Nav Copy', 'top_nav_copy_1'],
            'symbols collapse' => ['Summer/Sale -- 2026', 'summer_sale_2026'],
            'no usable characters' => ['!!! ---', 'menu_copy'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('identifierProvider')]
    public function testGeneratedIdentifierIsValidAndUnique(string $title, string $expected): void
    {
        $this->params = ['menu_id' => 1, 'new_title' => $title];

        $this->controller([
            ['menu_id' => 1, 'identifier' => 'main'],
            ['menu_id' => 2, 'identifier' => 'top_nav_copy'],
        ])->execute();

        $identifier = $this->created[1]->getIdentifier();
        $this->assertSame($expected, $identifier);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $identifier);
    }

    public function testSaveErrorIsReported(): void
    {
        $this->saveFails = true;
        $this->params = ['menu_id' => 1, 'new_title' => 'Copy'];

        $this->controller([['menu_id' => 1, 'identifier' => 'main']])->execute();

        $this->assertSame(['success' => false, 'message' => 'Error: disk full'], $this->jsonPayload);
    }

    public function testAcl(): void
    {
        $this->assertAclResource($this->controller([]), 'Panth_MegaMenu::menu');
    }
}
