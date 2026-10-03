<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller\Adminhtml\Menu;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\Core\Security\UploadExtensionPolicy;
use Panth\MegaMenu\Controller\Adminhtml\Menu\Upload;
use Panth\MegaMenu\Test\Unit\Controller\ControllerTestTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UploadTest extends TestCase
{
    use ControllerTestTrait;

    private Uploader&MockObject $uploader;
    private WriteInterface&MockObject $writeDir;
    private array $filesBackup = [];

    protected function setUp(): void
    {
        $this->filesBackup = $_FILES;
    }

    protected function tearDown(): void
    {
        $_FILES = $this->filesBackup;
    }

    private function controller(bool $mimeOk = true, $saveResult = ['file' => 'pic.png', 'tmp_name' => '/tmp/x'], bool $dirExists = true): Upload
    {
        $this->uploader = $this->createMock(Uploader::class);
        $this->uploader->method('checkMimeType')->willReturn($mimeOk);
        $this->uploader->method('save')->willReturn($saveResult);
        $factory = $this->createStub(UploaderFactory::class);
        $factory->method('create')->willReturn($this->uploader);

        $readDir = $this->createStub(ReadInterface::class);
        $readDir->method('getAbsolutePath')->willReturn('/media/panth/megamenu/');
        $this->writeDir = $this->createMock(WriteInterface::class);
        $this->writeDir->method('isDirectory')->willReturn($dirExists);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($readDir);
        $filesystem->method('getDirectoryWrite')->willReturn($this->writeDir);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Upload($this->context(), $factory, $filesystem, $storeManager, new UploadExtensionPolicy());
    }

    public function testSuccessfulUploadReturnsMediaUrl(): void
    {
        $controller = $this->controller();
        $this->uploader->expects($this->once())->method('setAllowedExtensions')->with(['jpg', 'jpeg', 'gif', 'png', 'webp']);
        $this->uploader->expects($this->once())->method('setAllowRenameFiles')->with(true);
        $this->uploader->expects($this->once())->method('setFilesDispersion')->with(false);
        $this->writeDir->expects($this->never())->method('create');

        $controller->execute();

        $this->assertSame([
            'file' => 'pic.png',
            'url' => 'https://shop.test/media/panth/megamenu/pic.png',
            'path' => 'panth/megamenu/pic.png',
            'name' => 'pic.png',
        ], $this->jsonPayload);
    }

    public function testMissingDirectoryIsCreated(): void
    {
        $controller = $this->controller(true, ['file' => 'a.jpg'], false);
        $this->writeDir->expects($this->once())->method('create')->with('panth/megamenu');

        $controller->execute();

        $this->assertSame('panth/megamenu/a.jpg', $this->jsonPayload['path']);
    }

    public function testDangerousExtensionRejectedBeforeUpload(): void
    {
        $_FILES = ['image' => ['name' => 'shell.php']];
        $controller = $this->controller();
        $this->uploader->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame('This file type is not allowed.', $this->jsonPayload['error']);
    }

    public function testMimeMismatchRejected(): void
    {
        $controller = $this->controller(false);
        $this->uploader->expects($this->never())->method('save');

        $controller->execute();

        $this->assertSame('File validation failed.', $this->jsonPayload['error']);
    }

    public function testFailedSaveReported(): void
    {
        $this->controller(true, false)->execute();

        $this->assertSame('File cannot be saved to path: /media/panth/megamenu/', $this->jsonPayload['error']);
    }

    public function testCustomParamNameIsUsed(): void
    {
        $this->params = ['param_name' => 'icon'];
        $_FILES = ['icon' => ['name' => 'run.phtml']];

        $this->controller()->execute();

        $this->assertSame('This file type is not allowed.', $this->jsonPayload['error']);
    }

    public function testCsrfAndAcl(): void
    {
        $controller = $this->controller();

        $this->assertTrue($controller->validateForCsrf($this->createStub(RequestInterface::class)));
        $this->assertNull($controller->createCsrfValidationException($this->createStub(RequestInterface::class)));
        $this->assertAclResource($controller, 'Panth_MegaMenu::menu');
    }
}
