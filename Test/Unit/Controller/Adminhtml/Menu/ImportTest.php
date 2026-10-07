<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Import;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ImportTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private MenuRepositoryInterface&MockObject $repository;
    private ?Menu $saved = null;

    private function controller(array $rows = []): Import
    {
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->selfLoadingMenu($rows));
        $this->repository = $this->createMock(MenuRepositoryInterface::class);
        $this->repository->method('save')->willReturnCallback(function (Menu $menu) {
            if (!$menu->getId()) {
                $menu->setId(30);
            }
            $this->saved = $menu;
            return $menu;
        });

        return new Import($this->context(), $this->jsonFactory(), $factory, $this->repository);
    }

    public static function rejectedProvider(): array
    {
        return [
            'missing' => [null, 'Menu data is required'],
            'too large' => [str_repeat('a', 5242881), 'Menu data is too large or malformed'],
            'array param' => [['x'], 'Menu data is too large or malformed'],
            'bad json' => ['{nope', 'Invalid JSON: Syntax error'],
            'wrong shape' => ['{"menu":{"identifier":"a"}}', 'Invalid JSON format. Expected format: {menu: {...}, items: [...]}'],
            'items not array' => ['{"menu":{"identifier":"a"},"items":"x"}', 'Invalid JSON format. Expected format: {menu: {...}, items: [...]}'],
            'no identifier' => ['{"menu":{"title":"a"},"items":[]}', 'Menu identifier is required in the JSON data'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function testRejectedPayloads(mixed $payload, string $message): void
    {
        $this->params = ['menu_data' => $payload];
        $controller = $this->controller();
        $this->repository->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame(['success' => false, 'message' => $message], $this->jsonPayload);
    }

    public function testTooDeepJsonIsRejected(): void
    {
        $this->params = ['menu_data' => str_repeat('[', 70) . str_repeat(']', 70)];
        $this->controller()->execute();

        $this->assertSame('Invalid JSON: Maximum stack depth exceeded', $this->jsonPayload['message']);
    }

    public function testCreatesNewMenuWithDefaults(): void
    {
        $items = [['title' => 'A']];
        $this->params = ['menu_data' => json_encode([
            'menu' => ['identifier' => 'imported', 'store_ids' => '0,2', 'item_gap' => '3px'],
            'items' => $items,
        ])];

        $this->controller()->execute();

        $this->assertSame([
            'success' => true,
            'message' => 'Menu "Imported Menu" created successfully',
            'menu_id' => 30,
            'action' => 'created',
        ], $this->jsonPayload);
        $this->assertSame('imported', $this->saved->getIdentifier());
        $this->assertSame('horizontal', $this->saved->getMenuType());
        $this->assertTrue($this->saved->getIsActive());
        $this->assertSame(json_encode($items), $this->saved->getItemsJson());
        $this->assertSame(['0', '2'], $this->saved->getStoreIds());
        $this->assertSame('3px', $this->saved->getData('item_gap'));
    }

    public function testUpdatesExistingMenuByIdentifier(): void
    {
        $this->params = ['menu_data' => json_encode([
            'menu' => ['identifier' => 'main', 'title' => 'Renamed', 'is_active' => 0, 'store_ids' => [1]],
            'items' => [],
        ])];

        $this->controller([['menu_id' => 6, 'identifier' => 'main', 'title' => 'Old']])->execute();

        $this->assertSame('updated', $this->jsonPayload['action']);
        $this->assertSame(6, $this->jsonPayload['menu_id']);
        $this->assertSame('Renamed', $this->saved->getTitle());
        $this->assertFalse($this->saved->getIsActive());
        $this->assertSame([1], $this->saved->getStoreIds());
    }

    public function testRepositoryErrorIsReported(): void
    {
        $this->params = ['menu_data' => '{"menu":{"identifier":"a"},"items":[]}'];
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->selfLoadingMenu([]));
        $repository = $this->createStub(MenuRepositoryInterface::class);
        $repository->method('save')->willThrowException(new \RuntimeException('nope'));

        (new Import($this->context(), $this->jsonFactory(), $factory, $repository))->execute();

        $this->assertSame(['success' => false, 'message' => 'Error: nope'], $this->jsonPayload);
    }

    public function testCsrfAndAcl(): void
    {
        $controller = $this->controller();

        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
