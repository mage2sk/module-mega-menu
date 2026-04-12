<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Import Menu from JSON
 */
class Import extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $menuFactory;
    protected $menuRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        MenuFactory $menuFactory,
        MenuRepositoryInterface $menuRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->menuFactory = $menuFactory;
        $this->menuRepository = $menuRepository;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);

        try {
            $menuDataJson = $this->getRequest()->getParam('menu_data');

            if (!$menuDataJson) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu data is required'
                ]);
            }

            // Validate and parse JSON data
            $menuData = json_decode($menuDataJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Invalid JSON: ' . json_last_error_msg()
                ]);
            }

            if (!$menuData || !isset($menuData['menu']) || !isset($menuData['items'])) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Invalid JSON format. Expected format: {menu: {...}, items: [...]}'
                ]);
            }

            $importedMenu = $menuData['menu'];
            $importedItems = $menuData['items'];

            // Validate required fields
            if (empty($importedMenu['identifier'])) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Menu identifier is required in the JSON data'
                ]);
            }

            // Check if menu with this identifier already exists
            $identifier = $importedMenu['identifier'];
            $existingMenu = $this->menuFactory->create();
            $existingMenu->load($identifier, 'identifier');

            if ($existingMenu->getId()) {
                // Update existing menu
                $menu = $existingMenu;
                $action = 'updated';
            } else {
                // Create new menu
                $menu = $this->menuFactory->create();
                $menu->setIdentifier($identifier);
                $action = 'created';
            }

            // Set menu properties
            $menu->setTitle($importedMenu['title'] ?? 'Imported Menu');
            $menu->setMenuType($importedMenu['menu_type'] ?? 'horizontal');
            $menu->setIsActive($importedMenu['is_active'] ?? 1);
            $menu->setCssClass($importedMenu['css_class'] ?? '');
            $menu->setSortOrder($importedMenu['sort_order'] ?? 0);
            $menu->setDescription($importedMenu['description'] ?? '');
            $menu->setCustomCss($importedMenu['custom_css'] ?? '');
            $menu->setMobileLayout($importedMenu['mobile_layout'] ?? 'accordion');
            $menu->setItemsJson(json_encode($importedItems));

            // Set container styling properties if available
            if (isset($importedMenu['container_bg_color'])) {
                $menu->setData('container_bg_color', $importedMenu['container_bg_color']);
            }
            if (isset($importedMenu['container_padding'])) {
                $menu->setData('container_padding', $importedMenu['container_padding']);
            }
            if (isset($importedMenu['container_margin'])) {
                $menu->setData('container_margin', $importedMenu['container_margin']);
            }
            if (isset($importedMenu['item_gap'])) {
                $menu->setData('item_gap', $importedMenu['item_gap']);
            }
            if (isset($importedMenu['container_max_width'])) {
                $menu->setData('container_max_width', $importedMenu['container_max_width']);
            }
            if (isset($importedMenu['container_border'])) {
                $menu->setData('container_border', $importedMenu['container_border']);
            }
            if (isset($importedMenu['container_border_radius'])) {
                $menu->setData('container_border_radius', $importedMenu['container_border_radius']);
            }
            if (isset($importedMenu['container_box_shadow'])) {
                $menu->setData('container_box_shadow', $importedMenu['container_box_shadow']);
            }

            // Handle store IDs
            if (isset($importedMenu['store_ids'])) {
                $storeIds = is_array($importedMenu['store_ids'])
                    ? $importedMenu['store_ids']
                    : explode(',', $importedMenu['store_ids']);
                $menu->setStoreIds($storeIds);
            }

            // Save through repository to trigger versioning
            $this->menuRepository->save($menu);

            return $result->setData([
                'success' => true,
                'message' => sprintf('Menu "%s" %s successfully', $menu->getTitle(), $action),
                'menu_id' => $menu->getId(),
                'action' => $action
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
