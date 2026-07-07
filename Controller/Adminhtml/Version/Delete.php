<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Version;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;

class Delete extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    protected $menuVersionFactory;

    protected $menuVersionResource;

    public function __construct(
        Context $context,
        MenuVersionFactory $menuVersionFactory,
        MenuVersionResource $menuVersionResource
    ) {
        $this->menuVersionFactory = $menuVersionFactory;
        $this->menuVersionResource = $menuVersionResource;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $versionId = (int) $this->getRequest()->getParam('version_id');

        if (!$versionId) {
            $this->messageManager->addErrorMessage(__('Version ID is required.'));
            return $resultRedirect->setPath('panth_menu/menu/');
        }

        try {
            $version = $this->menuVersionFactory->create();
            $this->menuVersionResource->load($version, $versionId);

            if (!$version->getId()) {
                throw new LocalizedException(__('Version not found.'));
            }

            $menuId = $version->getMenuId();
            $versionNumber = $version->getVersionNumber();

            $this->menuVersionResource->delete($version);

            $this->messageManager->addSuccessMessage(
                __('Version #%1 has been deleted.', $versionNumber)
            );

            return $resultRedirect->setPath('panth_menu/menu/edit', ['menu_id' => $menuId]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while deleting the version.')
            );
        }

        return $resultRedirect->setPath('panth_menu/menu/');
    }
}
