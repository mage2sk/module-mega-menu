<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Export;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use Panth\MegaMenu\Test\Unit\Controller\MenuModelTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ExportTest extends TestCase
{
    use ControllerTestTrait;
    use MenuModelTrait;

    private const ROW = [
        'menu_id' => 4,
        'identifier' => 'main',
        'title' => 'Main',
        'is_active' => 1,
        'sort_order' => 2,
        'items_json' => '[{"title":"Home"}]',
    ];

    private function controller(array $rows, bool $loadFails = false): Export
    {
        $factory = $this->createStub(MenuFactory::class);
        if ($loadFails) {
            $factory->method('create')->willThrowException(new \RuntimeException('db gone'));
        } else {
            $factory->method('create')->willReturnCallback(fn () => $this->selfLoadingMenu($rows));
        }

        return new Export($this->context(), $this->jsonFactory(), $factory);
    }

    public function testAjaxWithoutIdAndMissingMenu(): void
    {
        $this->params = ['ajax' => 1];
        $this->controller([])->execute();
        $this->assertSame(['success' => false, 'message' => 'Menu ID is required'], $this->jsonPayload);

        $this->params = ['ajax' => 1, 'menu_id' => 9];
        $this->controller([])->execute();
        $this->assertSame(['success' => false, 'message' => 'Menu not found'], $this->jsonPayload);
    }

    public function testNonAjaxErrorsRedirectToIndex(): void
    {
        $this->controller([])->execute();
        $this->assertSame(['Menu ID is required'], $this->messages['error']);
        $this->assertSame(['*/*/index', []], $this->redirectedTo);

        $this->params = ['menu_id' => 9];
        $this->controller([])->execute();
        $this->assertSame(['Menu ID is required', 'Menu not found'], $this->messages['error']);
    }

    public function testAjaxExportReturnsStructuredData(): void
    {
        $this->params = ['ajax' => 1, 'menu_id' => 4];
        $this->controller([self::ROW])->execute();

        $this->assertTrue($this->jsonPayload['success']);
        $data = $this->jsonPayload['data'];
        $this->assertSame('main', $data['menu']['identifier']);
        $this->assertTrue($data['menu']['is_active']);
        $this->assertSame(2, $data['menu']['sort_order']);
        $this->assertSame([['title' => 'Home']], $data['items']);
        $this->assertSame('1.0.0', $data['export_info']['version']);
    }

    public function testFileDownloadSetsHeadersAndBody(): void
    {
        $this->params = ['menu_id' => 4];
        $result = $this->controller([self::ROW])->execute();

        $this->assertSame($this->response, $result);
        $this->assertSame('application/json', $this->headers['Content-Type']);
        $this->assertMatchesRegularExpression('/^attachment; filename="menu_main_\d{8}_\d{6}\.json"$/', $this->headers['Content-Disposition']);
        $this->assertSame(strlen($this->body), $this->headers['Content-Length']);
        $decoded = json_decode($this->body, true);
        $this->assertSame('Main', $decoded['menu']['title']);
    }

    public function testMenuWithoutItemsExportsEmptyList(): void
    {
        $this->params = ['ajax' => 1, 'menu_id' => 5];
        $this->controller([['menu_id' => 5, 'identifier' => 'x']])->execute();

        $this->assertSame([], $this->jsonPayload['data']['items']);
    }

    public function testExceptionsAreReported(): void
    {
        $this->params = ['ajax' => 1, 'menu_id' => 4];
        $this->controller([], true)->execute();
        $this->assertSame(['success' => false, 'message' => 'Error: db gone'], $this->jsonPayload);

        $this->params = ['menu_id' => 4];
        $this->controller([], true)->execute();
        $this->assertSame(['Error: db gone'], $this->messages['error']);
        $this->assertSame(['*/*/index', []], $this->redirectedTo);
    }

    public function testCsrfAndAcl(): void
    {
        $controller = $this->controller([]);

        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
