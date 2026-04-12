<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\MegaMenu\Model\MenuFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Toggle Menu Enable/Disable
 */
class Toggle extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $menuFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        MenuFactory $menuFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->menuFactory = $menuFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);

        try {
            $menuId = $this->getRequest()->getParam('menu_id');
            $isActive = $this->getRequest()->getParam('is_active');

            if (!$menuId) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu ID is required'
                ]);
            }

            $menu = $this->menuFactory->create()->load($menuId);

            if (!$menu->getId()) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu not found'
                ]);
            }

            $menu->setIsActive($isActive ? 1 : 0);
            $menu->save();

            return $result->setData([
                'success' => true,
                'message' => 'Menu ' . ($isActive ? 'enabled' : 'disabled') . ' successfully'
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
