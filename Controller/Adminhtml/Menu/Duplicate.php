<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\MegaMenu\Model\MenuFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Duplicate extends Action implements HttpPostActionInterface
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
            $newTitle = $this->getRequest()->getParam('new_title');

            if (!$menuId) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu ID is required'
                ]);
            }

            if (!$newTitle) {
                return $result->setData([
                    'success' => false,
                    'message' => 'New menu title is required'
                ]);
            }

            $originalMenu = $this->menuFactory->create()->load($menuId);

            if (!$originalMenu->getId()) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu not found'
                ]);
            }

            $newMenu = $this->menuFactory->create();
            $newMenu->setTitle($newTitle);

            $newIdentifier = $this->generateUniqueIdentifier($newTitle);
            $newMenu->setIdentifier($newIdentifier);

            $newMenu->setMenuType($originalMenu->getMenuType());
            $newMenu->setIsActive(0);
            $newMenu->setCssClass($originalMenu->getCssClass());
            $newMenu->setSortOrder($originalMenu->getSortOrder());
            $newMenu->setDescription($originalMenu->getDescription());
            $newMenu->setCustomCss($originalMenu->getCustomCss());
            $newMenu->setMobileLayout($originalMenu->getMobileLayout());

            $newMenu->setItemsJson($originalMenu->getItemsJson());

            $newMenu->save();

            return $result->setData([
                'success' => true,
                'message' => 'Menu duplicated successfully',
                'menu_id' => $newMenu->getId()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    protected function generateUniqueIdentifier($title)
    {
        $identifier = (string) preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $title)));
        $identifier = trim($identifier, '_');
        if ($identifier === '') {
            $identifier = 'menu_copy';
        }

        $originalIdentifier = $identifier;
        $counter = 1;

        while ($this->identifierExists($identifier)) {
            $identifier = $originalIdentifier . '_' . $counter;
            $counter++;
        }

        return $identifier;
    }

    protected function identifierExists($identifier)
    {
        $menu = $this->menuFactory->create();
        $menu->load($identifier, 'identifier');
        return $menu->getId() ? true : false;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }
}
