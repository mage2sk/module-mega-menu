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
 * Duplicate Menu with all items
 */
class Duplicate extends Action implements CsrfAwareActionInterface
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

            // Create new menu with copied data
            $newMenu = $this->menuFactory->create();
            $newMenu->setTitle($newTitle);

            // Generate unique identifier
            $newIdentifier = $this->generateUniqueIdentifier($newTitle);
            $newMenu->setIdentifier($newIdentifier);

            // Copy other properties
            $newMenu->setMenuType($originalMenu->getMenuType());
            $newMenu->setIsActive(0); // Set as inactive by default
            $newMenu->setCssClass($originalMenu->getCssClass());
            $newMenu->setSortOrder($originalMenu->getSortOrder());
            $newMenu->setDescription($originalMenu->getDescription());
            $newMenu->setCustomCss($originalMenu->getCustomCss());
            $newMenu->setMobileLayout($originalMenu->getMobileLayout());

            // Copy items_json (all menu items)
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

    /**
     * Generate unique identifier from title
     */
    protected function generateUniqueIdentifier($title)
    {
        $identifier = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $title)));
        $identifier = preg_replace('/_+/', '_', $identifier);
        $identifier = trim($identifier, '_');

        // Check if identifier exists and append number if needed
        $originalIdentifier = $identifier;
        $counter = 1;

        while ($this->identifierExists($identifier)) {
            $identifier = $originalIdentifier . '_' . $counter;
            $counter++;
        }

        return $identifier;
    }

    /**
     * Check if identifier exists
     */
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

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
