<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Upload extends Action implements CsrfAwareActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $uploaderFactory;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * Upload image file
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $fileId = $this->getRequest()->getParam('param_name', 'image');

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $destinationPath = $mediaDirectory->getAbsolutePath('panth/megamenu/');

            // Create directory if it doesn't exist
            $mediaDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if (!$mediaDirectoryWrite->isDirectory('panth/megamenu')) {
                $mediaDirectoryWrite->create('panth/megamenu');
            }

            $result = $uploader->save($destinationPath);

            if (!$result) {
                throw new LocalizedException(__('File cannot be saved to path: %1', $destinationPath));
            }

            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );

            $result['url'] = $mediaUrl . 'panth/megamenu/' . $result['file'];
            $result['path'] = 'panth/megamenu/' . $result['file'];
            $result['name'] = $result['file'];

            return $resultJson->setData($result);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
