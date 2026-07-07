<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;

class Edit extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    protected $resultPageFactory;

    protected $menuFactory;

    protected $menuResource;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        MenuFactory $menuFactory,
        MenuResource $menuResource
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->menuFactory = $menuFactory;
        $this->menuResource = $menuResource;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $menuId = $this->getRequest()->getParam('menu_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_MegaMenu::menu');

        if ($menuId) {
            $menu = $this->menuFactory->create();
            $this->menuResource->load($menu, $menuId);

            if (!$menu->getId()) {
                $this->messageManager->addErrorMessage(__('This menu no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }

            $resultPage->getConfig()->getTitle()->prepend(__('Edit Menu: %1', $menu->getTitle()));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Menu'));
        }

        return $resultPage;
    }
}
