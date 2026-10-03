<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Delete;
use Panth\MegaMenu\Controller\Adminhtml\Menu\FlushCache;
use Panth\MegaMenu\Controller\Adminhtml\Menu\PreviewToken;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Toggle;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Validate;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonActionsTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    public function testFlushCacheCleansPageAndBlockCaches(): void
    {
        $cleaned = [];
        $typeList = $this->createMock(TypeListInterface::class);
        $typeList->expects($this->exactly(2))->method('cleanType')
            ->willReturnCallback(function (string $type) use (&$cleaned): void {
                $cleaned[] = $type;
            });

        (new FlushCache($this->context(), $typeList, $this->jsonFactory()))->execute();

        $this->assertSame(['full_page', 'block_html'], $cleaned);
        $this->assertSame(['success' => true, 'message' => 'Cache flushed'], $this->jsonPayload);
        $this->assertSame('Panth_MegaMenu::menu', FlushCache::ADMIN_RESOURCE);
    }

    public function testFlushCacheReportsError(): void
    {
        $typeList = $this->createStub(TypeListInterface::class);
        $typeList->method('cleanType')->willThrowException(new \RuntimeException('locked'));

        (new FlushCache($this->context(), $typeList, $this->jsonFactory()))->execute();

        $this->assertSame(['success' => false, 'message' => 'locked'], $this->jsonPayload);
    }

    private function validate(array $rows = []): Validate
    {
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->newMenu());

        return new Validate($this->context(), $this->jsonFactory(), $factory, $this->loadingMenuResource($rows));
    }

    public static function invalidProvider(): array
    {
        return [
            'missing both' => [[], ['Menu Title is required.', 'Identifier is required.']],
            'bad identifier' => [['title' => 'T', 'identifier' => 'Main-Menu'], ['Identifier can only contain lowercase letters, numbers, and underscores.']],
            'missing title' => [['identifier' => 'ok_1'], ['Menu Title is required.']],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testValidateRejectsBadInput(array $post, array $expected): void
    {
        $this->postValue = $post;
        $this->validate()->execute();

        $this->assertTrue($this->jsonPayload['error']);
        $this->assertSame($expected, array_map('strval', $this->jsonPayload['messages']));
    }

    public function testValidateDetectsDuplicateIdentifier(): void
    {
        $this->postValue = ['title' => 'T', 'identifier' => 'main'];
        $this->params = ['menu_id' => 2];
        $this->validate([['menu_id' => 1, 'identifier' => 'main']])->execute();

        $this->assertTrue($this->jsonPayload['error']);
        $this->assertSame('Menu identifier "main" already exists.', (string) $this->jsonPayload['messages'][0]);
    }

    public function testValidateAllowsSameMenuToKeepIdentifier(): void
    {
        $this->postValue = ['title' => 'T', 'identifier' => 'main'];
        $this->params = ['menu_id' => '1'];
        $this->validate([['menu_id' => 1, 'identifier' => 'main']])->execute();

        $this->assertSame(['error' => false, 'messages' => []], $this->jsonPayload);
    }

    private function toggle(?Menu $menu): Toggle
    {
        $factory = $this->createStub(MenuFactory::class);
        $factory->method('create')->willReturn($menu ?? $this->createStub(Menu::class));

        return new Toggle($this->context(), $this->jsonFactory(), $factory);
    }

    private function partialMenu(?int $id): Menu
    {
        $menu = $this->createPartialMock(Menu::class, ['load', 'save', 'getId']);
        $menu->method('load')->willReturnSelf();
        $menu->method('getId')->willReturn($id);

        return $menu;
    }

    public function testToggleRequiresMenuId(): void
    {
        $this->toggle(null)->execute();

        $this->assertSame(['success' => false, 'message' => 'Menu ID is required'], $this->jsonPayload);
        $this->assertSame('application/json', $this->headers['Content-Type']);
    }

    public function testToggleMissingMenu(): void
    {
        $this->params = ['menu_id' => 5];
        $menu = $this->partialMenu(null);
        $menu->expects($this->never())->method('save');
        $this->toggle($menu)->execute();

        $this->assertSame(['success' => false, 'message' => 'Menu not found'], $this->jsonPayload);
    }

    public function testToggleEnablesAndDisables(): void
    {
        $menu = $this->partialMenu(5);
        $menu->expects($this->exactly(2))->method('save');

        $this->params = ['menu_id' => 5, 'is_active' => '1'];
        $this->toggle($menu)->execute();
        $this->assertTrue($menu->getIsActive());
        $this->assertSame('Menu enabled successfully', $this->jsonPayload['message']);

        $this->params = ['menu_id' => 5, 'is_active' => '0'];
        $this->toggle($menu)->execute();
        $this->assertFalse($menu->getIsActive());
        $this->assertSame(['success' => true, 'message' => 'Menu disabled successfully'], $this->jsonPayload);
    }

    public function testToggleSaveError(): void
    {
        $menu = $this->partialMenu(5);
        $menu->expects($this->once())->method('save')->willThrowException(new \RuntimeException('ro'));
        $this->params = ['menu_id' => 5, 'is_active' => 1];

        $this->toggle($menu)->execute();

        $this->assertSame(['success' => false, 'message' => 'Error: ro'], $this->jsonPayload);
    }

    public function testToggleAcl(): void
    {
        $this->assertAclResource($this->toggle(null), 'Panth_MegaMenu::menu');
    }

    public function testPreviewTokenRequiresMenuId(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('save');

        (new PreviewToken($this->context(), $this->jsonFactory(), $cache))->execute();

        $this->assertSame(['success' => false, 'message' => 'Missing menu_id'], $this->jsonPayload);
    }

    public function testPreviewTokenStoresMenuIdUnderToken(): void
    {
        $stored = null;
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('save')
            ->willReturnCallback(function ($data, $id, $tags, $lifetime) use (&$stored) {
                $stored = [$data, $id, $tags, $lifetime];
                return true;
            });
        $this->params = ['menu_id' => 12];

        $controller = new PreviewToken($this->context(), $this->jsonFactory(), $cache);
        $controller->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $token = $this->jsonPayload['token'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame(['12', PreviewToken::CACHE_PREFIX . $token, ['megamenu_preview'], 300], $stored);
        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }

    private function delete(MenuRepositoryInterface $repository): Delete
    {
        return new Delete($this->context(), $repository, $this->jsonFactory());
    }

    public function testDeleteAjaxWithoutId(): void
    {
        $this->ajax = true;
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->never())->method('deleteById');

        $this->delete($repository)->execute();

        $this->assertSame(['success' => false, 'message' => 'Menu ID not found'], $this->jsonPayload);
    }

    public function testDeleteAjaxSuccessAndFailure(): void
    {
        $this->params = ['menu_id' => '3', 'ajax' => 1];
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->exactly(2))->method('deleteById')->with(3)
            ->willReturnOnConsecutiveCalls(true, $this->throwException(new \RuntimeException('in use')));

        $this->delete($repository)->execute();
        $this->assertSame(['success' => true, 'message' => 'Menu deleted successfully'], $this->jsonPayload);

        $this->delete($repository)->execute();
        $this->assertSame(['success' => false, 'message' => 'in use'], $this->jsonPayload);
    }

    public function testDeleteRedirectFlow(): void
    {
        $repository = $this->createMock(MenuRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteById')->with(7);

        $this->delete($repository)->execute();
        $this->assertSame(['*/*/', []], $this->redirectedTo);
        $this->assertSame(["We can't find a menu to delete."], $this->messages['error']);

        $this->params = ['menu_id' => 7];
        $this->delete($repository)->execute();
        $this->assertSame(['*/*/', []], $this->redirectedTo);
        $this->assertSame(['The menu has been deleted.'], $this->messages['success']);
    }

    public function testDeleteRedirectFailureReturnsToEdit(): void
    {
        $repository = $this->createStub(MenuRepositoryInterface::class);
        $repository->method('deleteById')->willThrowException(new \RuntimeException('fk'));
        $this->params = ['menu_id' => 7];

        $this->delete($repository)->execute();

        $this->assertSame(['*/*/edit', ['menu_id' => 7]], $this->redirectedTo);
        $this->assertSame(['fk'], $this->messages['error']);
        $this->assertSame('Panth_MegaMenu::menu_delete', Delete::ADMIN_RESOURCE);
    }
}
