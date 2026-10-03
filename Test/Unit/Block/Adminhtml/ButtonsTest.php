<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Panth\MegaMenu\Block\Adminhtml\Button\Import as GridImport;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\BackButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\DeleteButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\DuplicateButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\PreviewButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\ResetButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\SaveAndContinueButton;
use Panth\MegaMenu\Block\Adminhtml\Item\Edit\SaveButton;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\Back;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\Export;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\Import as FormImport;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\Preview;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\Save;
use Panth\MegaMenu\Block\Adminhtml\Menu\Button\SaveAndContinue;
use Panth\MegaMenu\Block\Adminhtml\Menu\ImportButton;
use PHPUnit\Framework\TestCase;

class ButtonsTest extends TestCase
{
    private function url(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            fn (string $path, ?array $params = null) => '/admin/' . $path . ($params ? '?' . http_build_query($params) : '')
        );

        return $url;
    }

    private function request(array $params): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($name, $default = null) => $params[$name] ?? $default);

        return $request;
    }

    private function widgetContext(array $params): Context
    {
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($this->request($params));
        $context->method('getUrlBuilder')->willReturn($this->url());

        return $context;
    }

    public function testStaticButtons(): void
    {
        $this->assertSame('save', (new Save())->getButtonData()['data_attribute']['mage-init']['button']['event']);
        $this->assertSame(90, (new Save())->getButtonData()['sort_order']);
        $this->assertSame('saveAndContinueEdit', (new SaveAndContinue())->getButtonData()['data_attribute']['mage-init']['button']['event']);
        $this->assertSame('saveAndContinueEdit', (new SaveAndContinueButton())->getButtonData()['data_attribute']['mage-init']['button']['event']);
        $this->assertSame('Save Item', (string) (new SaveButton())->getButtonData()['label']);
        $this->assertSame('location.reload();', (new ResetButton())->getButtonData()['on_click']);
        $this->assertSame('import-menu-button-form', (new FormImport())->getButtonData()['id']);
        $this->assertSame('Panth_MegaMenu::menu', (new GridImport())->getButtonData()['aclResource']);
        $this->assertSame('save', (new ImportButton())->getButtonData()['data_attribute']['form-role']);
    }

    public function testMenuBackAndPreview(): void
    {
        $back = new Back($this->url());
        $this->assertSame('/admin/*/*/', $back->getBackUrl());
        $this->assertSame("location.href = '/admin/*/*/';", $back->getButtonData()['on_click']);

        $preview = (new Preview($this->url()))->getButtonData();
        $this->assertSame('window.panthPreviewMenu(); return false;', $preview['on_click']);
        $this->assertSame(25, $preview['sort_order']);
    }

    public function testExportButtonOnlyForExistingMenu(): void
    {
        $this->assertSame([], (new Export($this->request([]), $this->url()))->getButtonData());

        $data = (new Export($this->request(['menu_id' => '7']), $this->url()))->getButtonData();
        $this->assertSame("window.location.href = '/admin/panth_menu/menu/export?menu_id=7';", $data['on_click']);
    }

    public function testItemButtonsDependOnItemId(): void
    {
        $this->assertSame([], (new DeleteButton($this->widgetContext([])))->getButtonData());
        $this->assertSame([], (new DuplicateButton($this->widgetContext([])))->getButtonData());
        $this->assertSame([], (new PreviewButton($this->widgetContext([])))->getButtonData());

        $context = $this->widgetContext(['item_id' => 5]);
        $delete = new DeleteButton($context);
        $this->assertSame('/admin/*/*/delete?item_id=5', $delete->getDeleteUrl());
        $this->assertStringContainsString("'/admin/*/*/delete?item_id=5')", $delete->getButtonData()['on_click']);
        $this->assertStringContainsString('/admin/*/*/duplicate?item_id=5', (new DuplicateButton($context))->getButtonData()['on_click']);
        $this->assertSame("window.open('/admin/*/*/preview?item_id=5', '_blank');", (new PreviewButton($context))->getButtonData()['on_click']);

        $back = new BackButton($context);
        $this->assertSame('/admin/*/*/', $back->getBackUrl());
        $this->assertSame(10, $back->getButtonData()['sort_order']);
    }
}
