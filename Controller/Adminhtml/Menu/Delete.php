<?php
/**
 * Panth Mega Menu Admin Controller - Delete Menu
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\MegaMenu\Api\MenuRepositoryInterface;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu_delete';

    protected $menuRepository;
    protected $jsonFactory;

    public function __construct(
        Context $context,
        MenuRepositoryInterface $menuRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->menuRepository = $menuRepository;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        // Check if it's an AJAX request
        if ($this->getRequest()->isAjax() || $this->getRequest()->getParam('ajax')) {
            return $this->executeJson();
        }

        return $this->executeRedirect();
    }

    protected function executeJson()
    {
        $result = $this->jsonFactory->create();
        $id = (int)$this->getRequest()->getParam('menu_id');

        if (!$id) {
            return $result->setData(['success' => false, 'message' => 'Menu ID not found']);
        }

        try {
            $this->menuRepository->deleteById($id);
            return $result->setData(['success' => true, 'message' => 'Menu deleted successfully']);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    protected function executeRedirect()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('menu_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a menu to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->menuRepository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('The menu has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['menu_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
