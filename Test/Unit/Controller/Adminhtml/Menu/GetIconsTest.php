<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Controller\Adminhtml\Menu\GetIcons;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GetIconsTest extends TestCase
{
    use ControllerTestTrait;

    public static function libraryProvider(): array
    {
        return [
            'fontawesome' => ['fontawesome', 20, 'class', 'fa-solid fa-home'],
            'lineicons' => ['lineicons', 10, 'class', 'lni lni-home'],
            'feather' => ['feather', 10, 'class', 'feather-home'],
            'material' => ['material', 10, 'text', 'home'],
            'emoji' => ['emoji', 14, 'name', 'Home'],
        ];
    }

    #[DataProvider('libraryProvider')]
    public function testLibraries(string $library, int $count, string $field, string $first): void
    {
        $this->params = ['library' => $library];
        (new GetIcons($this->context(), $this->jsonFactory()))->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $this->assertSame($library, $this->jsonPayload['library']);
        $this->assertCount($count, $this->jsonPayload['icons']);
        $this->assertSame($first, $this->jsonPayload['icons'][0][$field]);
        foreach ($this->jsonPayload['icons'] as $icon) {
            $this->assertNotEmpty($icon['name']);
        }
    }

    public function testDefaultsToFontAwesome(): void
    {
        (new GetIcons($this->context(), $this->jsonFactory()))->execute();

        $this->assertSame('fontawesome', $this->jsonPayload['library']);
        $this->assertCount(20, $this->jsonPayload['icons']);
    }

    public function testUnknownLibraryReturnsEmptyList(): void
    {
        $this->params = ['library' => 'bootstrap'];
        $controller = new GetIcons($this->context(), $this->jsonFactory());
        $controller->execute();

        $this->assertSame(['success' => true, 'library' => 'bootstrap', 'icons' => []], $this->jsonPayload);
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
