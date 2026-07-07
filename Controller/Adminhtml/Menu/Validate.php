<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;

class Validate extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    protected $resultJsonFactory;

    protected $menuFactory;

    protected $menuResource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MenuFactory $menuFactory,
        MenuResource $menuResource
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->menuFactory = $menuFactory;
        $this->menuResource = $menuResource;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $response = ['error' => false, 'messages' => []];
        $data = $this->getRequest()->getPostValue();

        if (empty($data['title'])) {
            $response['error'] = true;
            $response['messages'][] = __('Menu Title is required.');
        }

        if (empty($data['identifier'])) {
            $response['error'] = true;
            $response['messages'][] = __('Identifier is required.');
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['identifier'])) {
            $response['error'] = true;
            $response['messages'][] = __('Identifier can only contain lowercase letters, numbers, and underscores.');
        } else {
            $menuId = $this->getRequest()->getParam('menu_id');
            $existingMenu = $this->menuFactory->create();
            $this->menuResource->load($existingMenu, $data['identifier'], 'identifier');

            if ($existingMenu->getId() && $existingMenu->getId() != $menuId) {
                $response['error'] = true;
                $response['messages'][] = __('Menu identifier "%1" already exists.', $data['identifier']);
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
