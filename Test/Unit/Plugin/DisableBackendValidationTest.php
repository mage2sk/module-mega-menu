<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Plugin;

use Magento\Backend\App\Request\BackendValidator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\CustomSave;
use Panth\MegaMenu\Controller\Adminhtml\Menu\GetCategories;
use Panth\MegaMenu\Controller\Adminhtml\Menu\GetIcons;
use Panth\MegaMenu\Controller\Adminhtml\Menu\ImportCategories;
use Panth\MegaMenu\Plugin\DisableBackendValidation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DisableBackendValidationTest extends TestCase
{
    public function testCustomSaveValidatesFormKeyThenProceeds(): void
    {
        $request = $this->createStub(RequestInterface::class);
        $action = $this->createMock(CustomSave::class);
        $action->expects($this->once())->method('validateForCsrf')->with($request)->willReturn(true);
        $calls = 0;
        $proceed = function ($req, $act) use (&$calls, $request, $action) {
            $calls++;
            $this->assertSame($request, $req);
            $this->assertSame($action, $act);
            return 'validated';
        };

        $result = (new DisableBackendValidation())->aroundValidate(
            $this->createStub(BackendValidator::class),
            $proceed,
            $request,
            $action
        );

        $this->assertSame('validated', $result);
        $this->assertSame(1, $calls);
    }

    public static function allowListProvider(): array
    {
        return [[GetCategories::class], [ImportCategories::class], [GetIcons::class]];
    }

    #[DataProvider('allowListProvider')]
    public function testAllowListedJsonEndpointsBypassValidation(string $class): void
    {
        $action = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $proceed = function (): void {
            $this->fail('Validation should be skipped');
        };

        $result = (new DisableBackendValidation())->aroundValidate(
            $this->createStub(BackendValidator::class),
            $proceed,
            $this->createStub(RequestInterface::class),
            $action
        );

        $this->assertTrue($result);
    }

    public function testOtherActionsAreValidatedNormally(): void
    {
        $proceed = fn () => 'proceeded';

        $result = (new DisableBackendValidation())->aroundValidate(
            $this->createStub(BackendValidator::class),
            $proceed,
            $this->createStub(RequestInterface::class),
            $this->createStub(ActionInterface::class)
        );

        $this->assertSame('proceeded', $result);
    }
}
